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
use tool_mucertify\local\certification;
use tool_mucertify\local\period;
use tool_mucertify\local\source\manual;
use tool_muprog\local\course_reset;
use tool_muprog\local\program;

/**
 * Certifications program source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\mucertify
 */
final class mucertify_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        if (!get_config('tool_mucertify', 'version')) {
            $this->markTestSkipped('tool_mucertify not available');
        }
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('mucertify', \tool_muprog\local\source\mucertify::get_type());
    }

    public function test_is_new_allowed(): void {
        $program = new stdClass();
        $this->assertSame(true, \tool_muprog\local\source\mucertify::is_new_allowed($program));
    }

    public function test_is_update_allowed(): void {
        $program = new stdClass();
        $this->assertSame(true, \tool_muprog\local\source\mucertify::is_update_allowed($program));
    }

    public function test_is_allocation_update_possible(): void {
        $program = new stdClass();
        $source = new stdClass();
        $allocation = new stdClass();
        $this->assertSame(false, \tool_muprog\local\source\mucertify::is_allocation_update_possible($program, $source, $allocation));
    }

    public function test_is_allocation_delete_possible(): void {
        $now = time();
        $program = new stdClass();
        $source = new stdClass();
        $allocation = new stdClass();
        $allocation->archived = '0';
        $allocation->timeend = null;
        $this->assertSame(false, \tool_muprog\local\source\mucertify::is_allocation_delete_possible($program, $source, $allocation));

        $allocation->timeend = $now + 100;
        $this->assertSame(false, \tool_muprog\local\source\mucertify::is_allocation_delete_possible($program, $source, $allocation));

        $allocation->timeend = $now - 100;
        $this->assertSame(true, \tool_muprog\local\source\mucertify::is_allocation_delete_possible($program, $source, $allocation));

        $allocation->timeend = null;
        $allocation->archived = '1';
        $this->assertSame(true, \tool_muprog\local\source\mucertify::is_allocation_delete_possible($program, $source, $allocation));
    }

    public function test_render_status_details(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $catcontext->id);

        $this->setUser($user2);

        $program1 = $programgenerator->create_program();
        $source1 = null;
        $this->assertSame('Inactive', \tool_muprog\local\source\mucertify::render_status_details($program1, $source1));

        $program2 = $programgenerator->create_program(['sources' => 'mucertify']);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $this->assertSame('Active', \tool_muprog\local\source\mucertify::render_status_details($program2, $source2));

        $program3 = $programgenerator->create_program(['sources' => 'mucertify']);

        $certification1 = $generator->create_certification([
            'programid1' => $program2->id,
            'contextid' => $catcontext->id,
        ]);
        $this->assertSame('Active - Certification 1', \tool_muprog\local\source\mucertify::render_status_details($program2, $source2));

        $certification2 = $generator->create_certification([
            'programid1' => $program1->id,
            'contextid' => $syscontext->id,
        ]);
        $this->assertSame('Active - Certification 1', \tool_muprog\local\source\mucertify::render_status_details($program2, $source2));

        $certification3 = $generator->create_certification([
            'programid1' => $program1->id,
            'recertify' => DAYSECS,
            'programid2' => $program2->id,
        ]);
        $this->assertSame('Active - Certification 1, Certification 3', \tool_muprog\local\source\mucertify::render_status_details($program2, $source2));

        $this->setUser($user1);
        $result = \tool_muprog\local\source\mucertify::render_status_details($program2, $source2);
        $this->assertNotSame('Active - Certification 1, Certification 3', $result);
        $this->assertStringContainsString('Active', $result);
        $this->assertStringContainsString('>Certification 1<', $result);
        $this->assertStringContainsString('Certification 3', $result);
    }

    public function test_sync_certifications_allocate(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 1]);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);
        $top1 = program::load_content($program1->id);
        $program2 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 1]);
        $program2source = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $top2 = program::load_content($program2->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program2->id,
        ];
        $certification2 = $generator->create_certification($data);
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();
        $user9 = $this->getDataGenerator()->create_user();

        $now = time();

        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user2->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => $now - DAYSECS,
            'timewindowend' => $now + DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user3->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => $now - DAYSECS * 2,
            'timewindowend' => $now - DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user4->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $assignment4 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user4->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $assignment4 = \tool_mucertify\local\source\base::assignment_archive($assignment4->id);

        manual::assign_users($certification1->id, $source1->id, [$user5->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $period5 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user5->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period5 = period::override_dates((object)['id' => $period5->id, 'timerevoked' => $now]);

        manual::assign_users($certification1->id, $source1->id, [$user6->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now + DAYSECS,
        ]);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user6->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period6 = period::override_dates((object)['id' => $period6->id, 'timecertified' => $now]);

        manual::assign_users($certification1->id, $source1->id, [$user7->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now + DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user8->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now - DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user9->id], [
            'timewindowstart' => $now + DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);

        $this->assertCount(0, $DB->get_records('tool_muprog_allocation'));

        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(0, $DB->get_records('tool_muprog_allocation'));

        \tool_muprog\local\source\mucertify::sync_certifications($certification1->id, null);
        $this->assertCount(0, $DB->get_records('tool_muprog_allocation'));

        \tool_muprog\local\source\mucertify::sync_certifications($certification1->id, $user1->id);
        $this->assertCount(0, $DB->get_records('tool_muprog_allocation'));

        \tool_muprog\local\source\mucertify::sync_certifications(null, $user1->id);
        $this->assertCount(0, $DB->get_records('tool_muprog_allocation'));

        $DB->set_field('tool_muprog_program', 'archived', '0', []);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(3, $DB->get_records('tool_muprog_allocation'));
        $allocation1 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation1->archived);
        $this->assertSame((string)($now - DAYSECS), $allocation1->timestart);
        $this->assertSame(null, $allocation1->timedue);
        $this->assertSame(null, $allocation1->timeend);
        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation2->archived);
        $this->assertSame((string)($now - WEEKSECS), $allocation2->timestart);
        $this->assertSame((string)($now - DAYSECS), $allocation2->timedue);
        $this->assertSame((string)($now + DAYSECS), $allocation2->timeend);
        $allocation7 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user7->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation7->archived);
        $this->assertSame((string)($now - WEEKSECS), $allocation7->timestart);
        $this->assertSame(null, $allocation7->timedue);
        $this->assertSame(null, $allocation7->timeend);

        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation1->id, $period1->allocationid);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation2->id, $period2->allocationid);
        $period3 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user3->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period3->allocationid);
        $period4 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user4->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period4->allocationid);
        $period5 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user5->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period5->allocationid);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user6->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period6->allocationid);
        $period7 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user7->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation7->id, $period7->allocationid);
        $period8 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user8->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period8->allocationid);
    }

    /**
     * Test graceful handling of certification allocation colliding with pre-existing manual allocation.
     */
    public function test_sync_certifications_allocate_conflict_noreset(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => ['mucertify' => [], 'manual' => []]]);
        $top1 = program::load_content($program1->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $data = [
            'id' => (string)$certification1->id,
            'resettype1' => course_reset::RESETTYPE_NONE,
        ];
        $certification1 = certification::update_settings((object)$data);
        $settings = \tool_mucertify\local\certification::get_periods_settings($certification1);
        $this->assertSame(course_reset::RESETTYPE_NONE, $settings->resettype1);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $allocation1 = $programgenerator->create_program_allocation(['programid' => $program1->id, 'userid' => $user1->id]);
        $allocation2 = $programgenerator->create_program_allocation(['programid' => $program1->id, 'userid' => $user2->id]);

        $now = time();

        $data = (object)[
            'allocationid' => $allocation1->id,
            'itemid' => $top1->get_id(),
            'timecompleted' => $now,
        ];
        \tool_muprog\local\allocation::update_item_completion($data);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['id' => $allocation1->id], '*', MUST_EXIST);
        $this->assertNotNull($allocation1->timecompleted);

        $this->setCurrentTimeStart();
        $assignment1 = $generator->create_certification_assignment([
            'certificationid' => $certification1->id,
            'userid' => $user1->id,
        ]);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('0', $period1->allocationid);
        $this->assertTimeCurrent($period1->timecertified);

        $assignment2 = $generator->create_certification_assignment([
            'certificationid' => $certification1->id,
            'userid' => $user2->id,
        ]);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('0', $period2->allocationid);
        $this->assertSame(null, $period2->timecertified);

        $allocation1x = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertEquals($allocation1, $allocation1x);
        $allocation2x = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertEquals($allocation2, $allocation2x);

        $this->setCurrentTimeStart();
        $data = (object)[
            'allocationid' => $allocation2->id,
            'itemid' => $top1->get_id(),
            'timecompleted' => $now,
        ];
        \tool_muprog\local\allocation::update_item_completion($data);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('0', $period2->allocationid);
        $this->assertTimeCurrent($period2->timecertified);
    }

    public function test_sync_certifications_archive(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => ['mucertify' => [], 'manual' => []], 'archived' => 0]);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);
        $program1sourcemanual = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual']);
        $top1 = program::load_content($program1->id);
        $program2 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program2source = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $top2 = program::load_content($program2->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program2->id,
        ];
        $certification2 = $generator->create_certification($data);
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();
        $user9 = $this->getDataGenerator()->create_user();

        $now = time();

        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user2->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user3->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user4->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user5->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user6->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $program1sourcemanual->id, [$user7->id]);
        manual::assign_users($certification1->id, $source1->id, [$user7->id], [
            'timewindowstart' => $now + DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $assignment3 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user3->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $allocation3 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user3->id], '*', MUST_EXIST);
        $period3 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user3->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $allocation4 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user4->id], '*', MUST_EXIST);
        $period4 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user4->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $allocation5 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user5->id], '*', MUST_EXIST);
        $period5 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user5->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $allocation6 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user6->id], '*', MUST_EXIST);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user6->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertCount(6, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 0]
        ));
        $allocation7 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1sourcemanual->id, 'userid' => $user7->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation7->archived);

        $allocations = $DB->get_records('tool_muprog_allocation', [], 'id ASC');
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertEquals($allocations, $DB->get_records('tool_muprog_allocation', [], 'id ASC'));

        $period2->timewindowend = (string)($now - 10);
        $DB->update_record('tool_mucertify_period', $period2);

        $assignment3->archived = '1';
        $DB->update_record('tool_mucertify_assignment', $assignment3);

        $DB->delete_records('tool_mucertify_period', ['id' => $period4->id]);

        $period5->timefrom = (string)($now - 1000);
        $period5->timeuntil = (string)($now - 10);
        $DB->update_record('tool_mucertify_period', $period5);

        $period6->timerevoked = (string)$now;
        $DB->update_record('tool_mucertify_period', $period6);

        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(1, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 0]
        ));
        $this->assertCount(4, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 1]
        ));
        $this->assertFalse($DB->record_exists(
            'tool_muprog_allocation',
            ['id' => $period4->allocationid]
        ));

        $allocations = $DB->get_records('tool_muprog_allocation', [], 'id ASC');
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertEquals($allocations, $DB->get_records('tool_muprog_allocation', [], 'id ASC'));

        $certification1->archived = '1';
        $DB->update_record('tool_mucertify_certification', $certification1);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(0, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 0]
        ));
        $this->assertCount(5, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 1]
        ));

        $allocations = $DB->get_records('tool_muprog_allocation', [], 'id ASC');
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertEquals($allocations, $DB->get_records('tool_muprog_allocation', [], 'id ASC'));

        $DB->delete_records('tool_mucertify_assignment', ['id' => $assignment3->id]);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(0, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 0]
        ));
        $this->assertCount(4, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 1]
        ));

        $allocations = $DB->get_records('tool_muprog_allocation', [], 'id ASC');
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertEquals($allocations, $DB->get_records('tool_muprog_allocation', [], 'id ASC'));

        $DB->delete_records('tool_mucertify_certification', ['id' => $certification1->id]);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(0, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 0]
        ));
        $this->assertCount(0, $DB->get_records(
            'tool_muprog_allocation',
            ['sourceid' => $program1source->id, 'archived' => 1]
        ));
    }

    public function test_sync_certifications_restore(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);
        $top1 = program::load_content($program1->id);
        $program2 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program2source = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $top2 = program::load_content($program2->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program2->id,
        ];
        $certification2 = $generator->create_certification($data);
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();
        $user9 = $this->getDataGenerator()->create_user();

        $now = time();

        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user2->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => $now - DAYSECS,
            'timewindowend' => $now + DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user3->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => $now - DAYSECS * 2,
            'timewindowend' => $now - DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user4->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $assignment4 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user4->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $assignment4 = \tool_mucertify\local\source\base::assignment_archive($assignment4->id);

        manual::assign_users($certification1->id, $source1->id, [$user5->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $period5 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user5->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period5 = period::override_dates((object)['id' => $period5->id, 'timerevoked' => $now]);

        manual::assign_users($certification1->id, $source1->id, [$user6->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now + DAYSECS,
        ]);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user6->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period6 = period::override_dates((object)['id' => $period6->id, 'timecertified' => $now]);

        manual::assign_users($certification1->id, $source1->id, [$user7->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now + DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user8->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now - DAYSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user9->id], [
            'timewindowstart' => $now + DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);

        $this->assertCount(6, $DB->get_records('tool_muprog_allocation'));

        $DB->set_field('tool_muprog_allocation', 'archived', '1', []);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['archived' => 1]));
        $this->assertCount(4, $DB->get_records('tool_muprog_allocation', ['archived' => 0]));
        $allocation1 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation1->archived);
        $this->assertSame((string)($now - DAYSECS), $allocation1->timestart);
        $this->assertSame(null, $allocation1->timedue);
        $this->assertSame(null, $allocation1->timeend);
        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation2->archived);
        $this->assertSame((string)($now - WEEKSECS), $allocation2->timestart);
        $this->assertSame((string)($now - DAYSECS), $allocation2->timedue);
        $this->assertSame((string)($now + DAYSECS), $allocation2->timeend);
        $allocation4 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user4->id], '*', MUST_EXIST);
        $this->assertSame('1', $allocation4->archived);
        $allocation5 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user5->id], '*', MUST_EXIST);
        $this->assertSame('1', $allocation5->archived);
        $allocation6 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user6->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation6->archived);
        $allocation7 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user7->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation7->archived);
        $this->assertSame((string)($now - WEEKSECS), $allocation7->timestart);
        $this->assertSame(null, $allocation7->timedue);
        $this->assertSame(null, $allocation7->timeend);

        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation1->id, $period1->allocationid);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation2->id, $period2->allocationid);
        $period3 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user3->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period3->allocationid);
        $period4 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user4->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation4->id, $period4->allocationid);
        $period5 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user5->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation5->id, $period5->allocationid);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user6->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation6->id, $period6->allocationid);
        $period7 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user7->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($allocation7->id, $period7->allocationid);
        $period8 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user8->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period8->allocationid);
    }

    public function test_sync_certifications_update(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $now = time();

        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user2->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => $now + DAYSECS,
            'timewindowend' => $now + WEEKSECS,
        ]);

        $allocation1 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );

        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );

        $allocation1->timedue = (string)($now + DAYSECS);
        $DB->update_record('tool_muprog_allocation', $allocation1);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $allocation1 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame($period1->timewindowstart, $allocation1->timestart);
        $this->assertSame($period1->timewindowdue, $allocation1->timedue);
        $this->assertSame($period1->timewindowend, $allocation1->timeend);

        $allocation1->timeend = (string)($now + WEEKSECS);
        $DB->update_record('tool_muprog_allocation', $allocation1);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $allocation1 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame($period1->timewindowstart, $allocation1->timestart);
        $this->assertSame($period1->timewindowdue, $allocation1->timedue);
        $this->assertSame($period1->timewindowend, $allocation1->timeend);

        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($period2->timewindowstart, $allocation2->timestart);
        $this->assertSame($period2->timewindowdue, $allocation2->timedue);
        $this->assertSame($period2->timewindowend, $allocation2->timeend);

        $allocation2->timedue = (string)($now + DAYSECS);
        $DB->update_record('tool_muprog_allocation', $allocation2);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($period2->timewindowstart, $allocation2->timestart);
        $this->assertSame($period2->timewindowdue, $allocation2->timedue);
        $this->assertSame($period2->timewindowend, $allocation2->timeend);

        $allocation2->timeend = (string)($now + WEEKSECS);
        $DB->update_record('tool_muprog_allocation', $allocation2);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($period2->timewindowstart, $allocation2->timestart);
        $this->assertSame($period2->timewindowdue, $allocation2->timedue);
        $this->assertSame($period2->timewindowend, $allocation2->timeend);

        $allocation2->timedue = null;
        $DB->update_record('tool_muprog_allocation', $allocation2);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($period2->timewindowstart, $allocation2->timestart);
        $this->assertSame($period2->timewindowdue, $allocation2->timedue);
        $this->assertSame($period2->timewindowend, $allocation2->timeend);

        $allocation2->timeend = null;
        $DB->update_record('tool_muprog_allocation', $allocation2);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $allocation2 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($period2->timewindowstart, $allocation2->timestart);
        $this->assertSame($period2->timewindowdue, $allocation2->timedue);
        $this->assertSame($period2->timewindowend, $allocation2->timeend);

        $allocation1 = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame($period1->timewindowstart, $allocation1->timestart);
        $this->assertSame($period1->timewindowdue, $allocation1->timedue);
        $this->assertSame($period1->timewindowend, $allocation1->timeend);

        $program1->archived = '1';
        $DB->update_record('tool_muprog_program', $program1);
        $allocation1->timedue = (string)($now + DAYSECS);
        $allocation2->timedue = (string)($now + DAYSECS * 2);
        $allocation1->timeend = (string)($now + WEEKSECS);
        $DB->update_record('tool_muprog_allocation', $allocation1);
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $allocationx = $DB->get_record('tool_muprog_allocation', [
            'sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame($allocation1->timestart, $allocationx->timestart);
        $this->assertSame($allocation1->timedue, $allocationx->timedue);
        $this->assertSame($allocation1->timeend, $allocationx->timeend);
    }

    public function test_sync_certifications_complete(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);
        $top1 = program::load_content($program1->id);
        $program2 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program2source = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $top2 = program::load_content($program2->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program2->id,
        ];
        $certification2 = $generator->create_certification($data);
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();
        $user9 = $this->getDataGenerator()->create_user();

        $now = time();

        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user2->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => $now - DAYSECS,
            'timewindowend' => null,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user3->id], [
            'timewindowstart' => $now - WEEKSECS,
            'timewindowdue' => null,
            'timewindowend' => $now + WEEKSECS,
        ]);

        manual::assign_users($certification1->id, $source1->id, [$user4->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $assignment4 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user4->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $assignment4 = \tool_mucertify\local\source\base::assignment_archive($assignment4->id);

        manual::assign_users($certification1->id, $source1->id, [$user5->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $period5 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user5->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period5 = period::override_dates((object)['id' => $period5->id, 'timerevoked' => $now]);

        manual::assign_users($certification1->id, $source1->id, [$user6->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now + DAYSECS,
        ]);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user6->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period6 = period::override_dates((object)['id' => $period6->id, 'timecertified' => $now - 30]);

        manual::assign_users($certification1->id, $source1->id, [$user7->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        $period7 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user7->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period7 = period::override_dates((object)['id' => $period7->id, 'timewindowstart' => $now + WEEKSECS]);

        $this->assertCount(7, $DB->get_records('tool_muprog_allocation', []));
        $this->assertCount(7, $DB->get_records('tool_mucertify_period', []));
        $this->assertCount(6, $DB->get_records('tool_mucertify_period', ['timecertified' => null]));

        $DB->set_field('tool_muprog_allocation', 'timecompleted', ($now - 7), []);
        $this->setCurrentTimeStart();
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);
        $this->assertCount(7, $DB->get_records('tool_muprog_allocation', []));
        $this->assertCount(7, $DB->get_records('tool_mucertify_period', []));
        $this->assertCount(3, $DB->get_records('tool_mucertify_period', ['timecertified' => null]));

        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertTimeCurrent($period1->timecertified);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertTimeCurrent($period2->timecertified);
        $period3 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user3->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertTimeCurrent($period3->timecertified);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user6->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 30), $period6->timecertified);
    }

    public function test_sync_certifications_reset(): void {
        global $DB, $CFG;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \mod_forum_generator $forumgenerator */
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $admin = get_admin();

        $now = time();

        $CFG->enablecompletion = 1;
        $CFG->enableavailability = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $forum = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course->id, 'completion' => 1, 'completionview' => \COMPLETION_VIEW_REQUIRED]
        );
        $discussion = $forumgenerator->create_discussion(['course' => $course->id, 'forum' => $forum->id, 'userid' => $admin->id]);
        $cm = \cm_info::create(get_coursemodule_from_instance('forum', $forum->id));

        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $enrol2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $forum2 = $this->getDataGenerator()->create_module(
            'forum',
            ['course' => $course2->id, 'completion' => 1, 'completionview' => \COMPLETION_VIEW_REQUIRED]
        );
        $discussion2 = $forumgenerator->create_discussion(['course' => $course2->id, 'forum' => $forum2->id, 'userid' => $admin->id]);
        $cm2 = \cm_info::create(get_coursemodule_from_instance('forum', $forum2->id));

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program = $programgenerator->create_program(['sources' => 'mucertify']);
        $programsource = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'mucertify']);
        $item = $programgenerator->create_program_item(['programid' => $program->id, 'courseid' => $course->id]);

        $this->getDataGenerator()->enrol_user($user0->id, $course->id);
        $mallocation0 = $programgenerator->create_program_allocation(['programid' => $program->id, 'userid' => $user0->id]);
        $post0 = $forumgenerator->create_post(['discussion' => $discussion->id, 'userid' => $user0->id]);

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $mallocation1 = $programgenerator->create_program_allocation(['programid' => $program->id, 'userid' => $user1->id]);
        $post1 = $forumgenerator->create_post(['discussion' => $discussion->id, 'userid' => $user1->id]);

        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $mallocation2 = $programgenerator->create_program_allocation(['programid' => $program->id, 'userid' => $user2->id]);
        $post2 = $forumgenerator->create_post(['discussion' => $discussion->id, 'userid' => $user2->id]);

        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $mallocation3 = $programgenerator->create_program_allocation(['programid' => $program->id, 'userid' => $user3->id]);
        $post3 = $forumgenerator->create_post(['discussion' => $discussion->id, 'userid' => $user3->id]);

        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm, $user0->id);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user0->id]);
        $ccompletion->mark_complete();
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm, $user1->id);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm, $user2->id);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm, $user3->id);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user3->id]);
        $ccompletion->mark_complete();
        // Control data.
        $completion = new \completion_info($course2);
        $completion->set_module_viewed($cm2, $user2->id);
        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();
        $completion = new \completion_info($course2);
        $completion->set_module_viewed($cm2, $user3->id);
        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $user3->id]);
        $ccompletion->mark_complete();
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);

        $certification0 = $generator->create_certification(
            ['sources' => ['manual' => []], 'programid1' => $program->id, 'periods_resettype1' => course_reset::RESETTYPE_NONE]
        );
        $source0 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification0->id],
            '*',
            MUST_EXIST
        );
        $certification1 = $generator->create_certification(
            ['sources' => ['manual' => []], 'programid1' => $program->id, 'periods_resettype1' => course_reset::RESETTYPE_DEALLOCATE]
        );
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $certification2 = $generator->create_certification(
            ['sources' => ['manual' => []], 'programid1' => $program->id, 'periods_resettype1' => course_reset::RESETTYPE_STANDARD]
        );
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );
        $certification3 = $generator->create_certification(
            ['sources' => ['manual' => []], 'programid1' => $program->id, 'periods_resettype1' => course_reset::RESETTYPE_FULL]
        );
        $source3 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification3->id],
            '*',
            MUST_EXIST
        );

        manual::assign_users($certification0->id, $source0->id, [$user0->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification2->id, $source2->id, [$user2->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);
        manual::assign_users($certification3->id, $source3->id, [$user3->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
        ]);

        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['id' => $mallocation0->id]));
        $period0 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user0->id, 'certificationid' => $certification0->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('0', $period0->allocationid);
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user0->id]));
        $post = $DB->get_record('forum_posts', ['id' => $post0->id]);
        $this->assertSame($post0->subject, $post->subject);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user0->id]);
        $this->assertTrue($ccompletion->is_complete());
        $this->assertTrue($DB->record_exists('course_modules_completion', ['coursemoduleid' => $cm->id, 'userid' => $user0->id]));

        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['id' => $mallocation1->id]));
        $callocation1 = $DB->get_record('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program->id]);
        $this->assertSame($programsource->id, $callocation1->sourceid);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($callocation1->id, $period1->allocationid);
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user1->id]));
        $post = $DB->get_record('forum_posts', ['id' => $post1->id]);
        $this->assertSame($post1->subject, $post->subject);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user1->id]);
        $this->assertTrue($ccompletion->is_complete());
        $this->assertTrue($DB->record_exists('course_modules_completion', ['coursemoduleid' => $cm->id, 'userid' => $user1->id]));

        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['id' => $mallocation2->id]));
        $callocation2 = $DB->get_record('tool_muprog_allocation', ['userid' => $user2->id, 'programid' => $program->id]);
        $this->assertSame($programsource->id, $callocation2->sourceid);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($callocation2->id, $period2->allocationid);
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user2->id]));
        $post = $DB->get_record('forum_posts', ['id' => $post2->id]);
        $this->assertSame($post2->subject, $post->subject);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user2->id]);
        $this->assertFalse($ccompletion->is_complete());
        $this->assertFalse($DB->record_exists('course_modules_completion', ['coursemoduleid' => $cm->id, 'userid' => $user2->id]));

        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['id' => $mallocation3->id]));
        $callocation3 = $DB->get_record('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program->id]);
        $this->assertSame($programsource->id, $callocation3->sourceid);
        $period3 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user3->id, 'certificationid' => $certification3->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame($callocation3->id, $period3->allocationid);
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user3->id]));
        $post = $DB->get_record('forum_posts', ['id' => $post3->id]);
        $this->assertSame('', $post->subject);
        $ccompletion = new \completion_completion(['course' => $course->id, 'userid' => $user2->id]);
        $this->assertFalse($ccompletion->is_complete());
        $this->assertFalse($DB->record_exists('course_modules_completion', ['coursemoduleid' => $cm->id, 'userid' => $user3->id]));

        // Control data.
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $enrol2->id, 'userid' => $user2->id]));
        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $user2->id]);
        $this->assertTrue($ccompletion->is_complete());
        $this->assertTrue($DB->record_exists('course_modules_completion', ['coursemoduleid' => $cm2->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $enrol2->id, 'userid' => $user3->id]));
        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $user3->id]);
        $this->assertTrue($ccompletion->is_complete());
        $this->assertTrue($DB->record_exists('course_modules_completion', ['coursemoduleid' => $cm2->id, 'userid' => $user3->id]));
    }

    public function test_certificate_delete(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);
        $top1 = program::load_content($program1->id);
        $program2 = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $program2source = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $top2 = program::load_content($program2->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program2->id,
        ];
        $certification2 = $generator->create_certification($data);
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $now = time();
        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - DAYSECS,
        ]);
        manual::assign_users($certification1->id, $source1->id, [$user2->id], [
            'timewindowstart' => $now - WEEKSECS,
        ]);
        manual::assign_users($certification2->id, $source2->id, [$user3->id], [
            'timewindowstart' => $now - WEEKSECS,
        ]);
        $this->assertCount(3, $DB->get_records('tool_muprog_allocation', []));

        \tool_mucertify\local\certification::delete($certification1->id);
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', []));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['userid' => $user3->id]));
    }
}
