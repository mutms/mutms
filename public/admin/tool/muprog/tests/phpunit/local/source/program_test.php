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

use stdClass;

/**
 * Completed program allocation source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\program
 */
final class program_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('program', \tool_muprog\local\source\program::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->assertTrue(\tool_muprog\local\source\program::is_new_allowed($program));
        set_config('source_program_allownew', 0, 'tool_muprog');
        $this->assertFalse(\tool_muprog\local\source\program::is_new_allowed($program));
    }

    public function test_fix_allocations(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $programx = $generator->create_program();
        $allocationx1 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user1->id]);
        $allocationx2 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user2->id]);
        $allocationx3 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user3->id]);

        $programy = $generator->create_program();
        $allocationy4 = $generator->create_program_allocation(['programid' => $programy->id, 'userid' => $user4->id]);

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'program' => ['auxint1' => $programx->id]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1p = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'program'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'program' => ['auxint1' => $programx->id]]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program3 = $generator->create_program(['sources' => ['manual' => [], 'program' => []]]);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id]);

        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(1, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);

        $DB->set_field('tool_muprog_allocation', 'timecompleted', time(), ['id' => $allocationx1->id]);
        $DB->set_field('tool_muprog_allocation', 'timecompleted', time(), ['id' => $allocationx2->id]);
        $DB->set_field('tool_muprog_allocation', 'timecompleted', time(), ['id' => $allocationy4->id]);
        \tool_muprog\local\source\program::fix_allocations($program1->id, null);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1p->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);

        $DB->set_field('tool_muprog_source', 'auxint1', $programy->id, ['id' => $source1p->id]);
        \tool_muprog\local\source\program::fix_allocations($program1->id, null);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(3, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1p->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);
        $this->assertSame($user4->id, $allocations[2]->userid);
        $this->assertSame($source1p->id, $allocations[2]->sourceid);
        $this->assertSame('0', $allocations[2]->archived);

        $DB->set_field('tool_muprog_source', 'auxint1', null, ['id' => $source1p->id]);
        \tool_muprog\local\source\program::fix_allocations($program1->id, null);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(3, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1p->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);
        $this->assertSame($user4->id, $allocations[2]->userid);
        $this->assertSame($source1p->id, $allocations[2]->sourceid);
        $this->assertSame('0', $allocations[2]->archived);
    }

    public function test_observe_allocation_completed(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $programx = $generator->create_program();
        $allocationx1 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user1->id]);
        $allocationx2 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user2->id]);
        $allocationx3 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user3->id]);

        $programy = $generator->create_program();
        $allocationy4 = $generator->create_program_allocation(['programid' => $programy->id, 'userid' => $user4->id]);

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'program' => ['auxint1' => $programx->id]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1p = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'program'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'program' => ['auxint1' => $programx->id]]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program3 = $generator->create_program(['sources' => ['manual' => [], 'program' => []]]);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id]);

        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(1, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);

        $this->complete_allocation($allocationx1);
        $this->complete_allocation($allocationx2);
        $this->complete_allocation($allocationy4);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'userid ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($source1m->id, $allocations[0]->sourceid);
        $this->assertSame('0', $allocations[0]->archived);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($source1p->id, $allocations[1]->sourceid);
        $this->assertSame('0', $allocations[1]->archived);
    }

    public function test_is_import_allowed(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['program' => []]]);
        $program2 = $generator->create_program(['sources' => []]);
        $program3 = $generator->create_program(['sources' => []]);
        $program4 = $generator->create_program(['sources' => ['program' => []]]);

        set_config('source_program_allownew', '1', 'tool_muprog');

        $this->assertFalse(\tool_muprog\local\source\program::is_import_allowed($program1, $program3));
        $this->assertFalse(\tool_muprog\local\source\program::is_import_allowed($program2, $program3));
        $this->assertFalse(\tool_muprog\local\source\program::is_import_allowed($program1, $program4));
        $this->assertFalse(\tool_muprog\local\source\program::is_import_allowed($program2, $program4));
    }

    public function test_is_allocation_archive_possible(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $programx = $generator->create_program();
        $allocationx1 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user1->id]);
        $allocationx2 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user2->id]);

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'program' => ['auxint1' => $programx->id]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1p = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'program'], '*', MUST_EXIST);

        $this->complete_allocation($allocationx1);
        $this->complete_allocation($allocationx2);

        $this->assertTrue(\tool_muprog\local\source\program::is_allocation_archive_possible($program1, $source1p, $allocationx2));
    }

    public function test_is_allocation_restore_possible(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $programx = $generator->create_program();
        $allocationx1 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user1->id]);
        $allocationx2 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user2->id]);

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'program' => ['auxint1' => $programx->id]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1p = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'program'], '*', MUST_EXIST);

        $this->complete_allocation($allocationx1);
        $this->complete_allocation($allocationx2);

        $this->assertTrue(\tool_muprog\local\source\program::is_allocation_restore_possible($program1, $source1p, $allocationx2));
    }

    public function test_is_allocation_delete_possible(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $programx = $generator->create_program();
        $allocationx1 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user1->id]);
        $allocationx2 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user2->id]);
        $allocationx3 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user3->id]);
        $allocationx4 = $generator->create_program_allocation(['programid' => $programx->id, 'userid' => $user4->id]);

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'program' => ['auxint1' => $programx->id]]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1p = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'program'], '*', MUST_EXIST);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id]);

        $allocationx1 = $this->complete_allocation($allocationx1);
        $allocationx2 = $this->complete_allocation($allocationx2);
        $allocationx3 = $this->complete_allocation($allocationx3);
        $allocationx4 = $this->complete_allocation($allocationx4);

        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id], '*', MUST_EXIST);
        $allocation4 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id], '*', MUST_EXIST);

        $allocation3 = $this->archive_allocation($allocation3);

        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation2));
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation3));
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation4));

        $this->delete_allocation($allocationx4);
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation2));
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation3));
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation4));

        $allocation4 = $this->archive_allocation($allocation4);
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation2));
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation3));
        $this->assertTrue(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation4));

        $DB->set_field('tool_muprog_source', 'auxint1', null, ['id' => $source1p->id]);
        $source1p->auxint1 = null;
        $this->assertFalse(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation2));
        $this->assertTrue(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation3));
        $this->assertTrue(\tool_muprog\local\source\program::is_allocation_delete_possible($program1, $source1p, $allocation4));
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
     * Mark given allocation as archived.
     *
     * @param stdClass $allocation
     * @return stdClass
     */
    protected function archive_allocation(stdClass $allocation): stdClass {
        global $DB;

        $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);
        $sourceclass = \tool_muprog\local\allocation::get_source_classname($source->type);

        return $sourceclass::allocation_archive($allocation->id);
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
