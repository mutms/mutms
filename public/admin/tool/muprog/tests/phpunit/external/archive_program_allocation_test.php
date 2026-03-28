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

namespace tool_muprog\phpunit\external;

use tool_muprog\external\archive_program_allocation;
use core\exception\invalid_parameter_exception;
use core\exception\moodle_exception;

/**
 * Program allocation archiving web services tests.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\archive_program_allocation
 */
final class archive_program_allocation_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $categotycontext1 = \context_coursecat::instance($category1->id);
        $categotycontext2 = \context_coursecat::instance($category2->id);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:deallocate', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $user0->id, $categotycontext1->id);

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['contextid' => $categotycontext1->id]);
        $program2 = $generator->create_program(['contextid' => $categotycontext2->id]);

        $allocation1 = $generator->create_program_allocation(['programid' => $program1->id, 'userid' => $user1->id]);
        $allocation2 = $generator->create_program_allocation(['programid' => $program2->id, 'userid' => $user2->id]);

        $this->setUser($user0);

        $result = archive_program_allocation::clean_returnvalue(
            archive_program_allocation::execute_returns(),
            archive_program_allocation::execute($program1->id, $user1->id)
        );

        $result = (object)$result;
        $this->assertSame((int)$allocation1->id, $result->id);
        $this->assertSame((int)$user1->id, $result->userid);
        $this->assertSame(true, $result->archived);
        $this->assertSame(true, $result->deletepossible);
        $this->assertSame(false, $result->archivepossible);
        $this->assertSame(true, $result->restorepossible);
        $this->assertSame(false, $result->editpossible);

        try {
            archive_program_allocation::execute($program1->id, $user1->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Allocation cannot be archived)', $ex->getMessage());
        }

        try {
            archive_program_allocation::execute($program2->id, $user2->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame('Sorry, but you do not currently have permissions to do that (De-allocate users from programs).', $ex->getMessage());
        }

        try {
            archive_program_allocation::execute($program1->id, $user2->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $ex);
        }
    }
}
