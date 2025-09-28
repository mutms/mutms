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

namespace tool_mutrain\phpunit\task;

/**
 * Cron test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\task\cron
 */
final class cron_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_name(): void {
        $cron = new \tool_mutrain\task\cron();
        $cron->get_name();
    }

    public function test_execute(): void {
        $cron = new \tool_mutrain\task\cron();
        ob_start();
        $cron->execute();
        $output = ob_get_clean();
        $this->assertSame('', $output);

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

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

        $cron = new \tool_mutrain\task\cron();
        ob_start();
        $cron->execute();
        $output = ob_get_clean();
        $this->assertStringContainsString('tool_mutrain\local\area\core_course_course::sync_area_completions', $output);
    }
}
