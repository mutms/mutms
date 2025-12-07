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
 * @coversDefaultClass \core_user_external
 */
final class core_user_external_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_users
     */
    public function test_get_users(): void {
        global $CFG;
        require_once("$CFG->dirroot/user/externallib.php");

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $user1 = $this->getDataGenerator()->create_user();

        $this->setAdminUser();

        $result = \core_user_external::get_users([['key' => 'id', 'value' => $user1->id]]);
        $this->assertCount(1, $result['users']);
        $user = reset($result['users']);
        $this->assertArrayNotHasKey('tenantid', $user);

        tenancy::activate();

        $result = \core_user_external::get_users([['key' => 'id', 'value' => $user1->id]]);
        $this->assertCount(1, $result['users']);
        $user = reset($result['users']);
        $this->assertSame(null, $user['tenantid']);

        $tenant = $generator->create_tenant();
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $result = \core_user_external::get_users([['key' => 'id', 'value' => $user2->id]]);
        $this->assertCount(1, $result['users']);
        $user = reset($result['users']);
        $this->assertSame($tenant->id, $user['tenantid']);
    }

    /**
     * @covers ::get_users_by_field
     */
    public function test_get_users_by_field(): void {
        global $CFG;
        require_once("$CFG->dirroot/user/externallib.php");

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $user1 = $this->getDataGenerator()->create_user();

        $this->setAdminUser();

        $result = \core_user_external::get_users_by_field('id', [$user1->id]);
        $this->assertCount(1, $result);
        $user = reset($result);
        $this->assertArrayNotHasKey('tenantid', $user);

        tenancy::activate();

        $result = \core_user_external::get_users_by_field('id', [$user1->id]);
        $this->assertCount(1, $result);
        $user = reset($result);
        $this->assertSame(null, $user['tenantid']);

        $tenant = $generator->create_tenant();
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $result = \core_user_external::get_users_by_field('id', [$user2->id]);
        $this->assertCount(1, $result);
        $user = reset($result);
        $this->assertSame($tenant->id, $user['tenantid']);
    }

    /**
     * @covers ::create_users
     */
    public function test_create_users(): void {
        global $CFG, $DB;
        require_once("$CFG->dirroot/user/externallib.php");

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $this->setAdminUser();

        $CFG->passwordpolicy = 0;

        $user0 = [
            'username' => 'usernametest0',
            'firstname' => 'First 0',
            'lastname' => 'Last 0',
            'email' => 'usertest0@example.com',
            'password' => 'abc',
        ];
        $user1 = [
            'username' => 'usernametest1',
            'firstname' => 'First 1',
            'lastname' => 'Last 1',
            'email' => 'usertest1@example.com',
            'password' => 'abc',
            'tenantid' => $tenant1->id,
        ];
        $user2 = [
            'username' => 'usernametest2',
            'firstname' => 'First 2',
            'lastname' => 'Last 2',
            'email' => 'usertest2@example.com',
            'password' => 'abc',
            'tenantid' => $tenant2->id,
        ];

        $result = \core_user_external::create_users([$user0, $user1, $user2]);
        $result = \core_user_external::validate_parameters(\core_user_external::create_users_returns(), $result);
        $this->assertCount(3, $result);
        $user0 = $DB->get_record('user', ['username' => $user0['username']]);
        $user1 = $DB->get_record('user', ['username' => $user1['username']]);
        $user2 = $DB->get_record('user', ['username' => $user2['username']]);
        $this->assertSame(null, $user0->tenantid);
        $this->assertSame($tenant1->id, $user1->tenantid);
        $this->assertSame($tenant2->id, $user2->tenantid);
    }
}
