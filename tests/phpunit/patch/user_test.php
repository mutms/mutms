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

namespace tool_mutenancy\phpunit\patch;

use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy tests for upstream modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers \core\user::require_active_user
     */
    public function test_require_active_user(): void {
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant(['archived' => 1]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user4 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'suspended' => 1]);

        \core\user::require_active_user($user0, false);
        \core\user::require_active_user($user1, false);
        \core\user::require_active_user($user2, false);
        \core\user::require_active_user($user3, false);
        \core\user::require_active_user($user4, false);

        \core\user::require_active_user($user0, true);
        \core\user::require_active_user($user1, true);
        try {
            \core\user::require_active_user($user2, true);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Suspended account', $ex->getMessage());
        }
        try {
            \core\user::require_active_user($user3, true);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Suspended account', $ex->getMessage());
        }
        try {
            \core\user::require_active_user($user3, true);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Suspended account', $ex->getMessage());
        }
    }
}
