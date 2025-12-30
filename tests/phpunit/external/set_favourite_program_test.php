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

namespace block_muprogmyoverview\phpunit\external;

use block_muprogmyoverview\external\set_favourite_program;
use tool_muprog\local\program;

/**
 * My programs external API tests.
 *
 * @group       MuTMS
 * @package     block_muprogmyoverview
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \block_muprogmyoverview\external\set_favourite_program
 */
final class set_favourite_program_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $ufservice1 = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($user1->id));
        $ufservice2 = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($user2->id));

        $this->assertFalse($ufservice1->favourite_exists('tool_muprog', 'programs', $program1->id, $syscontext));
        $this->assertFalse($ufservice2->favourite_exists('tool_muprog', 'programs', $program1->id, $syscontext));

        $this->setUser($user1);

        $result = set_favourite_program::execute($program1->id, 1);
        $result = set_favourite_program::validate_parameters(set_favourite_program::execute_returns(), $result);
        $this->assertSame(['warnings' => []], $result);
        $this->assertTrue($ufservice1->favourite_exists('tool_muprog', 'programs', $program1->id, $syscontext));
        $this->assertFalse($ufservice2->favourite_exists('tool_muprog', 'programs', $program1->id, $syscontext));

        $result = set_favourite_program::execute($program1->id, 0);
        $result = set_favourite_program::validate_parameters(set_favourite_program::execute_returns(), $result);
        $this->assertSame(['warnings' => []], $result);
        $this->assertFalse($ufservice1->favourite_exists('tool_muprog', 'programs', $program1->id, $syscontext));
        $this->assertFalse($ufservice2->favourite_exists('tool_muprog', 'programs', $program1->id, $syscontext));
    }
}
