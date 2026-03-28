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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\role_util;

/**
 * Date helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\role_util
 */
final class role_util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_contextlevel_roles_menu(): void {
        global $DB;

        $this->assertSame([], role_util::get_contextlevel_roles_menu(CONTEXT_USER));

        $roleid = $this->getDataGenerator()->create_role(['shortname' => 'somerole', 'name' => 'Some role']);
        $manager = $DB->get_record('role', ['shortname' => 'manager']);

        $this->assertSame([$roleid => 'Some role'], role_util::get_contextlevel_roles_menu(CONTEXT_USER));

        $DB->insert_record('role_context_levels', ['roleid' => $manager->id, 'contextlevel' => CONTEXT_USER]);
        $this->assertSame(
            [$manager->id => 'Manager', $roleid => 'Some role'],
            role_util::get_contextlevel_roles_menu(CONTEXT_USER)
        );
    }
}
