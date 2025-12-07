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

namespace tool_mucertify\phpunit\local\source;

use tool_mucertify\local\source\cohort;

/**
 * Certification cohort assignment source test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\source\cohort
 */
final class cohort_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('cohort', cohort::get_type());
    }

    public function test_get_name(): void {
        $this->assertSame('Automatic cohort assignment', cohort::get_name());
    }

    public function test_is_new_allowed(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        $certification = $generator->create_certification();

        $this->assertTrue(cohort::is_new_allowed($certification));
        set_config('source_cohort_allownew', 0, 'tool_mucertify');
        $this->assertFalse(cohort::is_new_allowed($certification));
    }

    public function test_is_new_allowed_in_new(): void {
        $this->assertFalse(cohort::is_new_allowed_in_new());
    }

    public function test_is_update_allowed(): void {
        $certification = new \stdClass();
        $this->assertSame(true, cohort::is_update_allowed($certification));
    }

    public function test_fix_assignments(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort3->id, $user1->id);

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id, $cohort2->id]]],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort2->id]]],
            'programid1' => $program2->id,
        ];
        $certification2 = $generator->create_certification($data);
        $assignment11 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $assignment12 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $assignment21 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $assignment22 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification1->id]));
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification2->id]));

        // Use low level DB edits to prevent cohort events interfering with tests.

        $DB->delete_records('tool_mucertify_assignment', ['id' => $assignment11->id]);
        cohort::fix_assignments($certification1->id, $user2->id);
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id]));

        cohort::fix_assignments($certification2->id, $user1->id);
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id]));

        cohort::fix_assignments(null, $user2->id);
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id]));

        cohort::fix_assignments($certification2->id, null);
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id]));

        cohort::fix_assignments($certification1->id, $user1->id);
        $assignment11 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $assignment12 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $assignment21 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $assignment22 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);

        $DB->set_field('tool_mucertify_assignment', 'archived', 1, ['id' => $assignment11->id]);
        cohort::fix_assignments($certification1->id, $user1->id);
        $assignment11 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment11->archived);
        $assignment12 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment12->archived);
        $assignment21 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment21->archived);
        $assignment22 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment22->archived);

        $DB->delete_records('cohort_members', ['cohortid' => $cohort2->id]);
        cohort::fix_assignments(null, null);
        $assignment11 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment11->archived);
        $assignment12 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment12->archived);
        $assignment21 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $this->assertSame('1', $assignment21->archived);
        $assignment22 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $this->assertSame('1', $assignment22->archived);
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification1->id]));
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification2->id]));

        $DB->insert_record('cohort_members', ['cohortid' => $cohort1->id, 'userid' => $user3->id]);
        cohort::fix_assignments(null, null);
        $assignment11 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment11->archived);
        $assignment12 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment12->archived);
        $assignment13 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification1->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment13->archived);
        $assignment21 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $this->assertSame('1', $assignment21->archived);
        $assignment22 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification2->id], '*', MUST_EXIST);
        $this->assertSame('1', $assignment22->archived);
        $this->assertFalse($DB->record_exists('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification2->id]));
    }

    public function test_is_assignment_update_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id]]],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        cohort_remove_member($cohort1->id, $user2->id);
        cohort::fix_assignments(null, null);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment1->archived);
        $this->assertSame('1', $assignment2->archived);

        $this->assertTrue(cohort::is_assignment_update_possible($certification, $source, $assignment1));
        $this->assertFalse(cohort::is_assignment_update_possible($certification, $source, $assignment2));

        $certification = \tool_mucertify\local\certification::archive($certification->id);
        $this->assertFalse(cohort::is_assignment_update_possible($certification, $source, $assignment1));
        $this->assertFalse(cohort::is_assignment_update_possible($certification, $source, $assignment2));
    }

    public function test_is_assignment_archive_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id]]],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        cohort_remove_member($cohort1->id, $user2->id);
        cohort::fix_assignments(null, null);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment1->archived);
        $this->assertSame('1', $assignment2->archived);

        $this->assertFalse(cohort::is_assignment_archive_possible($certification, $source, $assignment1));
        $this->assertFalse(cohort::is_assignment_archive_possible($certification, $source, $assignment2));

        $certification = \tool_mucertify\local\certification::archive($certification->id);
        $this->assertFalse(cohort::is_assignment_archive_possible($certification, $source, $assignment1));
        $this->assertFalse(cohort::is_assignment_archive_possible($certification, $source, $assignment2));
    }

    public function test_is_assignment_restore_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id]]],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        cohort_remove_member($cohort1->id, $user2->id);
        cohort::fix_assignments(null, null);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment1->archived);
        $this->assertSame('1', $assignment2->archived);

        $this->assertFalse(cohort::is_assignment_restore_possible($certification, $source, $assignment1));
        $this->assertFalse(cohort::is_assignment_restore_possible($certification, $source, $assignment2));

        $certification = \tool_mucertify\local\certification::archive($certification->id);
        $this->assertFalse(cohort::is_assignment_restore_possible($certification, $source, $assignment1));
        $this->assertFalse(cohort::is_assignment_restore_possible($certification, $source, $assignment2));
    }

    public function test_is_assignment_delete_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id]]],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        cohort_remove_member($cohort1->id, $user2->id);
        cohort::fix_assignments(null, null);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment1->archived);
        $this->assertSame('1', $assignment2->archived);

        $this->assertFalse(cohort::is_assignment_delete_possible($certification, $source, $assignment1));
        $this->assertTrue(cohort::is_assignment_delete_possible($certification, $source, $assignment2));

        $certification = \tool_mucertify\local\certification::archive($certification->id);
        $this->assertFalse(cohort::is_assignment_delete_possible($certification, $source, $assignment1));
        $this->assertFalse(cohort::is_assignment_delete_possible($certification, $source, $assignment2));
    }

    public function test_get_catalogue_actions(): void {
        $certification = new \stdClass();
        $source = new \stdClass();
        $this->assertSame([], cohort::get_catalogue_actions($certification, $source));
    }

    public function test_decode_datajson(): void {
        $source = new \stdClass();
        $this->assertSame($source, cohort::decode_datajson($source));
    }

    public function test_encode_datajson(): void {
        $formdata = new \stdClass();
        $this->assertSame('[]', cohort::encode_datajson($formdata));
    }

    public function test_add_management_certification_users_actions(): void {
        $certification = new \stdClass();
        $source = new \stdClass();

        $actions = new \tool_mulib\output\header_actions('xyz');
        cohort::add_management_certification_users_actions($actions, $certification, $source);
        $this->assertFalse($actions->has_items());
    }

    public function test_update_source(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $cohort4 = $this->getDataGenerator()->create_cohort();

        $data = [
            'sources' => ['cohort' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $data = [
            'certificationid' => $certification->id,
            'type' => 'cohort',
            'enable' => 0,
        ];
        $source = cohort::update_source((object)$data);
        $this->assertSame(null, $source);

        $data = [
            'certificationid' => $certification->id,
            'type' => 'cohort',
            'enable' => 1,
            'cohortids' => [],
        ];
        $source = cohort::update_source((object)$data);
        $this->assertSame($certification->id, $source->certificationid);
        $this->assertSame('cohort', $source->type);

        $data = [
            'certificationid' => $certification->id,
            'type' => 'cohort',
            'enable' => 1,
            'cohortids' => [$cohort1->id, $cohort3->id],
        ];
        $source = cohort::update_source((object)$data);
        $this->assertSame($certification->id, $source->certificationid);
        $this->assertSame('cohort', $source->type);
        $cohorts = $DB->get_records_menu('tool_mucertify_src_cohort', ['sourceid' => $source->id], '', 'cohortid, id');
        $this->assertCount(2, $cohorts);
        $this->assertArrayHasKey($cohort1->id, $cohorts);
        $this->assertArrayHasKey($cohort3->id, $cohorts);

        $data = [
            'certificationid' => $certification->id,
            'type' => 'cohort',
            'enable' => 1,
            'cohortids' => [$cohort2->id, $cohort3->id],
        ];
        $source = cohort::update_source((object)$data);
        $this->assertSame($certification->id, $source->certificationid);
        $this->assertSame('cohort', $source->type);
        $cohorts = $DB->get_records_menu('tool_mucertify_src_cohort', ['sourceid' => $source->id], '', 'cohortid, id');
        $this->assertCount(2, $cohorts);
        $this->assertArrayHasKey($cohort2->id, $cohorts);
        $this->assertArrayHasKey($cohort3->id, $cohorts);

        $data = [
            'certificationid' => $certification->id,
            'type' => 'cohort',
            'enable' => 0,
            'cohortids' => [$cohort2->id, $cohort3->id],
        ];
        $source = cohort::update_source((object)$data);
        $this->assertSame(null, $source);
        $cohorts = $DB->get_records_menu('tool_mucertify_src_cohort', [], '', 'cohortid, id');
        $this->assertCount(0, $cohorts);
    }

    public function test_unassign_user(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id, $cohort2->id]]],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertCount(2, $DB->get_records('tool_mucertify_period', ['certificationid' => $certification->id]));

        cohort::assignment_delete($certification, $source, $assignment1);
        $this->assertCount(0, $DB->get_records('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id]));
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id]));
    }

    public function test_render_status_details(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $data = [
            'sources' => [],
            ];
        $certification = $generator->create_certification($data);
        $this->assertSame('Inactive', cohort::render_status_details($certification, null));

        $data = [
            'sources' => ['cohort' => ['cohortids' => []]],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('Active', cohort::render_status_details($certification, $source));

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id, $cohort2->id]]],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('Active (Cohort 1, Cohort 2)', cohort::render_status_details($certification, $source));
    }

    public function test_render_status(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mucertify:edit', CAP_ALLOW, $editorroleid, $syscontext);
        \role_assign($editorroleid, $user1->id, $catcontext->id);

        $data = [
            'contextid' => $catcontext->id,
            'sources' => ['cohort' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->setUser($user2);
        $this->assertSame('Active', cohort::render_status($certification, $source));
        $this->assertSame('Inactive', cohort::render_status($certification, null));

        $this->setUser($user1);
        $this->assertStringStartsWith('Active', cohort::render_status($certification, $source));
        $this->assertStringContainsString('"Update Automatic cohort assignment"', cohort::render_status($certification, $source));
        $this->assertStringStartsWith('Inactive', cohort::render_status($certification, null));
        $this->assertStringContainsString('"Update Automatic cohort assignment"', cohort::render_status($certification, null));
    }

    public function test_get_assigner(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);

        $data = [
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id]]],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'cohort', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->setUser(null);
        $result = cohort::get_assigner($certification, $source, $assignment);
        $this->assertSame($admin->id, $result->id);

        $this->setUser($user2);
        $result = cohort::get_assigner($certification, $source, $assignment);
        $this->assertSame($admin->id, $result->id);

        $this->setGuestUser();
        $result = cohort::get_assigner($certification, $source, $assignment);
        $this->assertSame($admin->id, $result->id);
    }
}
