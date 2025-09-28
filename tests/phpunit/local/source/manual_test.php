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
use tool_muprog\local\source\manual;

/**
 * Manual allocation source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\manual
 */
final class manual_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('manual', manual::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->assertTrue(manual::is_new_allowed($program));
        \set_config('source_manual_allownew', 0, 'tool_muprog');
        $this->assertTrue(manual::is_new_allowed($program));
    }

    public function test_is_allocation_possible(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program1 = program::update_allocation(
            (object)['id' => $program1->id, 'timeallocationstart' => null, 'timeallocationend' => null]
        );
        $this->assertTrue(manual::is_allocation_possible($program1, $source1));

        $program1 = program::update_allocation(
            (object)['id' => $program1->id, 'timeallocationstart' => time() - 100, 'timeallocationend' => time() + 100]
        );
        $this->assertTrue(manual::is_allocation_possible($program1, $source1));

        $program1 = program::update_allocation(
            (object)['id' => $program1->id, 'timeallocationstart' => time() + 100, 'timeallocationend' => time() + 200]
        );
        $this->assertFalse(manual::is_allocation_possible($program1, $source1));

        $program1 = program::update_allocation(
            (object)['id' => $program1->id, 'timeallocationstart' => time() - 200, 'timeallocationend' => time() - 100]
        );
        $this->assertFalse(manual::is_allocation_possible($program1, $source1));

        $program1 = program::update_allocation(
            (object)['id' => $program1->id, 'timeallocationstart' => null, 'timeallocationend' => null]
        );
        $program1 = program::archive($program1->id);
        $this->assertFalse(manual::is_allocation_possible($program1, $source1));
    }

    public function test_allocate_users(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);
        $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id], 'id ASC');
        $allocations = array_values($allocations);
        $this->assertCount(2, $allocations);
        $this->assertSame($user1->id, $allocations[0]->userid);
        $this->assertSame($program1->id, $allocations[0]->programid);
        $this->assertSame($source1->id, $allocations[0]->sourceid);
        $this->assertSame($user2->id, $allocations[1]->userid);
        $this->assertSame($program1->id, $allocations[1]->programid);
        $this->assertSame($source1->id, $allocations[1]->sourceid);

        // Invalid default dates get fixed.
        $now = time();
        $data = (object)[
            'id' => $program1->id,
            'programstart_type' => 'date',
            'programstart_date' => $now,
            'programdue_type' => 'date',
            'programdue_date' => $now - 10,
            'programend_type' => 'date',
            'programend_date' => $now - 20,
        ];
        $program1 = program::update_scheduling($data);
        manual::allocate_users($program1->id, $source1->id, [$user3->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id]);
        $this->assertEquals($now, $allocation->timestart);
        $this->assertEquals($now + 1, $allocation->timedue);
        $this->assertEquals($now + 1, $allocation->timeend);

        // Use date overrides.
        $now = time();
        $dateoverrides = [
            'timeallocated' => $now - 60 * 60 * 3,
            'timestart' => $now - 60 * 60 * 2,
            'timedue' => $now + 60 * 60 * 1,
            'timeend' => $now + 60 * 60 * 2,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user4->id], $dateoverrides);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id]);
        $this->assertSame((string)$dateoverrides['timeallocated'], $allocation->timeallocated);
        $this->assertSame((string)$dateoverrides['timestart'], $allocation->timestart);
        $this->assertSame((string)$dateoverrides['timedue'], $allocation->timedue);
        $this->assertSame((string)$dateoverrides['timeend'], $allocation->timeend);
    }

    public function test_allocation_update(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $context1 = \context_course::instance($course1->id);
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $now = time();

        $allocation->timeallocated = (string)$now;
        $allocation->timestart = (string)($now - 60 * 60 * 12);
        $allocation->timedue = (string)($now + 60 * 60 * 10);
        $allocation->timeend = (string)($now + 60 * 60 * 20);
        $result = manual::allocation_update($allocation);
        $this->assertSame((array)$result, (array)$allocation);

        $newallocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame((array)$allocation, (array)$newallocation);

        $allocation->timeallocated = (string)$now;
        $allocation->timestart = (string)($now - 60 * 60 * 12);
        $allocation->timedue = (string)($now + 60 * 60 * 10);
        $allocation->timeend = (string)($now + 60 * 60 * 20);
        manual::allocation_update($allocation);
        $newallocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame((array)$allocation, (array)$newallocation);

        $sink = $this->redirectEvents();
        $allocation->timecompleted = (string)$now;
        manual::allocation_update($allocation);
        $newallocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation->calendarupdated = '0';
        $this->assertSame((array)$allocation, (array)$newallocation);
        $events = $sink->get_events();
        $sink->close();
        $event = array_pop($events);
        $this->assertInstanceOf(\tool_muprog\event\allocation_completed::class, $event);
    }

    public function test_allocation_archive(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $context1 = \context_course::instance($course1->id);
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $allocation = manual::allocation_archive($allocation->id);
        $this->assertSame('1', $allocation->archived);

        $allocation = manual::allocation_archive($allocation->id);
        $this->assertSame('1', $allocation->archived);
    }

    public function test_allocation_restore(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $context1 = \context_course::instance($course1->id);
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $allocation = manual::allocation_archive($allocation->id);

        $allocation = manual::allocation_restore($allocation->id);
        $this->assertSame('0', $allocation->archived);

        $allocation = manual::allocation_restore($allocation->id);
        $this->assertSame('0', $allocation->archived);
    }

    public function test_allocation_delete(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $notification = $generator->create_program_notification(['programid' => $program1->id, 'notificationtype' => 'allocation']);
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        manual::allocation_delete($program1, $source1, $allocation1);
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id]));
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));
    }

    public function test_process_uploaded_data(): void {
        global $CFG, $DB;
        require_once("$CFG->libdir/filelib.php");

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1', 'email' => 'user1@example.com', 'idnumber' => 'u1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2', 'email' => 'user2@example.com', 'idnumber' => 'u2']);
        $user3 = $this->getDataGenerator()->create_user(['username' => 'user3', 'email' => 'user3@example.com', 'idnumber' => 'u3']);
        $user4 = $this->getDataGenerator()->create_user(['username' => 'user4', 'email' => 'user4@example.com', 'idnumber' => 'u4']);

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = \file_get_unused_draft_itemid();

        $csvdata = [
            ['username', 'firstname', 'lastname'],
            ['user1', 'First', 'User'],
            ['user2', 'Second', 'User'],
        ];
        $data = (object)[
            'sourceid' => $source1->id,
            'usermapping' => 'username',
            'usercolumn' => 0,
            'hasheaders' => 1,
            'userfile' => $draftid,
        ];
        $expected = [
            'assigned' => 2,
            'skipped' => 0,
            'errors' => 0,
        ];
        $result = manual::process_uploaded_data($data, $csvdata);
        $this->assertSame($expected, $result);
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id]));

        $csvdata = [
            ['user3@example.com'],
            ['user2@example.com'],
        ];
        $data = (object)[
            'sourceid' => $source1->id,
            'usermapping' => 'email',
            'usercolumn' => 0,
            'hasheaders' => 0,
            'userfile' => $draftid,
        ];
        $expected = [
            'assigned' => 1,
            'skipped' => 1,
            'errors' => 0,
        ];
        $result = manual::process_uploaded_data($data, $csvdata);
        $this->assertSame($expected, $result);
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id]));

        $csvdata = [
            ['1', 'u5'],
            ['1', 'u4'],
        ];
        $data = (object)[
            'sourceid' => $source1->id,
            'usermapping' => 'idnumber',
            'usercolumn' => 1,
            'hasheaders' => 0,
            'userfile' => $draftid,
        ];
        $expected = [
            'assigned' => 1,
            'skipped' => 0,
            'errors' => 1,
        ];
        $result = manual::process_uploaded_data($data, $csvdata);
        $this->assertSame($expected, $result);
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id]));
    }

    public function test_process_uploaded_data_with_dates(): void {
        global $CFG, $DB;
        require_once("$CFG->libdir/filelib.php");

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $admin = get_admin();
        $this->setUser($admin);

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1', 'email' => 'user1@example.com', 'idnumber' => 'u1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2', 'email' => 'user2@example.com', 'idnumber' => 'u2']);
        $user3 = $this->getDataGenerator()->create_user(['username' => 'user3', 'email' => 'user3@example.com', 'idnumber' => 'u3']);
        $user4 = $this->getDataGenerator()->create_user(['username' => 'user4', 'email' => 'user4@example.com', 'idnumber' => 'u4']);
        $user5 = $this->getDataGenerator()->create_user(['username' => 'user5', 'email' => 'user4@example.com', 'idnumber' => 'u5']);

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $timestart = time();
        $tz = new \DateTimeZone(get_user_timezone());
        $pdata = (object)[
            'id' => $program1->id,
            'programstart_type' => 'date',
            'programstart_date' => $timestart,
            'programdue_type' => 'date',
            'programdue_date' => $timestart + (20 * 60 * 60 * 24),
            'programend_type' => 'date',
            'programend_date' => $timestart + (30 * 60 * 60 * 24),
        ];
        $program1 = program::update_scheduling($pdata);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $draftid = \file_get_unused_draft_itemid();

        $timestart2 = new \DateTime('@' . ($timestart - (10 * 60 * 60 * 24)));
        $timedue3 = new \DateTime('@' . ($timestart + (1 * 60 * 60 * 24)));
        $timeend3 = new \DateTime('@' . ($timestart + (2 * 60 * 60 * 24)));
        $csvdata = [
            ['u1', '', '', ''],
            ['u2', $timestart2->format(\DateTime::ATOM), '', ''],
            ['u3', '', $timedue3->format(\DateTime::ATOM), $timeend3->format(\DateTime::COOKIE)],
            ['u4', 'abc', '', ''],
            ['u5', '', '', '01/01/2001'],
        ];
        $data = (object)[
            'sourceid' => $source1->id,
            'usermapping' => 'idnumber',
            'usercolumn' => 0,
            'timestartcolumn' => 1,
            'timeduecolumn' => 2,
            'timeendcolumn' => 3,
            'hasheaders' => 0,
            'userfile' => $draftid,
        ];
        $expected = [
            'assigned' => 3,
            'skipped' => 0,
            'errors' => 2,
        ];
        $result = manual::process_uploaded_data($data, $csvdata);
        $this->assertSame($expected, $result);

        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertEquals($pdata->programstart_date, $allocation1->timestart);
        $this->assertEquals($pdata->programdue_date, $allocation1->timedue);
        $this->assertEquals($pdata->programend_date, $allocation1->timeend);

        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id]);
        $this->assertEquals($timestart2->getTimestamp(), $allocation2->timestart);
        $this->assertEquals($pdata->programdue_date, $allocation2->timedue);
        $this->assertEquals($pdata->programend_date, $allocation2->timeend);

        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id]);
        $this->assertEquals($pdata->programstart_date, $allocation3->timestart);
        $this->assertEquals($timedue3->getTimestamp(), $allocation3->timedue);
        $this->assertEquals($timeend3->getTimestamp(), $allocation3->timeend);
    }

    public function test_tool_uploaduser_process(): void {
        global $CFG, $DB;
        require_once("$CFG->dirroot/admin/tool/uploaduser/locallib.php");

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['idnumber' => 123, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['idnumber' => 342, 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $now = time();
        $data = (object)[
            'id' => $program2->id,
            'programstart_type' => 'date',
            'programstart_date' => $now,
            'programdue_type' => 'date',
            'programdue_date' => $now + 60 * 60,
            'programend_type' => 'date',
            'programend_date' => $now + 60 * 60 * 2,
        ];
        $program2 = program::update_scheduling($data);

        $program3 = $generator->create_program();

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1', 'email' => 'user1@example.com', 'idnumber' => 'u1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2', 'email' => 'user2@example.com', 'idnumber' => 'u2']);
        $manager = $this->getDataGenerator()->create_user();

        $syscontext = \context_system::instance();
        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:allocate', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $manager->id, $syscontext->id);
        $this->setUser($manager);

        $upt = new class extends \uu_progress_tracker {
            /** @var array */
            public $result;
            // phpcs:ignore moodle.Commenting.MissingDocblock.MissingTestcaseMethodDescription
            public function reset() {
                $this->result = [];
                return $this;
            }
            // phpcs:ignore moodle.Commenting.MissingDocblock.MissingTestcaseMethodDescription
            public function track($col, $msg, $level = 'normal', $merge = true) {
                if (!in_array($col, $this->columns)) {
                    throw new \Exception('Incorrect column:' . $col);
                }
                if (!$merge) {
                    $this->result[$col][$level] = [];
                }
                $this->result[$col][$level][] = $msg;
            }
        };

        $data = (object)[
            'id' => $user1->id,
            'program1' => $program1->idnumber,
        ];
        manual::tool_uploaduser_process($data, 'xyz', $upt->reset());
        $this->assertSame([], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);

        $data = (object)[
            'id' => $user1->id,
            'program1' => $program1->idnumber,
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'program1', $upt->reset());
        $this->assertSame([
            'enrolments' => ['info' => ['Allocated to \'Program 1\'']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertTimeCurrent($allocation->timeallocated);
        $this->assertTimeCurrent($allocation->timeallocated, $allocation->timestart);
        $this->assertSame(null, $allocation->timedue);
        $this->assertSame(null, $allocation->timeend);
        manual::allocation_delete($program1, $source1, $allocation);

        $data = (object)[
            'id' => $user1->id,
            'programid9' => $program1->id,
            'pstartdate9' => '10/29/2022',
            'penddate9' => '2022-12-29',
            'pduedate9' => '21.11.2022',
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'programid9', $upt->reset());
        $this->assertSame([
            'enrolments' => ['info' => ['Allocated to \'Program 1\'']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertTimeCurrent($allocation->timeallocated);
        $this->assertSame(strtotime($data->pstartdate9), (int)$allocation->timestart);
        $this->assertSame(strtotime($data->pduedate9), (int)$allocation->timedue);
        $this->assertSame(strtotime($data->penddate9), (int)$allocation->timeend);
        manual::allocation_delete($program1, $source1, $allocation);

        $data = (object)[
            'id' => $user1->id,
            'program2' => $program2->idnumber,
            'pstartdate2' => '10/29/2022',
            'penddate2' => '',
            'pduedate2' => '',
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'program2', $upt->reset());
        $this->assertSame([
            'enrolments' => ['info' => ['Allocated to \'Program 2\'']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id]);
        $this->assertTimeCurrent($allocation->timeallocated);
        $this->assertSame(strtotime($data->pstartdate2), (int)$allocation->timestart);
        $this->assertSame($now + 60 * 60, (int)$allocation->timedue);
        $this->assertSame($now + 60 * 60 * 2, (int)$allocation->timeend);
        manual::allocation_delete($program2, $source2, $allocation);

        $data = (object)[
            'id' => $user1->id,
            'programid1' => '999',
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'programid1', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Cannot allocate to \'999\'']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);

        $data = (object)[
            'id' => $user1->id,
            'programid1' => $program3->id,
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'programid1', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Cannot allocate to \'Program 3\'']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program3->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);

        $data = (object)[
            'id' => $user1->id,
            'programid1' => $program1->id,
            'pstartdate1' => 'xx',
            'penddate1' => '',
            'pduedate1' => '',
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'programid1', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Invalid program allocation dates']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);

        $data = (object)[
            'id' => $user1->id,
            'programid1' => $program1->id,
            'pstartdate1' => '10/29/2022',
            'penddate1' => '2022-09-29',
            'pduedate1' => '21.11.2022',
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'programid1', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Invalid program allocation dates']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);

        $data = (object)[
            'id' => $user1->id,
            'program1' => $program3->idnumber,
        ];
        manual::tool_uploaduser_process($data, 'program1', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Cannot allocate to \'Program 3\'']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program3->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);

        $data = (object)[
            'id' => $user1->id,
            'programid2' => $program2->id,
            'pstartdate2' => '10/29/2035',
            'penddate2' => '',
            'pduedate2' => '',
        ];
        manual::tool_uploaduser_process($data, 'programid2', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Invalid program allocation dates']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);

        $this->setUser($user2);

        $data = (object)[
            'id' => $user1->id,
            'program1' => $program1->idnumber,
        ];
        manual::tool_uploaduser_process($data, 'program1', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Cannot allocate to \'Program 1\'']],
        ], $upt->result);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]);
        $this->assertFalse($allocation);
    }


    public function test_tool_uploaduser_programid_col_process(): void {
        global $CFG, $DB;
        require_once("$CFG->dirroot/admin/tool/uploaduser/locallib.php");

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['idnumber' => 123, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['idnumber' => 'PR2', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $now = time();
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1', 'email' => 'user1@example.com', 'idnumber' => 'u1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2', 'email' => 'user2@example.com', 'idnumber' => 'u2']);
        $manager = $this->getDataGenerator()->create_user();

        $syscontext = \context_system::instance();
        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:allocate', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $manager->id, $syscontext->id);
        $this->setUser($manager);

        $upt = new class extends \uu_progress_tracker {
            /** @var array */
            public $result;
            // phpcs:ignore moodle.Commenting.MissingDocblock.MissingTestcaseMethodDescription
            public function reset() {
                $this->result = [];
                return $this;
            }
            // phpcs:ignore moodle.Commenting.MissingDocblock.MissingTestcaseMethodDescription
            public function track($col, $msg, $level = 'normal', $merge = true) {
                if (!in_array($col, $this->columns)) {
                    throw new \Exception('Incorrect column:' . $col);
                }
                if (!$merge) {
                    $this->result[$col][$level] = [];
                }
                $this->result[$col][$level][] = $msg;
            }
        };

        $data = (object)[
            'id' => $user1->id,
            'programid2' => $program2->idnumber,
            'pstartdate2' => '10/29/2022',
            'penddate2' => '',
            'pduedate2' => '',
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'programid2', $upt->reset());
        $this->assertSame([
            'enrolments' => ['error' => ['Cannot allocate to \'PR2\'']],
        ], $upt->result);

        $data = (object)[
            'id' => $user1->id,
            'programid2' => $program2->id,
            'pstartdate2' => '10/29/2022',
            'penddate2' => '',
            'pduedate2' => '',
        ];
        $this->setCurrentTimeStart();
        manual::tool_uploaduser_process($data, 'programid2', $upt->reset());
        $this->assertSame([
            'enrolments' => ['info' => ['Allocated to \'Program 2\'']],
        ], $upt->result);
    }

    public function test_is_import_allowed(): void {

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $program2 = $generator->create_program(['sources' => []]);
        $program3 = $generator->create_program(['sources' => []]);
        $program4 = $generator->create_program(['sources' => ['manual' => []]]);

        $this->assertFalse(get_config('tool_muprog', 'source_manual_allownew'));

        $this->assertTrue(manual::is_import_allowed($program1, $program3));
        $this->assertFalse(manual::is_import_allowed($program2, $program3));
        $this->assertTrue(manual::is_import_allowed($program1, $program4));
        $this->assertFalse(manual::is_import_allowed($program2, $program4));
    }

    public function test_import_source_data(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $program2 = $generator->create_program(['sources' => []]);

        $source1 = manual::update_source((object)[
            'programid' => $program1->id,
            'type' => 'manual',
            'enable' => 1,
        ]);

        $source2 = manual::import_source_data($program1->id, $program2->id);
        $this->assertSame($program2->id, $source2->programid);
        $this->assertSame('manual', $source2->type);
        $this->assertSame($source1->datajson, $source2->datajson);
        $this->assertSame($source1->auxint1, $source2->auxint1);
        $this->assertSame($source1->auxint2, $source2->auxint2);
        $this->assertSame($source1->auxint3, $source2->auxint3);
    }
}
