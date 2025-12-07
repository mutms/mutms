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

use tool_mucertify\local\certification;
use tool_mucertify\local\source\manual;

/**
 * Certification manual assignment source test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\source\manual
 */
final class manual_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('manual', manual::get_type());
    }

    public function test_get_name(): void {
        $this->assertSame('Manual assignment', manual::get_name());
    }

    public function test_is_new_allowed(): void {
        $certification = new \stdClass();
        $this->assertSame(true, manual::is_new_allowed($certification));
    }

    public function test_is_new_allowed_in_new(): void {
        $this->assertTrue(manual::is_new_allowed_in_new());
    }

    public function test_is_update_allowed(): void {
        $certification = new \stdClass();
        $this->assertSame(true, manual::is_update_allowed($certification));
    }

    public function test_fix_assignments(): void {
        $result = manual::fix_assignments(null, null);
        $this->assertFalse($result);
    }

    public function test_is_assignment_update_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id]);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = manual::assignment_archive($assignment2->id);

        $this->assertTrue(manual::is_assignment_update_possible($certification, $source, $assignment1));
        $this->assertFalse(manual::is_assignment_update_possible($certification, $source, $assignment2));

        $certification = certification::archive($certification->id);
        $this->assertFalse(manual::is_assignment_update_possible($certification, $source, $assignment1));
        $this->assertFalse(manual::is_assignment_update_possible($certification, $source, $assignment2));
    }

    public function test_is_assignment_archive_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id]);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = manual::assignment_archive($assignment2->id);

        $this->assertTrue(manual::is_assignment_archive_possible($certification, $source, $assignment1));
        $this->assertFalse(manual::is_assignment_archive_possible($certification, $source, $assignment2));

        $certification = certification::archive($certification->id);
        $this->assertFalse(manual::is_assignment_archive_possible($certification, $source, $assignment1));
        $this->assertFalse(manual::is_assignment_archive_possible($certification, $source, $assignment2));
    }

    public function test_is_assignment_restore_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id]);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = manual::assignment_archive($assignment2->id);

        $this->assertFalse(manual::is_assignment_restore_possible($certification, $source, $assignment1));
        $this->assertTrue(manual::is_assignment_restore_possible($certification, $source, $assignment2));

        $certification = certification::archive($certification->id);
        $this->assertFalse(manual::is_assignment_restore_possible($certification, $source, $assignment1));
        $this->assertFalse(manual::is_assignment_restore_possible($certification, $source, $assignment2));
    }

    public function test_is_assignment_delete_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id]);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment2 = manual::assignment_archive($assignment2->id);

        $this->assertFalse(manual::is_assignment_delete_possible($certification, $source, $assignment1));
        $this->assertTrue(manual::is_assignment_delete_possible($certification, $source, $assignment2));

        $certification = certification::archive($certification->id);
        $this->assertFalse(manual::is_assignment_delete_possible($certification, $source, $assignment1));
        $this->assertFalse(manual::is_assignment_delete_possible($certification, $source, $assignment2));
    }

    public function test_is_assignment_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $result = manual::is_assignment_possible($certification, $source);
        $this->assertTrue($result);

        $certification->archived = '1';
        $result = manual::is_assignment_possible($certification, $source);
        $this->assertFalse($result);
        $certification->archived = '0';

        $certification->programid1 = null;
        $result = manual::is_assignment_possible($certification, $source);
        $this->assertFalse($result);
    }

    public function test_get_catalogue_actions(): void {
        $certification = new \stdClass();
        $source = new \stdClass();
        $this->assertSame([], manual::get_catalogue_actions($certification, $source));
    }

    public function test_decode_datajson(): void {
        $source = new \stdClass();
        $this->assertSame($source, manual::decode_datajson($source));
    }

    public function test_encode_datajson(): void {
        $formdata = new \stdClass();
        $this->assertSame('[]', manual::encode_datajson($formdata));
    }

    public function test_add_management_certification_users_actions(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mucertify:assign', CAP_ALLOW, $editorroleid, $syscontext);
        \role_assign($editorroleid, $user1->id, $catcontext->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'contextid' => $catcontext->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->setUser($user2);
        $actions = new \tool_mulib\output\header_actions('xyz');
        manual::add_management_certification_users_actions($actions, $certification, $source);
        $this->assertFalse($actions->has_items());

        $this->setUser($user1);
        $actions = new \tool_mulib\output\header_actions('xyz');
        manual::add_management_certification_users_actions($actions, $certification, $source);
        $this->assertTrue($actions->has_items());

        $certification->archived = '1';
        $actions = new \tool_mulib\output\header_actions('xyz');
        manual::add_management_certification_users_actions($actions, $certification, $source);
        $this->assertFalse($actions->has_items());
        $certification->archived = '0';
    }

    public function test_update_source(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $data = [
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $data = [
            'certificationid' => $certification->id,
            'type' => 'manual',
            'enable' => 0,
        ];
        $source = manual::update_source((object)$data);
        $this->assertSame(null, $source);

        $data = [
            'certificationid' => $certification->id,
            'type' => 'manual',
            'enable' => 1,
        ];
        $source = manual::update_source((object)$data);
        $this->assertSame($certification->id, $source->certificationid);
        $this->assertSame('manual', $source->type);
    }

    public function test_assign_users(): void {
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
        $user4 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->setCurrentTimeStart();
        $result = manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($program1->id, $period->programid);
        $this->assertTimeCurrent($period->timewindowstart);
        $this->assertSame((string)($period->timewindowstart + DAYSECS), $period->timewindowdue);
        $this->assertSame((string)($period->timewindowstart + 2 * DAYSECS), $period->timewindowend);
        $this->assertSame(null, $period->allocationid);
        $this->assertSame(null, $period->timecertified);
        $this->assertSame(null, $period->timefrom);
        $this->assertSame(null, $period->timeuntil);
        $this->assertSame(null, $period->timerevoked);
        $this->assertSame('1', $period->first);
        $this->assertSame('1', $period->recertifiable);
        $this->assertCount(1, $result);
        $this->assertSame($assignment->id, $result[0]);

        $now = time();
        $this->setCurrentTimeStart();
        $result = manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => $now + DAYSECS,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($program1->id, $period->programid);
        $this->assertSame((string)($now - DAYSECS), $period->timewindowstart);
        $this->assertSame(null, $period->timewindowdue);
        $this->assertSame((string)($now + DAYSECS), $period->timewindowend);
        $this->assertSame(null, $period->allocationid);
        $this->assertSame(null, $period->timecertified);
        $this->assertSame(null, $period->timefrom);
        $this->assertSame(null, $period->timeuntil);
        $this->assertSame(null, $period->timerevoked);
        $this->assertSame('1', $period->first);
        $this->assertSame('1', $period->recertifiable);
        $this->assertCount(1, $result);
        $this->assertSame($assignment->id, $result[0]);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => null,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]);
        $this->assertFalse($period);

        $now = time();
        manual::assign_users($certification->id, $source->id, [$user2->id], [
            'timecertifiedtemp' => $now + WEEKSECS,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame((string)($now + WEEKSECS), $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id]);
        $this->assertFalse($period);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->setCurrentTimeStart();
        manual::assign_users($certification->id, $source->id, [$user3->id], [
            'noperiod' => 1,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user3->id]));

        $this->setCurrentTimeStart();
        manual::assign_users($certification->id, $source->id, [$user4->id], [
            'noperiod' => 0,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user4->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user4->id]));
    }

    public function test_assignment_update(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'recertify' => '98765',
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $data = [
            'id' => $assignment->id,
        ];
        manual::assignment_update((object)$data);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((array)$assignment, (array)$assignment2);

        $now = time();
        $data = [
            'id' => $assignment->id,
            'timecertifiedtemp' => (string)($now + WEEKSECS),
        ];
        manual::assignment_update((object)$data);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($data['timecertifiedtemp'], $assignment2->timecertifiedtemp);
        $this->assertSame($assignment->timecreated, $assignment2->timecertifiedfrom);
        $this->assertSame(null, $assignment2->timecertifieduntil);
        $assignment->timecertifiedtemp = $assignment2->timecertifiedtemp;
        $assignment->timecertifiedfrom = $assignment2->timecreated;
        $this->assertSame((array)$assignment, (array)$assignment2);

        $data = [
            'id' => $assignment->id,
            'timecertifiedtemp' => null,
        ];
        manual::assignment_update((object)$data);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment2->timecertifiedtemp);
        $this->assertSame(null, $assignment2->timecertifiedfrom);
        $this->assertSame(null, $assignment2->timecertifieduntil);

        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('1', $period->recertifiable);
        $data = [
            'id' => $assignment->id,
            'stoprecertify' => '1',
        ];
        manual::assignment_update((object)$data);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('0', $period->recertifiable);

        $data = [
            'id' => $assignment->id,
            'stoprecertify' => '0',
        ];
        manual::assignment_update((object)$data);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('1', $period->recertifiable);

        $certification = certification::update_settings((object)['id' => $certification->id, 'recertify' => null]);
        $data = [
            'id' => $assignment->id,
            'stoprecertify' => '1',
        ];
        manual::assignment_update((object)$data);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('1', $period->recertifiable);

        $period = \tool_mucertify\local\period::override_dates((object)[
            'id' => $period->id,
            'timecertified' => $now,
            'timefrom' => (string)($now + 10),
            'timeuntil' => (string)($now + DAYSECS * 10),
        ]);
        $data = [
            'id' => $assignment->id,
            'timecertifiedtemp' => (string)($now + DAYSECS * 12),
        ];
        manual::assignment_update((object)$data);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($period->timefrom, $assignment2->timecertifiedfrom);
        $this->assertSame($period->timeuntil, $assignment2->timecertifieduntil);
        $this->assertSame($data['timecertifiedtemp'], $assignment2->timecertifiedtemp);

        $data = [
            'id' => $assignment->id,
            'timecertifiedtemp' => (string)($now + DAYSECS * 9),
        ];
        $assignment2 = manual::assignment_update((object)$data);
        $this->assertSame($period->timefrom, $assignment2->timecertifiedfrom);
        $this->assertSame($period->timeuntil, $assignment2->timecertifieduntil);
        $this->assertSame(null, $assignment2->timecertifiedtemp);
    }

    public function test_assignment_archive(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'recertify' => '98765',
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('0', $assignment->archived);

        $assignment = manual::assignment_archive($assignment->id);
        $this->assertSame('1', $assignment->archived);

        $assignment = manual::assignment_archive($assignment->id);
        $this->assertSame('1', $assignment->archived);
    }

    public function test_assignment_restore(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'recertify' => '98765',
            'archived' => '1',
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment = manual::assignment_archive($assignment->id);

        $assignment = manual::assignment_restore($assignment->id);
        $this->assertSame('0', $assignment->archived);

        $assignment = manual::assignment_restore($assignment->id);
        $this->assertSame('0', $assignment->archived);
    }

    public function test_assignment_delete(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $this->setCurrentTimeStart();
        manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]));

        manual::assignment_delete($certification, $source, $assignment);
        $this->assertCount(0, $DB->get_records('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id]));
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id]));
    }

    public function test_render_status_details(): void {
        $certification = new \stdClass();
        $source = new \stdClass();
        $this->assertSame('Active', manual::render_status_details($certification, $source));
        $this->assertSame('Inactive', manual::render_status_details($certification, null));
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
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->setUser($user2);
        $this->assertSame('Active', manual::render_status($certification, $source));
        $this->assertSame('Inactive', manual::render_status($certification, null));

        $this->setUser($user1);
        $this->assertStringStartsWith('Active', manual::render_status($certification, $source));
        $this->assertStringContainsString('"Update Manual assignment"', manual::render_status($certification, $source));
        $this->assertStringStartsWith('Inactive', manual::render_status($certification, null));
        $this->assertStringContainsString('"Update Manual assignment"', manual::render_status($certification, null));
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

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->setUser(null);
        $result = manual::get_assigner($certification, $source, $assignment);
        $this->assertSame($admin->id, $result->id);

        $this->setUser($user2);
        $result = manual::get_assigner($certification, $source, $assignment);
        $this->assertSame($user2->id, $result->id);

        $this->setGuestUser();
        $result = manual::get_assigner($certification, $source, $assignment);
        $this->assertSame($admin->id, $result->id);
    }
}
