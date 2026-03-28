<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mutrain\phpunit\local\area;

/**
 * Area base test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\local\area\base
 */
final class base_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_area_classes(): void {
        $classnames = \tool_mutrain\local\area\base::get_area_classes();
        $this->assertIsArray($classnames);
        foreach ($classnames as $classname) {
            $this->assertTrue(class_exists($classname));
        }
        $this->assertArrayHasKey('core_course_course', $classnames);
    }

    public function test_get_area_class(): void {
        $this->assertSame(\tool_mutrain\local\area\core_course_course::class, \tool_mutrain\local\area\base::get_area_class('core_course', 'course'));
    }

    public function test_sync_all_completions(): void {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3']
        );

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 10, 'customfield_field2' => 1, 'enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 20, 'enablecompletion' => 1]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 40, 'enablecompletion' => 1]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field3' => 'abc', 'enablecompletion' => 1]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->assertCount(5, $DB->get_records('customfield_data', []));
        $this->assertCount(0, $DB->get_records('tool_mutrain_completion', []));

        \tool_mutrain\local\area\base::sync_all_completions();
        $this->assertCount(0, $DB->get_records('tool_mutrain_completion', []));

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();

        $this->assertCount(5, $DB->get_records('tool_mutrain_completion', []));
        $DB->delete_records('tool_mutrain_completion', []);

        \tool_mutrain\local\area\base::sync_all_completions();
        $this->assertCount(5, $DB->get_records('tool_mutrain_completion', []));

        $DB->delete_records('customfield_field', ['id' => $field2->get('id')]);
        \tool_mutrain\local\area\base::sync_all_completions();
        $this->assertCount(3, $DB->get_records('tool_mutrain_completion', []));

        $DB->set_field('user', 'deleted', 1, ['id' => $user1->id]);
        \tool_mutrain\local\area\base::sync_all_completions();
        $completions = $DB->get_records('tool_mutrain_completion', []);
        $this->assertCount(1, $completions);

        $completion = reset($completions);
        $this->assertSame($field1->get('id'), (int)$completion->fieldid);
        $this->assertSame($course1->id, $completion->instanceid);
        $this->assertSame($user2->id, $completion->userid);
    }
}
