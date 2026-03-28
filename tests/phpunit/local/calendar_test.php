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

namespace tool_muprog\phpunit\local;

use tool_muprog\local\source\manual;

/**
 * Program calendar helper test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\calendar
 */
final class calendar_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_fix_allocation_events(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => null,
            'timeend' => null,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id], $dates);
        $dates = [
            'timestart' => $now - 100,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id], $dates);

        $DB->delete_records('event', ['component' => 'tool_muprog']);

        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation1x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $allocation2x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        \tool_muprog\local\calendar::fix_allocation_events($allocation1x1, $program1);
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)$now, $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_DUE],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 200), $event->timestart);
        $this->assertSame("$program2->fullname is due", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_END],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 300), $event->timestart);
        $this->assertSame("$program2->fullname ends", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timestart = (string)($now + 100);
        $allocation2x1->timedue = (string)($now + 1000);
        $allocation2x1->timeend = (string)($now + 2000);
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        $program2->fullname = 'XYZZZZ';
        $program2->description = 'blah';
        $program2->format = (string)\FORMAT_MARKDOWN;
        $DB->update_record('tool_muprog_program', $program2);
        $DB->set_field('tool_muprog_allocation', 'calendarupdated', 0, []);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_DUE],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 1000), $event->timestart);
        $this->assertSame("$program2->fullname is due", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_END],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 2000), $event->timestart);
        $this->assertSame("$program2->fullname ends", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timestart = (string)($now - 100);
        $allocation2x1->timedue = null;
        $allocation2x1->timeend = null;
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(2, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $DB->set_field('tool_muprog_allocation', 'calendarupdated', 0, []);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(2, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timestart = (string)($now + 100);
        $allocation2x1->timedue = (string)($now + 1000);
        $allocation2x1->timeend = (string)($now + 2000);
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog']));

        $allocation2x1->archived = '1';
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog']));

        $allocation2x1->archived = '0';
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog']));

        $program2->archived = '1';
        $DB->update_record('tool_muprog_program', $program2);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog']));

        $program2->archived = '0';
        $DB->update_record('tool_muprog_program', $program2);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog']));

        $allocation2x1->timecompleted = $now;
        $DB->update_record('tool_muprog_allocation', $allocation1x1);
        \tool_muprog\local\calendar::fix_allocation_events($allocation2x1, $program2);
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog']));
    }

    public function test_fix_program_events(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $dates = [
            'timestart' => $now - 100,
            'timedue' => null,
            'timeend' => null,
        ];
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id], $dates);

        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $DB->delete_records('event', ['component' => 'tool_muprog']);

        \tool_muprog\local\calendar::fix_program_events($program2);
        $this->assertCount(2, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x2->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user2->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_DUE],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 200), $event->timestart);
        $this->assertSame("$program1->fullname is due", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_END],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 300), $event->timestart);
        $this->assertSame("$program1->fullname ends", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x2->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user2->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timestart = (string)($now + 100);
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timedue = (string)($now + 200);
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(6, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_DUE],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 200), $event->timestart);
        $this->assertSame("$program2->fullname is due", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timedue = (string)($now + 300);
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(6, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_DUE],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 300), $event->timestart);
        $this->assertSame("$program2->fullname is due", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timedue = null;
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));

        $allocation2x1->timeend = (string)($now + 200);
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(6, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_END],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 200), $event->timestart);
        $this->assertSame("$program2->fullname ends", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timeend = (string)($now + 300);
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(6, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_END],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 300), $event->timestart);
        $this->assertSame("$program2->fullname ends", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timeend = null;
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));

        $newprogram2 = clone($program2);
        $newprogram2->fullname = 'XYZZZZ';
        $newprogram2->description = 'blah';
        $newprogram2->format = (string)\FORMAT_MARKDOWN;
        $DB->update_record('tool_muprog_program', $newprogram2);

        \tool_muprog\local\calendar::fix_program_events(null);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $DB->set_field('tool_muprog_allocation', 'calendarupdated', 0, []);
        \tool_muprog\local\calendar::fix_program_events(null);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$newprogram2->fullname starts", $event->name);
        $this->assertSame($newprogram2->description, $event->description);
        $this->assertSame($newprogram2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $program2 = $newprogram2;

        $program1->archived = '1';
        $DB->update_record('tool_muprog_program', $program1);
        $allocation2x2->archived = '1';
        $DB->update_record('tool_muprog_allocation', $allocation2x2);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation2x1->timecompleted = $now;
        $DB->update_record('tool_muprog_allocation', $allocation2x1);
        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog']));
    }

    public function test_delete_allocation_events(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $dates = [
            'timestart' => $now - 100,
            'timedue' => null,
            'timeend' => null,
        ];
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id], $dates);

        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $DB->delete_records('event', ['component' => 'tool_muprog']);

        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation1x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x2->id]));

        \tool_muprog\local\calendar::delete_allocation_events($allocation2x1->id);
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation1x1->id]));
        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x2->id]));
    }

    public function test_delete_program_events(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $dates = [
            'timestart' => $now - 100,
            'timedue' => null,
            'timeend' => null,
        ];
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id], $dates);

        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $DB->delete_records('event', ['component' => 'tool_muprog']);

        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation1x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x2->id]));

        \tool_muprog\local\calendar::delete_program_events($program2->id);
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation1x1->id]));
        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x1->id]));
        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x2->id]));
    }

    public function test_invalidate_program_events(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $dates = [
            'timestart' => $now - 100,
            'timedue' => null,
            'timeend' => null,
        ];
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id], $dates);

        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $DB->delete_records('event', ['component' => 'tool_muprog']);

        \tool_muprog\local\calendar::fix_program_events(null);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog', 'visible' => 1]));
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation1x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x2->id]));
        $this->assertCount(3, $DB->get_records('tool_muprog_allocation', ['calendarupdated' => 1]));
        $this->assertCount(0, $DB->get_records('tool_muprog_allocation', ['calendarupdated' => 0]));

        \tool_muprog\local\calendar::invalidate_program_events($program2->id);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog', 'visible' => 1]));
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation1x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x1->id]));
        $this->assertCount(1, $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation2x2->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['calendarupdated' => 1]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['calendarupdated' => 1, 'id' => $allocation1x1->id]));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['calendarupdated' => 0]));
    }

    public function test_update_general(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $dates = [
            'timestart' => $now - 100,
            'timedue' => null,
            'timeend' => null,
        ];
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id], $dates);

        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog', 'visible' => 1]));

        $data = [
            'id' => $program1->id,
            'fullname' => 'XYZ',
        ];
        $program1 = \tool_muprog\local\program::update_general((object)$data);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog', 'visible' => 1]));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $data = [
            'id' => $program1->id,
            'description' => 'blah blah',
        ];
        $program1 = \tool_muprog\local\program::update_general((object)$data);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog', 'visible' => 1]));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
    }

    public function test_allocate(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog']));

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(3, $DB->get_records('event', ['component' => 'tool_muprog']));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_DUE],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 200), $event->timestart);
        $this->assertSame("$program1->fullname is due", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_END],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 300), $event->timestart);
        $this->assertSame("$program1->fullname ends", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
    }

    public function test_deallocate(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $dates = [
            'timestart' => $now - 100,
            'timedue' => null,
            'timeend' => null,
        ];
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id], $dates);

        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2x2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(5, $DB->get_records('event', ['component' => 'tool_muprog', 'visible' => 1]));

        manual::allocation_delete($program2, $source2, $allocation2x1, true);
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog']));
        $this->assertCount(4, $DB->get_records('event', ['component' => 'tool_muprog', 'visible' => 1]));
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_DUE],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 200), $event->timestart);
        $this->assertSame("$program1->fullname is due", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_END],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + 300), $event->timestart);
        $this->assertSame("$program1->fullname ends", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation2x2->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program2->fullname starts", $event->name);
        $this->assertSame($program2->description, $event->description);
        $this->assertSame($program2->descriptionformat, $event->format);
        $this->assertSame($user2->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
    }

    public function test_allocation_update(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog']));

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation1x1->timestart = (string)($now - 100);
        $allocation1x1 = \tool_muprog\local\source\base::allocation_update($allocation1x1);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now - 100), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);
    }

    public function test_completion(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog']));

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation1x1->timecompleted = (string)($now - 100);
        $allocation1x1 = \tool_muprog\local\source\base::allocation_update($allocation1x1);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START]
        );
        $this->assertFalse($event);
    }

    public function test_archiving(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $this->assertCount(0, $DB->get_records('event', ['component' => 'tool_muprog']));

        $now = time();

        $dates = [
            'timestart' => $now,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        manual::allocate_users($program1->id, $source1->id, [$user1->id], $dates);
        $allocation1x1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now), $event->timestart);
        $this->assertSame("$program1->fullname starts", $event->name);
        $this->assertSame($program1->description, $event->description);
        $this->assertSame($program1->descriptionformat, $event->format);
        $this->assertSame($user1->id, $event->userid);
        $this->assertSame('0', $event->courseid);
        $this->assertSame('0', $event->groupid);
        $this->assertSame('1', $event->visible);

        $allocation1x1 = \tool_muprog\local\source\base::allocation_archive($allocation1x1->id);
        $event = $DB->get_record(
            'event',
            ['component' => 'tool_muprog', 'instance' => $allocation1x1->id, 'eventtype' => \tool_muprog\local\calendar::EVENTTYPE_START]
        );
        $this->assertFalse($event);
    }
}
