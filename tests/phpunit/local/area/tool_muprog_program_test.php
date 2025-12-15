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

use stdClass;

/**
 * Program custom fields test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\local\area\tool_muprog_program
 */
final class tool_muprog_program_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        if (!class_exists(\tool_muprog\local\program::class)) {
            $this->markTestSkipped('tool_muprog not available');
        }

        $this->resetAfterTest();
    }

    public function test_get_category_select(): void {
        global $DB;

        $select = \tool_mutrain\local\area\tool_muprog_program::get_category_select('xx');
        $sql = "SELECT xx.*
                  FROM {customfield_category} xx
                 WHERE $select";
        $this->assertCount(0, $DB->get_records_sql($sql));

        $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'tool_muprog', 'area' => 'program']
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

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setAdminUser();

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'tool_muprog', 'area' => 'program', 'name' => 'Training custom fields']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'Field 1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2', 'name' => 'Field 2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3', 'name' => 'Field 3']
        );

        $program1 = $programgenerator->create_program(['customfield_field1' => 10, 'customfield_field2' => 1]);

        $program2 = $programgenerator->create_program(['customfield_field1' => 20]);
        $program3 = $programgenerator->create_program(['customfield_field1' => 40]);
        $program4 = $programgenerator->create_program(['customfield_field3' => 'abc']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program1->id]);
        $allocation1x2 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program2->id]);
        $allocation1x3 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program3->id]);
        $allocation1x4 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program4->id]);
        $allocation2x1 = $programgenerator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation3x1 = $programgenerator->create_program_allocation(['userid' => $user3->id, 'programid' => $program1->id]);

        $allocation1x1 = $this->complete_allocation($allocation1x1);
        $allocation1x3 = $this->complete_allocation($allocation1x3);
        $allocation1x4 = $this->complete_allocation($allocation1x4);
        $allocation2x1 = $this->complete_allocation($allocation2x1);

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(5, $completions);

        $DB->delete_records('tool_mutrain_completion', []);

        // Add completions.
        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(5, $completions);

        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program1->id, 'userid' => $user1->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $program1->id, 'userid' => $user1->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program3->id, 'userid' => $user1->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program1->id, 'userid' => $user2->id]
        ));
        $this->assertTrue($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $program1->id, 'userid' => $user2->id]
        ));

        // No modifications.
        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('tool_mutrain_completion', [], 'id ASC'));

        // Removing of completions.
        $DB->delete_records('tool_muprog_allocation', ['id' => $allocation1x1->id]);
        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(3, $completions);
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program1->id, 'userid' => $user1->id]
        ));
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $program1->id, 'userid' => $user1->id]
        ));

        // Date sync.
        $DB->set_field('tool_mutrain_completion', 'timecompleted', '1', []);
        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $completions2 = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertEquals($completions, $completions2);

        // Remove pending null completions.
        $DB->set_field('tool_muprog_allocation', 'timecompleted', null, ['id' => $allocation2x1->id]);
        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(1, $completions);
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program1->id, 'userid' => $user2->id]
        ));
        $this->assertFalse($DB->record_exists(
            'tool_mutrain_completion',
            ['fieldid' => $field2->get('id'), 'instanceid' => $program1->id, 'userid' => $user2->id]
        ));

        // Test contextid sync.
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $completion311 = $DB->get_record(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program3->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)$syscontext->id, $completion311->contextid);
        $allocation1x2 = $this->complete_allocation($allocation1x2);
        $completion211 = $DB->get_record(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program2->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)$syscontext->id, $completion211->contextid);

        $DB->set_field('tool_muprog_program', 'contextid', $categorycontext->id, ['id' => $program3->id]);
        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $completion311 = $DB->get_record(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program3->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)$categorycontext->id, $completion311->contextid);
        $completion211 = $DB->get_record(
            'tool_mutrain_completion',
            ['fieldid' => $field1->get('id'), 'instanceid' => $program2->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)$syscontext->id, $completion211->contextid);
    }

    public function test_observe_allocation_completed(): void {
        global $DB;

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setAdminUser();

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'tool_muprog', 'area' => 'program']
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

        $program1 = $programgenerator->create_program(['customfield_field1' => 10, 'customfield_field2' => 1]);
        $program2 = $programgenerator->create_program(['customfield_field1' => 20]);
        $program3 = $programgenerator->create_program(['customfield_field1' => 40]);
        $program4 = $programgenerator->create_program(['customfield_field3' => 'abc']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program1->id]);
        $allocation1x2 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program2->id]);
        $allocation1x3 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program3->id]);
        $allocation1x4 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program4->id]);
        $allocation2x1 = $programgenerator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation3x1 = $programgenerator->create_program_allocation(['userid' => $user3->id, 'programid' => $program1->id]);

        $this->assertCount(5, $DB->get_records('customfield_data', []));
        $this->assertCount(0, $DB->get_records('tool_mutrain_completion', []));

        $allocation1x4 = $this->complete_allocation($allocation1x4);
        $this->assertCount(0, $DB->get_records('tool_mutrain_completion', []));

        $allocation1x3 = $this->complete_allocation($allocation1x3);
        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(1, $completions);
        $completion = reset($completions);
        $this->assertSame((string)$field1->get('id'), $completion->fieldid);
        $this->assertSame($program3->id, $completion->instanceid);
        $this->assertSame($user1->id, $completion->userid);
        $this->assertSame($allocation1x3->timecompleted, $completion->timecompleted);
    }

    public function test_observe_allocation_deleted(): void {
        global $DB;

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setAdminUser();

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'tool_muprog', 'area' => 'program']
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

        $program1 = $programgenerator->create_program(['customfield_field1' => 10, 'customfield_field2' => 1]);
        $program2 = $programgenerator->create_program(['customfield_field1' => 20]);
        $program3 = $programgenerator->create_program(['customfield_field1' => 40]);
        $program4 = $programgenerator->create_program(['customfield_field3' => 'abc']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program1->id]);
        $allocation1x2 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program2->id]);
        $allocation1x3 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program3->id]);
        $allocation1x4 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program4->id]);
        $allocation2x1 = $programgenerator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation3x1 = $programgenerator->create_program_allocation(['userid' => $user3->id, 'programid' => $program1->id]);

        $allocation1x1 = $this->complete_allocation($allocation1x1);
        $allocation2x1 = $this->complete_allocation($allocation2x1);
        $allocation1x3 = $this->complete_allocation($allocation1x3);
        $allocation1x4 = $this->complete_allocation($allocation1x4);

        $this->delete_allocation($allocation1x1);

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(3, $completions);

        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('tool_mutrain_completion', []));
    }

    public function test_observe_program_deleted(): void {
        global $DB;

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setAdminUser();

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'tool_muprog', 'area' => 'program']
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

        $program1 = $programgenerator->create_program(['customfield_field1' => 10, 'customfield_field2' => 1]);
        $program2 = $programgenerator->create_program(['customfield_field1' => 20]);
        $program3 = $programgenerator->create_program(['customfield_field1' => 40]);
        $program4 = $programgenerator->create_program(['customfield_field3' => 'abc']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program1->id]);
        $allocation1x2 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program2->id]);
        $allocation1x3 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program3->id]);
        $allocation1x4 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program4->id]);
        $allocation2x1 = $programgenerator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation3x1 = $programgenerator->create_program_allocation(['userid' => $user3->id, 'programid' => $program1->id]);

        $allocation1x1 = $this->complete_allocation($allocation1x1);
        $allocation2x1 = $this->complete_allocation($allocation2x1);
        $allocation1x3 = $this->complete_allocation($allocation1x3);
        $allocation1x4 = $this->complete_allocation($allocation1x4);

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(5, $completions);

        \tool_muprog\local\program::delete($program1->id);
        $this->assertFalse($DB->record_exists('tool_muprog_program', ['id' => $program1->id]));

        $completions = $DB->get_records('tool_mutrain_completion', [], 'id ASC');
        $this->assertCount(1, $completions);

        \tool_mutrain\local\area\tool_muprog_program::sync_area_completions();
        $this->assertEquals($completions, $DB->get_records('tool_mutrain_completion', []));
    }

    /**
     * Mark given allocation as completed.
     *
     * @param stdClass $allocation
     * @param int|null $timecompleted
     * @return stdClass
     */
    protected function complete_allocation(stdClass $allocation, ?int $timecompleted = null): stdClass {
        global $DB;

        $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);
        $sourceclass = \tool_muprog\local\allocation::get_source_classname($source->type);

        $allocation->timecompleted = $timecompleted ?? time();

        return $sourceclass::allocation_update($allocation);
    }

    /**
     * Mark given allocation as completed.
     *
     * @param stdClass $allocation
     */
    protected function delete_allocation(stdClass $allocation): void {
        global $DB;

        $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
        $sourceclass = \tool_muprog\local\allocation::get_source_classname($source->type);

        $sourceclass::allocation_delete($program, $source, $allocation, true);
    }
}
