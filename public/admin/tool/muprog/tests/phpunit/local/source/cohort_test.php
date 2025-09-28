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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_muprog\phpunit\local\source;

use tool_muprog\local\program;

/**
 * Visible cohort allocation source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\cohort
 */
final class cohort_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('cohort', \tool_muprog\local\source\cohort::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->assertTrue(\tool_muprog\local\source\cohort::is_new_allowed($program));
        \set_config('source_cohort_allownew', 0, 'tool_muprog');
        $this->assertFalse(\tool_muprog\local\source\cohort::is_new_allowed($program));
    }

    public function test_allocations_ignore_visibility(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'cohort' => ['cohortids' => [$cohort1->id]]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1c = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'cohort'], '*', MUST_EXIST);

        \cohort_add_member($cohort1->id, $user1->id);
        \cohort_add_member($cohort2->id, $user1->id);
        \cohort_add_member($cohort2->id, $user2->id);
        $program1 = program::update_visibility(
            (object)['id' => $program1->id, 'publicaccess' => 1, 'cohortids' => [$cohort1->id, $cohort2->id]]
        );
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(1, $allocations);

        $program1 = program::update_visibility(
            (object)['id' => $program1->id, 'publicaccess' => 1, 'cohortids' => []]
        );
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(1, $allocations);
    }

    public function test_fetch_allocation_cohorts_menu(): void {
        global $DB;
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort A']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort B']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort C']);

        $program1 = $generator->create_program(['sources' => ['cohort' => ['cohortids' => [$cohort1->id, $cohort2->id]]]]);
        $program2 = $generator->create_program(['sources' => ['cohort' => ['cohortids' => [$cohort1->id]]]]);
        $program3 = $generator->create_program();

        $source1id = $DB->get_field('tool_muprog_source', 'id', ['programid' => $program1->id, 'type' => 'cohort']);
        $source2id = $DB->get_field('tool_muprog_source', 'id', ['programid' => $program2->id, 'type' => 'cohort']);
        $source3id = $DB->get_field('tool_muprog_source', 'id', ['programid' => $program3->id, 'type' => 'cohort']);

        $expected = [
            $cohort1->id => $cohort1->name,
            $cohort2->id => $cohort2->name,
        ];
        $menu = \tool_muprog\local\source\cohort::fetch_allocation_cohorts_menu($source1id);
        $this->assertSame($expected, $menu);

        $expected = [
            $cohort1->id => $cohort1->name,
        ];
        $menu = \tool_muprog\local\source\cohort::fetch_allocation_cohorts_menu($source2id);
        $this->assertSame($expected, $menu);

        $menu = \tool_muprog\local\source\cohort::fetch_allocation_cohorts_menu($source3id);
        $this->assertSame([], $menu);
    }

    public function test_allocations(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'cohort' => [$cohort1->id]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1c = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'cohort'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        \cohort_add_member($cohort1->id, $user1->id);
        \cohort_add_member($cohort2->id, $user1->id);
        \cohort_add_member($cohort2->id, $user2->id);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id]);

        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(1, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);

        $record = (object)['sourceid' => $source1c->id, 'cohortid' => $cohort2->id];
        $DB->insert_record('tool_muprog_src_cohort', $record);
        \tool_muprog\local\source\cohort::fix_allocations($program1->id, null);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);

        $DB->delete_records('tool_muprog_src_cohort', ['sourceid' => $source1c->id]);
        \tool_muprog\local\source\cohort::fix_allocations($program1->id, null);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('1', $allocations[1]->archived);

        $record = (object)['sourceid' => $source1c->id, 'cohortid' => $cohort1->id];
        $DB->insert_record('tool_muprog_src_cohort', $record);
        $record = (object)['sourceid' => $source1c->id, 'cohortid' => $cohort2->id];
        $DB->insert_record('tool_muprog_src_cohort', $record);
        \tool_muprog\local\source\cohort::fix_allocations($program1->id, null);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);

        // Cohort membership changes.

        \cohort_add_member($cohort2->id, $user3->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(3, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);
        $this->assertSame($user3->id, $allocations[2]->userid);
        $this->assertSame($source1c->id, $allocations[2]->sourceid);
        $this->assertSame('0', $allocations[2]->archived);

        \cohort_remove_member($cohort2->id, $user3->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(3, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);
        $this->assertSame($user3->id, $allocations[2]->userid);
        $this->assertSame($source1c->id, $allocations[2]->sourceid);
        $this->assertSame('1', $allocations[2]->archived);

        // Freezing of archived program.

        $program1 = program::archive($program1->id);

        \cohort_remove_member($cohort2->id, $user2->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(3, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);
        $this->assertSame($user3->id, $allocations[2]->userid);
        $this->assertSame($source1c->id, $allocations[2]->sourceid);
        $this->assertSame('1', $allocations[2]->archived);

        $DB->delete_records('tool_muprog_src_cohort', ['sourceid' => $source1c->id]);
        \tool_muprog\local\source\cohort::fix_allocations($program1->id, null);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(3, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);
        $this->assertSame($user3->id, $allocations[2]->userid);
        $this->assertSame($source1c->id, $allocations[2]->sourceid);
        $this->assertSame('1', $allocations[2]->archived);

        \tool_muprog\local\source\manual::allocation_delete($program1, $source1m, $allocations[0]);

        $program1 = program::restore($program1->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user2->id, $allocations[0]->userid);
        $this->assertSame($source1c->id, $allocations[0]->sourceid);
        $this->assertSame('1', $allocations[0]->archived);
        $this->assertSame($user3->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('1', $allocations[1]->archived);

        // Check there are no SQL syntax errors with different parameters.
        \tool_muprog\local\source\cohort::fix_allocations(null, null);
        \tool_muprog\local\source\cohort::fix_allocations($program1->id, null);
        \tool_muprog\local\source\cohort::fix_allocations($program1->id, $user1->id);
        \tool_muprog\local\source\cohort::fix_allocations(null, $user1->id);

        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user2->id, $allocations[0]->userid);
        $this->assertSame($source1c->id, $allocations[0]->sourceid);
        $this->assertSame('1', $allocations[0]->archived);
        $this->assertSame($user3->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('1', $allocations[1]->archived);
    }

    /**
     * @return void
     *
     * @covers \tool_muprog\local\event_observer::cohort_member_added()
     * @covers \tool_muprog\local\event_observer::cohort_member_removed()
     */
    public function test_cohort_observers(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'cohort' => ['cohortids' => [$cohort1->id, $cohort2->id]]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1c = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'cohort'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'cohort' => ['cohortids' => [$cohort1->id]]], 'archived' => 1]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2c = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'cohort'], '*', MUST_EXIST);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id]);
        \cohort_add_member($cohort3->id, $user1->id);

        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(1, $allocations);
        $allocations = array_values($allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);

        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program2->id], 'userid ASC');
        $this->assertCount(0, $allocations);

        \cohort_add_member($cohort1->id, $user2->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(2, $allocations);
        $allocations = array_values($allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);

        \cohort_add_member($cohort2->id, $user2->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(2, $allocations);
        $allocations = array_values($allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);

        \cohort_remove_member($cohort2->id, $user2->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(2, $allocations);
        $allocations = array_values($allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);

        \cohort_remove_member($cohort1->id, $user2->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(2, $allocations);
        $allocations = array_values($allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('1', $allocations[1]->archived);

        \cohort_remove_member($cohort1->id, $user1->id);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $this->assertCount(2, $allocations);
        $allocations = array_values($allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1c->id, $allocations[1]->sourceid);
        $this->assertSame('1', $allocations[1]->archived);
    }

    public function test_is_import_allowed(): void {

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['cohort' => []]]);
        $program2 = $generator->create_program(['sources' => []]);
        $program3 = $generator->create_program(['sources' => []]);
        $program4 = $generator->create_program(['sources' => ['cohort' => []]]);

        \set_config('source_cohort_allownew', '1', 'tool_muprog');

        $this->assertTrue(\tool_muprog\local\source\cohort::is_import_allowed($program1, $program3));
        $this->assertFalse(\tool_muprog\local\source\cohort::is_import_allowed($program2, $program3));
        $this->assertTrue(\tool_muprog\local\source\cohort::is_import_allowed($program1, $program4));
        $this->assertFalse(\tool_muprog\local\source\cohort::is_import_allowed($program2, $program4));

        \set_config('source_cohort_allownew', '0', 'tool_muprog');

        $this->assertFalse(\tool_muprog\local\source\cohort::is_import_allowed($program1, $program3));
        $this->assertFalse(\tool_muprog\local\source\cohort::is_import_allowed($program2, $program3));
        $this->assertTrue(\tool_muprog\local\source\cohort::is_import_allowed($program1, $program4));
        $this->assertFalse(\tool_muprog\local\source\cohort::is_import_allowed($program2, $program4));
    }

    public function test_import_source_data(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program(['sources' => ['cohort' => ['cohortids' => [$cohort1->id]]]]);
        $program2 = $generator->create_program(['sources' => ['cohort' => ['cohortids' => [$cohort2->id, $cohort3->id]]]]);
        $program3 = $generator->create_program(['sources' => []]);

        $sql = "SELECT c.cohortid
                  FROM {tool_muprog_src_cohort} c
                  JOIN {tool_muprog_source} s ON s.id = c.sourceid AND s.type = 'cohort'
                 WHERE s.programid = ?
              ORDER BY c.cohortid ASC";

        $cohortids = $DB->get_fieldset_sql($sql, [$program3->id]);
        $this->assertSame([], $cohortids);

        $source3 = \tool_muprog\local\source\cohort::import_source_data($program1->id, $program3->id);
        $cohortids = $DB->get_fieldset_sql($sql, [$program3->id]);
        $this->assertSame([$cohort1->id], $cohortids);
        $this->assertSame($program3->id, $source3->programid);
        $this->assertSame('cohort', $source3->type);

        \tool_muprog\local\source\cohort::import_source_data($program2->id, $program3->id);
        $cohortids = $DB->get_fieldset_sql($sql, [$program3->id]);
        $this->assertSame([$cohort1->id, $cohort2->id, $cohort3->id], $cohortids);
    }
}
