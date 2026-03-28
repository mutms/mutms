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
 * Multi-tenancy upstream patch test.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core_role_admins_potential_selector
 */
final class core_role_admins_potential_selector_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::find_users
     */
    public function test_find_users(): void {
        global $CFG;
        require_once("$CFG->dirroot/admin/roles/classes/admins_potential_selector.php");

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'user0@example.com',
        ]);

        $selector = new \core_role_admins_potential_selector('xxx', []);
        $result = $selector->find_users('');
        $this->assertSame('First', $result['Potential users'][$user0->id]->firstname);
        $this->assertCount(1, $result['Potential users']);

        $result = $selector->find_users('User');
        $this->assertSame('First', $result['Potential users matching \'User\''][$user0->id]->firstname);
        $this->assertCount(1, $result['Potential users matching \'User\'']);

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'Tenant',
            'lastname' => 'User',
            'email' => 'tenant1@example.com',
            'tenantid' => $tenant1->id,
        ]);

        $selector = new \core_role_admins_potential_selector('xxx', []);
        $result = $selector->find_users('');
        $this->assertSame('First', $result['Potential users'][$user0->id]->firstname);
        $this->assertCount(1, $result['Potential users']);
    }
}
