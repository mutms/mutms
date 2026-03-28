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
 * Course custom fields test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\local\area\core_course_course
 */
final class core_course_course_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_category_select(): void {
        global $DB;

        $select = \tool_mutrain\local\area\core_course_course::get_category_select('xx');
        $sql = "SELECT xx.*
                  FROM {customfield_category} xx
                 WHERE $select";
        $this->assertCount(0, $DB->get_records_sql($sql));

        $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_group', 'area' => 'group']
        );
        $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_group', 'area' => 'group']
        );
        $sql = "SELECT xx.*
                  FROM {customfield_category} xx
                 WHERE $select";
        $this->assertCount(1, $DB->get_records_sql($sql));
    }

    public function test_sync_area_completions(): void {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2', 'name' => 'F2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3', 'name' => 'F3']
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
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course3->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course4->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course4->id, 'userid' => $user4->id]);
        $ccompletion->mark_complete();
        $DB->set_field('course_completions', 'timecompleted', null, ['id' => $ccompletion->id]);

        $DB->delete_records('tool_mutrain_completion', []);

        // Add completions.
        \tool_mutrain\local\area\core_course_course::sync_area_completions();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(5, $completions);

        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course3->id, 'userid' => $user1->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]
        ));

        // No modifications.
        \tool_mutrain\local\area\core_course_course::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('tool_mutrain_completion', [], 'id ASC'));

        // Removing of completions.
        $DB->delete_records('course_completions', ['course' => $course1->id, 'userid' => $user1->id]);
        \tool_mutrain\local\area\core_course_course::sync_area_completions();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(3, $completions);
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]
        ));
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user1->id]
        ));

        // Date sync.
        $DB->set_field('tool_mutrain_completion', 'timecompleted', '1', []);
        \tool_mutrain\local\area\core_course_course::sync_area_completions();
        $completions2 = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertEquals($completions, $completions2);

        // Remove pending null completions.
        $DB->set_field('course_completions', 'timecompleted', null, ['userid' => $user2->id, 'course' => $course1->id]);
        \tool_mutrain\local\area\core_course_course::sync_area_completions();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(1, $completions);
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]
        ));
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]
        ));
    }

    public function test_observe_course_completed(): void {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2', 'name' => 'F2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3', 'name' => 'F3']
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
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $this->assertCount(5, $DB->get_records('customfield_data', []));
        $this->assertCount(0, $DB->get_records('tool_mutrain_completion', []));

        $ccompletion = new \completion_completion(['course' => $course4->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $this->assertCount(0, $DB->get_records('tool_mutrain_completion', []));

        $ccompletion = new \completion_completion(['course' => $course3->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(1, $completions);
        $completion = reset($completions);
        $ccompletion = $DB->get_record('course_completions', ['course' => $course3->id, 'userid' => $user1->id]);
        $this->assertSame((string)$field1->get('id'), $completion->fieldid);
        $this->assertSame($course3->id, $completion->instanceid);
        $this->assertSame($user1->id, $completion->userid);
        $this->assertSame($ccompletion->timecompleted, $completion->timecompleted);
        $oldid = $completion->id;

        $ccompletion = new \completion_completion(['course' => $course3->id, 'userid' => $user1->id]);
        $ccompletion->timecompleted = null;
        $ccompletion->mark_complete();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(1, $completions);
        $completion = reset($completions);
        $ccompletion = $DB->get_record('course_completions', ['course' => $course3->id, 'userid' => $user1->id]);
        $this->assertSame($oldid, $completion->id);
        $this->assertSame((string)$field1->get('id'), $completion->fieldid);
        $this->assertSame($course3->id, $completion->instanceid);
        $this->assertSame($user1->id, $completion->userid);
        $this->assertSame($ccompletion->timecompleted, $completion->timecompleted);

        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(3, $completions);

        \tool_mutrain\local\area\core_course_course::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('tool_mutrain_completion', []));
    }

    public function test_observe_course_deleted(): void {
        global $DB;

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2', 'name' => 'F2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3', 'name' => 'F3']
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
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course3->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course4->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(5, $completions);

        delete_course($course1, false);
        $this->assertFalse($DB->record_exists('course', ['id' => $course1->id]));

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(1, $completions);

        \tool_mutrain\local\area\core_course_course::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('tool_mutrain_completion', []));
    }

    public function test_program_course_completions_purged(): void {
        global $DB;

        if (!\tool_mulib\local\mulib::is_muprog_available()) {
            $this->markTestSkipped('tool_muprog not installed');
        }

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2', 'name' => 'F2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3', 'name' => 'F3']
        );

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 10, 'customfield_field2' => 1, 'enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 20, 'enablecompletion' => 1]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 40, 'enablecompletion' => 1]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field3' => 'abc', 'enablecompletion' => 1]);

        $program1 = $programgenerator->create_program();
        $record = [
            'programid' => $program1->id,
            'courseid' => $course1->id,
        ];
        $item1 = $programgenerator->create_program_item($record);
        $record = [
            'programid' => $program1->id,
            'courseid' => $course2->id,
        ];
        $item2 = $programgenerator->create_program_item($record);
        $program2 = $programgenerator->create_program();
        $record = [
            'programid' => $program2->id,
            'courseid' => $course3->id,
        ];
        $item3 = $programgenerator->create_program_item($record);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(0, $completions);

        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course3->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course4->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(5, $completions);

        \tool_muprog\local\course_reset::purge_completions($user1, $program1->id);

        $this->assertCount(3, $DB->get_records('tool_mutrain_completion', []));
        $this->assertCount(1, $DB->get_records(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]
        ));
        $this->assertCount(1, $DB->get_records(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $course1->id, 'userid' => $user2->id]
        ));
        $this->assertCount(1, $DB->get_records(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course3->id, 'userid' => $user1->id]
        ));

        \tool_muprog\local\course_reset::purge_completions($user2, $program1->id);
        $this->assertCount(1, $DB->get_records('tool_mutrain_completion', []));
        $this->assertCount(1, $DB->get_records(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $course3->id, 'userid' => $user1->id]
        ));
    }
}
