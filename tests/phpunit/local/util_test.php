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

namespace block_muprogmyoverview\phpunit\local;

use block_muprogmyoverview\local\util;
use tool_muprog\local\program;

/**
 * My programs page helper tests.
 *
 * @group       MuTMS
 * @package     block_muprogmyoverview
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \block_muprogmyoverview\local\util
 */
final class util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_count_active_programs(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program1->id]);
        $allocation1x2 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program2->id]);
        $allocation1x3 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program3->id]);

        $allocation2x1 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation2x2 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program2->id]);

        $program3 = program::archive($program3->id);
        $allocation2x2 = \tool_muprog\local\source\base::allocation_archive($allocation2x2->id);

        $this->setUser($user0);
        $this->assertSame(0, util::count_active_programs());

        $this->setUser($user1);
        $this->assertSame(2, util::count_active_programs());

        $this->setUser($user2);
        $this->assertSame(1, util::count_active_programs());
    }

    public function test_get_hidden_programs_on_timeline(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program1->id]);
        $allocation1x2 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program2->id]);
        $allocation1x3 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program3->id]);

        $allocation2x1 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation2x2 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program2->id]);

        $program3 = program::archive($program3->id);
        $allocation2x2 = \tool_muprog\local\source\base::allocation_archive($allocation2x2->id);

        $this->setUser($user1);

        $this->assertEqualsCanonicalizing([], util::get_hidden_programs_on_timeline());
        set_user_preference('block_muprogmyoverview_hidden_program_' . $program1->id, 1);
        set_user_preference('block_muprogmyoverview_hidden_program_' . $program2->id, 1);
        $this->assertEqualsCanonicalizing([$program1->id, $program2->id], util::get_hidden_programs_on_timeline());

        $this->setUser($user2);

        set_user_preference('block_muprogmyoverview_hidden_program_' . $program2->id, 1);
        set_user_preference('block_muprogmyoverview_hidden_program_' . $program3->id, 1);
        $this->assertEqualsCanonicalizing([$program2->id, $program3->id], util::get_hidden_programs_on_timeline());
    }

    public function test_cleanup_hidden_programs(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program1->id]);
        $allocation1x2 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program2->id]);
        $allocation1x3 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program3->id]);

        $allocation2x1 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation2x2 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program2->id]);

        $program3 = program::archive($program3->id);
        $allocation2x2 = \tool_muprog\local\source\base::allocation_archive($allocation2x2->id);

        $this->setUser($user1);

        $this->assertEqualsCanonicalizing([], util::get_hidden_programs_on_timeline());
        set_user_preference('block_muprogmyoverview_hidden_program_' . $program1->id, 1);
        set_user_preference('block_muprogmyoverview_hidden_program_' . $program2->id, 1);
        $this->assertEqualsCanonicalizing([$program1->id, $program2->id], util::get_hidden_programs_on_timeline());

        $this->setUser($user2);

        set_user_preference('block_muprogmyoverview_hidden_program_' . $program2->id, 1);
        set_user_preference('block_muprogmyoverview_hidden_program_' . $program3->id, 1);
        $this->assertEqualsCanonicalizing([$program2->id, $program3->id], util::get_hidden_programs_on_timeline());

        util::cleanup_hidden_programs();

        $this->setUser($user1);
        $this->assertEqualsCanonicalizing([$program1->id, $program2->id], util::get_hidden_programs_on_timeline());

        $this->setUser($user2);
        $this->assertEqualsCanonicalizing([$program2->id], util::get_hidden_programs_on_timeline());

        set_user_preference('block_muprogmyoverview_hidden_program_' . $program3->id, 1);
        util::cleanup_hidden_programs(false);
        $this->assertEqualsCanonicalizing([$program2->id, $program3->id], util::get_hidden_programs_on_timeline());
        util::cleanup_hidden_programs(true);
        $this->assertEqualsCanonicalizing([$program2->id], util::get_hidden_programs_on_timeline());
    }
}
