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

namespace tool_muprog\phpunit\local\notification;

use tool_muprog\local\allocation;
use tool_muprog\local\notification\base;
use tool_muprog\local\source\manual;

/**
 * Program notifications base test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\notification\base
 */
final class base_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_constants(): void {
        $this->assertGreaterThan(0, base::TIME_SOON);
        $this->assertGreaterThan(0, base::TIME_CUTOFF);
    }

    public function test_get_allocation_placeholders(): void {
        global $DB, $CFG;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $strnotset = get_string('notset', 'tool_muprog');

        $result = base::get_allocation_placeholders($program1, $source1, $allocation, $user1);
        $this->assertIsArray($result);
        $this->assertSame(\fullname($user1), $result['user_fullname']);
        $this->assertSame($user1->firstname, $result['user_firstname']);
        $this->assertSame($user1->lastname, $result['user_lastname']);
        $this->assertSame($program1->fullname, $result['program_fullname']);
        $this->assertSame($program1->idnumber, $result['program_idnumber']);
        $this->assertSame("$CFG->wwwroot/admin/tool/muprog/my/program.php?id=$program1->id", $result['program_url']);
        $this->assertSame('Manual allocation', $result['program_sourcename']);
        $this->assertSame('Open', $result['program_status']);
        $this->assertSame(userdate($allocation->timeallocated), $result['program_allocationdate']);
        $this->assertSame(userdate($allocation->timestart), $result['program_startdate']);
        $this->assertSame($strnotset, $result['program_duedate']);
        $this->assertSame($strnotset, $result['program_enddate']);
        $this->assertSame($strnotset, $result['allocation_completeddate']);

        $result = base::get_allocation_placeholders($program1, $source1, $allocation, $user1);
        $this->assertIsArray($result);
        $this->assertSame(\fullname($user1), $result['user_fullname']);
        $this->assertSame($user1->firstname, $result['user_firstname']);
        $this->assertSame($user1->lastname, $result['user_lastname']);
        $this->assertSame($program1->fullname, $result['program_fullname']);
        $this->assertSame($program1->idnumber, $result['program_idnumber']);
        $this->assertSame("$CFG->wwwroot/admin/tool/muprog/my/program.php?id=$program1->id", $result['program_url']);
        $this->assertSame('Manual allocation', $result['program_sourcename']);
        $this->assertSame('Open', $result['program_status']);
        $this->assertSame(userdate($allocation->timeallocated), $result['program_allocationdate']);
        $this->assertSame(userdate($allocation->timestart), $result['program_startdate']);
        $this->assertSame($strnotset, $result['program_duedate']);
        $this->assertSame($strnotset, $result['program_enddate']);
        $this->assertSame($strnotset, $result['allocation_completeddate']);

        $now = time();
        $allocation->archived = '0';
        $allocation->timeallocated = (string)$now;
        $allocation->timestart = (string)($now - 60 * 60 * 24 * 1);
        $allocation->timedue = (string)($now + 60 * 60 * 24 * 10);
        $allocation->timeend = (string)($now + 60 * 60 * 24 * 20);
        $allocation->timecompleted = (string)($now + 60 * 60 * 24 * 1);
        \tool_muprog\local\source\base::allocation_update($allocation);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $result = base::get_allocation_placeholders($program1, $source1, $allocation, $user1);
        $this->assertIsArray($result);
        $this->assertSame(\fullname($user1), $result['user_fullname']);
        $this->assertSame($user1->firstname, $result['user_firstname']);
        $this->assertSame($user1->lastname, $result['user_lastname']);
        $this->assertSame($program1->fullname, $result['program_fullname']);
        $this->assertSame($program1->idnumber, $result['program_idnumber']);
        $this->assertSame("$CFG->wwwroot/admin/tool/muprog/my/program.php?id=$program1->id", $result['program_url']);
        $this->assertSame('Manual allocation', $result['program_sourcename']);
        $this->assertSame('Completed', $result['program_status']);
        $this->assertSame(userdate($allocation->timeallocated), $result['program_allocationdate']);
        $this->assertSame(userdate($allocation->timestart), $result['program_startdate']);
        $this->assertSame(userdate($allocation->timedue), $result['program_duedate']);
        $this->assertSame(userdate($allocation->timeend), $result['program_enddate']);
        $this->assertSame(userdate($allocation->timecompleted), $result['allocation_completeddate']);
    }

    public function test_get_notifier(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $this->setUser(null);
        $result = base::get_notifier($program1, $allocation1);
        $this->assertSame(-10, $result->id);

        $this->setUser($user2);
        $result = base::get_notifier($program1, $allocation1);
        $this->assertSame(-10, $result->id);
    }
}
