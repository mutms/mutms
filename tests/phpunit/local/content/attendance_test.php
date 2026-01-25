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
// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

namespace tool_muprog\phpunit\local\content;

use tool_muprog\local\content\attendance;

/**
 * Program offline attendance test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\content\attendance
 */
final class attendance_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('attendance', attendance::get_type());
    }

    public function test_get_type_name(): void {
        $this->assertSame('Offline attendance', attendance::get_type_name());
    }

    public function test_get_statuses(): void {
        $this->assertSame(
            [
                0 => 'Not set',
                3 => 'No show',
                2 => 'Failed',
                1 => 'Completed',
            ],
            attendance::get_statuses()
        );
    }

    public function test_take_attendance(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $this->assertInstanceOf('tool_muprog_generator', $generator);

        $program = $generator->create_program([]);

        $course1 = $this->getDataGenerator()->create_course();
        $record = [
            'programid' => $program->id,
            'courseid' => $course1->id,
        ];
        $item1 = $generator->create_program_item($record);
        $record = [
            'programid' => $program->id,
            'type' => 'attendance',
            'fullname' => 'Driving test',
        ];
        $item2 = $generator->create_program_item($record);
        $record = [
            'programid' => $program->id,
            'type' => 'attendance',
            'fullname' => 'Riding test',
            'completiondelay' => 600,
        ];
        $item3 = $generator->create_program_item($record);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $allocation1 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program->id]);
        $allocation2 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program->id]);
        $this->assertSame('0', $allocation1->itemscompleted);
        $this->assertSame('0', $allocation2->itemscompleted);

        $now = time();

        $this->setUser($user0);

        $this->setCurrentTimeStart();
        $attendance1x2 = attendance::take_attendance((object)[
            'itemid' => $item2->get_id(),
            'userid' => $user1->id,
            'status' => attendance::STATUS_NOSHOW,
        ]);
        $this->assertSame((string)$item2->get_id(), $attendance1x2->itemid);
        $this->assertSame($user1->id, $attendance1x2->userid);
        $this->assertSame((string)attendance::STATUS_NOSHOW, $attendance1x2->status);
        $this->assertTimeCurrent($attendance1x2->timeeffective);
        $this->assertSame($user0->id, $attendance1x2->takenby);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation1->itemscompleted);

        $this->setUser($user2->id);

        $attendance1x2 = attendance::take_attendance((object)[
            'itemid' => $item2->get_id(),
            'userid' => $user1->id,
            'status' => attendance::STATUS_FAILED,
            'timeeffective' => $now - 600,
        ]);
        $this->assertSame((string)$item2->get_id(), $attendance1x2->itemid);
        $this->assertSame($user1->id, $attendance1x2->userid);
        $this->assertSame((string)attendance::STATUS_FAILED, $attendance1x2->status);
        $this->assertSame((string)($now - 600), $attendance1x2->timeeffective);
        $this->assertSame($user2->id, $attendance1x2->takenby);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('0', $allocation1->itemscompleted);
        $completion1x2 = $DB->get_record('tool_muprog_completion', ['itemid' => $item2->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertFalse($completion1x2);

        $this->setUser($user0->id);

        $attendance1x2 = attendance::take_attendance((object)[
            'itemid' => $item2->get_id(),
            'userid' => $user1->id,
            'status' => attendance::STATUS_COMPLETED,
            'timeeffective' => $now - 10,
        ]);
        $this->assertSame((string)$item2->get_id(), $attendance1x2->itemid);
        $this->assertSame($user1->id, $attendance1x2->userid);
        $this->assertSame((string)attendance::STATUS_COMPLETED, $attendance1x2->status);
        $this->assertSame((string)($now - 10), $attendance1x2->timeeffective);
        $this->assertSame($user0->id, $attendance1x2->takenby);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('1', $allocation1->itemscompleted);
        $completion1x2 = $DB->get_record('tool_muprog_completion', ['itemid' => $item2->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertSame((string)($now - 10), $completion1x2->timecompleted);

        $attendance1x3 = attendance::take_attendance((object)[
            'itemid' => $item3->get_id(),
            'userid' => $user1->id,
            'status' => attendance::STATUS_COMPLETED,
            'timeeffective' => $now,
        ]);
        $this->assertSame((string)$item3->get_id(), $attendance1x3->itemid);
        $this->assertSame($user1->id, $attendance1x3->userid);
        $this->assertSame((string)attendance::STATUS_COMPLETED, $attendance1x3->status);
        $this->assertSame((string)$now, $attendance1x3->timeeffective);
        $this->assertSame($user0->id, $attendance1x3->takenby);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('2', $allocation1->itemscompleted);
        $completion1x3 = $DB->get_record('tool_muprog_completion', ['itemid' => $item3->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertSame((string)($now + 600), $completion1x3->timecompleted);

        $attendance1x2 = attendance::take_attendance((object)[
            'itemid' => $item2->get_id(),
            'userid' => $user1->id,
            'status' => attendance::STATUS_NOTSET,
            'timeeffective' => $now,
        ]);
        $this->assertSame((string)$item2->get_id(), $attendance1x2->itemid);
        $this->assertSame($user1->id, $attendance1x2->userid);
        $this->assertSame(null, $attendance1x2->status);
        $this->assertSame(null, $attendance1x2->timeeffective);
        $this->assertSame(null, $attendance1x2->takenby);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('1', $allocation1->itemscompleted);
        $completion1x2 = $DB->get_record('tool_muprog_completion', ['itemid' => $item2->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertFalse($completion1x2);
        $completion1x3 = $DB->get_record('tool_muprog_completion', ['itemid' => $item3->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertSame((string)($now + 600), $completion1x3->timecompleted);
    }
}
