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

namespace tool_mutenancy\phpunit\local;

use tool_mutenancy\local\manager;
use tool_mutenancy\local\tenancy;
use stdClass;

/**
 * Multi-tenancy tenant manager tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\manager
 */
final class manager_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @coversNothing
     */
    public function test_managerarchetype_constant(): void {
        $this->assertSame('tenantmanager', \tool_mutenancy\local\manager::ROLESHORTNAME);
    }

    /**
     * @covers ::get_default_capabilities
     */
    public function test_get_default_capabilities(): void {
        $capabilities = manager::get_default_capabilities();

        $this->assertSame(CAP_ALLOW, $capabilities['tool/mutenancy:view']);
        $this->assertSame(CAP_ALLOW, $capabilities['tool/mutenancy:configauth']);
        $this->assertSame(CAP_ALLOW, $capabilities['tool/mutenancy:configappearance']);
        $this->assertSame(CAP_ALLOW, $capabilities['tool/mutenancy:membercreate']);
        $this->assertSame(CAP_ALLOW, $capabilities['tool/mutenancy:memberupdate']);
        $this->assertSame(CAP_ALLOW, $capabilities['tool/mutenancy:memberdelete']);
        $this->assertSame(CAP_ALLOW, $capabilities['moodle/site:viewuseridentity']);

        $this->assertArrayNotHasKey('tool/mutenancy:admin', $capabilities);
        $this->assertArrayNotHasKey('tool/mutenancy:switch', $capabilities);
        $this->assertArrayNotHasKey('moodle/site:config', $capabilities);
        $this->assertArrayNotHasKey('moodle/site:configview', $capabilities);
        $this->assertArrayNotHasKey('moodle/user:editprofile', $capabilities);
        $this->assertArrayNotHasKey('moodle/user:update', $capabilities);
        $this->assertArrayNotHasKey('moodle/user:delete', $capabilities);
    }

    /**
     * @covers ::create_role
     */
    public function test_create_role(): void {
        global $DB;

        tenancy::activate();
        $DB->delete_records('role', ['shortname' => manager::ROLESHORTNAME]);

        $roleid = manager::create_role();
        $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
        $this->assertSame('tenantmanager', $role->shortname);
        $this->assertSame('tenantmanager', $role->archetype);

        $user = $this->getDataGenerator()->create_user();
        $syscontext = \context_system::instance();
        role_assign($roleid, $user->id, $syscontext->id);

        $capabilities = manager::get_default_capabilities();
        foreach ($capabilities as $capability => $permission) {
            if ($permission == CAP_ALLOW) {
                $this->assertTrue(has_capability($capability, $syscontext, $user));
            }
        }

        $this->assertFalse(has_capability('tool/mutenancy:admin', $syscontext, $user));
        $this->assertFalse(has_capability('tool/mutenancy:switch', $syscontext, $user));
        $this->assertFalse(has_capability('moodle/site:config', $syscontext, $user));
        $this->assertFalse(has_capability('moodle/site:configview', $syscontext, $user));
        $this->assertFalse(has_capability('moodle/user:editprofile', $syscontext, $user));
        $this->assertFalse(has_capability('moodle/user:update', $syscontext, $user));
        $this->assertFalse(has_capability('moodle/user:delete', $syscontext, $user));
    }

    /**
     * @covers ::get_role
     */
    public function test_get_role(): void {
        global $DB;

        tenancy::activate();

        $role = manager::get_role();
        $this->assertSame(manager::ROLESHORTNAME, $role->shortname);
        $this->assertSame(manager::ROLESHORTNAME, $role->archetype);

        // UI prevents role deleting, but there are other ways.

        delete_role($role->id);
        $role2 = manager::get_role();
        $this->assertSame(manager::ROLESHORTNAME, $role2->shortname);
        $this->assertSame(manager::ROLESHORTNAME, $role2->archetype);
        $this->assertNotEquals($role->id, $role2->id);

        // UI does not prevent role renaming.

        $DB->set_field('role', 'shortname', 'cyz', ['id' => $role2->id]);
        $role3 = manager::get_role();
        $this->assertSame(manager::ROLESHORTNAME, $role3->shortname);
        $this->assertSame(manager::ROLESHORTNAME, $role3->archetype);
        $this->assertNotEquals($role2->id, $role3->id);
    }

    /**
     * @covers ::delete_role
     */
    public function test_delete_role(): void {
        global $DB;

        tenancy::activate();
        $role = manager::get_role();

        manager::delete_role();
        $this->assertFalse($DB->record_exists('role', ['id' => $role->id]));
        $this->assertFalse($DB->record_exists('role', ['shortname' => manager::ROLESHORTNAME]));
    }

    /**
     * @covers ::add
     */
    public function test_add(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $role = manager::get_role();

        $this->setAdminUser();
        $admin = get_admin();

        $this->setCurrentTimeStart();
        $this->assertTrue(manager::add($tenant1->id, $user0->id));
        $record = $DB->get_record('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id], '*', MUST_EXIST);
        $this->assertSame($admin->id, $record->usercreated);
        $this->assertTimeCurrent($record->timecreated);
        $this->assertTrue(user_has_role_assignment($user0->id, $role->id, $tenantcontext1->id));
        $this->assertTrue(user_has_role_assignment($user0->id, $role->id, $catcontext1->id));

        $this->setCurrentTimeStart();
        $this->assertTrue(manager::add($tenant1->id, $user1->id));
        $record = $DB->get_record('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame($admin->id, $record->usercreated);
        $this->assertTimeCurrent($record->timecreated);
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $tenantcontext1->id));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $catcontext1->id));

        $this->assertDebuggingNotCalled();
        $this->assertFalse(manager::add($tenant1->id, $user2->id));
        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user2->id]));
        $this->assertFalse(user_has_role_assignment($user2->id, $role->id, $tenantcontext1->id));
        $this->assertFalse(user_has_role_assignment($user2->id, $role->id, $catcontext1->id));
        $this->assertDebuggingCalled('tenant members cannot be added to another tenant as managers');
    }

    /**
     * @covers ::remove
     */
    public function test_remove(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $role = manager::get_role();

        $this->assertTrue(manager::add($tenant1->id, $user0->id));
        $this->assertTrue(manager::add($tenant1->id, $user1->id));
        $this->assertTrue(manager::add($tenant2->id, $user2->id));

        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant2->id, 'userid' => $user2->id]));
        $this->assertTrue(user_has_role_assignment($user0->id, $role->id, $tenantcontext1->id));
        $this->assertTrue(user_has_role_assignment($user0->id, $role->id, $catcontext1->id));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $tenantcontext1->id));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $catcontext1->id));
        $this->assertTrue(user_has_role_assignment($user2->id, $role->id, $tenantcontext2->id));
        $this->assertTrue(user_has_role_assignment($user2->id, $role->id, $catcontext2->id));

        manager::remove($tenant1->id, $user2->id);
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant2->id, 'userid' => $user2->id]));
        $this->assertTrue(user_has_role_assignment($user0->id, $role->id, $tenantcontext1->id));
        $this->assertTrue(user_has_role_assignment($user0->id, $role->id, $catcontext1->id));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $tenantcontext1->id));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $catcontext1->id));
        $this->assertTrue(user_has_role_assignment($user2->id, $role->id, $tenantcontext2->id));
        $this->assertTrue(user_has_role_assignment($user2->id, $role->id, $catcontext2->id));

        manager::remove($tenant1->id, $user0->id);
        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant2->id, 'userid' => $user2->id]));
        $this->assertFalse(user_has_role_assignment($user0->id, $role->id, $tenantcontext1->id));
        $this->assertFalse(user_has_role_assignment($user0->id, $role->id, $catcontext1->id));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $tenantcontext1->id));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $catcontext1->id));
        $this->assertTrue(user_has_role_assignment($user2->id, $role->id, $tenantcontext2->id));
        $this->assertTrue(user_has_role_assignment($user2->id, $role->id, $catcontext2->id));
    }

    /**
     * Assert that user is tenant manager.
     *
     * @param int $userid
     * @param \stdClass $tenant
     */
    protected function assert_is_manager(int $userid, \stdClass $tenant): void {
        global $DB;

        $role = manager::get_role();

        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant->id, 'userid' => $userid]));
        $tenantcontext = \context_tenant::instance($tenant->id);
        $categorycontext = \context_coursecat::instance($tenant->categoryid);
        $this->assertTrue(user_has_role_assignment($userid, $role->id, $tenantcontext->id));
        $this->assertTrue(user_has_role_assignment($userid, $role->id, $categorycontext->id));
    }

    /**
     * Assert that user is NOT tenant manager.
     *
     * @param int $userid
     * @param \stdClass $tenant
     */
    protected function assert_is_not_manager(int $userid, \stdClass $tenant): void {
        global $DB;

        $role = manager::get_role();

        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant->id, 'userid' => $userid]));
        $tenantcontext = \context_tenant::instance($tenant->id);
        $categorycontext = \context_coursecat::instance($tenant->categoryid);
        $this->assertFalse(user_has_role_assignment($userid, $role->id, $tenantcontext->id));
        $this->assertFalse(user_has_role_assignment($userid, $role->id, $categorycontext->id));
    }

    /**
     * @covers ::set_userids
     */
    public function test_set_userids(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $role = manager::get_role();

        manager::set_userids($tenant1->id, [$user0->id]);
        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_not_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant2);

        manager::set_userids($tenant1->id, [$user0->id, $user1->id]);
        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant2);

        manager::set_userids($tenant1->id, [$user1->id]);
        $this->assert_is_not_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant2);

        manager::set_userids($tenant2->id, [$user2->id]);
        $this->assert_is_not_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user2->id, $tenant2);

        manager::set_userids($tenant1->id, []);
        $this->assert_is_not_manager($user0->id, $tenant1);
        $this->assert_is_not_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user2->id, $tenant2);
    }

    /**
     * @covers ::sync
     */
    public function test_sync(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user();

        $this->assertTrue(manager::add($tenant1->id, $user0->id));
        $this->assertTrue(manager::add($tenant1->id, $user1->id));
        $this->assertTrue(manager::add($tenant2->id, $user2->id));
        $this->assertTrue(manager::add($tenant1->id, $user3->id));

        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user3->id, $tenant1);
        $this->assert_is_not_manager($user0->id, $tenant2);
        $this->assert_is_not_manager($user1->id, $tenant2);
        $this->assert_is_manager($user2->id, $tenant2);
        $this->assert_is_not_manager($user3->id, $tenant2);

        // Nothing should change.

        $prevmans = $DB->get_records('tool_mutenancy_manager', [], 'id ASC');
        $prevras = $DB->get_records('role_assignments', [], 'id ASC');
        manager::sync();
        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user3->id, $tenant1);
        $this->assert_is_not_manager($user0->id, $tenant2);
        $this->assert_is_not_manager($user1->id, $tenant2);
        $this->assert_is_manager($user2->id, $tenant2);
        $this->assert_is_not_manager($user3->id, $tenant2);
        $mans = $DB->get_records('tool_mutenancy_manager', [], 'id ASC');
        $this->assertEquals($prevmans, $mans);
        $ras = $DB->get_records('role_assignments', [], 'id ASC');
        $this->assertEquals($prevras, $ras);

        // Remove deleted managers and managers from other tenants.

        $DB->set_field('user', 'deleted', '1', ['id' => $user2->id]);
        manager::sync();
        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user3->id, $tenant1);
        $this->assert_is_not_manager($user0->id, $tenant2);
        $this->assert_is_not_manager($user1->id, $tenant2);
        $this->assert_is_not_manager($user2->id, $tenant2);
        $this->assert_is_not_manager($user3->id, $tenant2);

        $DB->set_field('user', 'tenantid', $tenant2->id, ['id' => $user1->id]);
        manager::sync();
        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_not_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user3->id, $tenant1);
        $this->assert_is_not_manager($user0->id, $tenant2);
        $this->assert_is_not_manager($user1->id, $tenant2);
        $this->assert_is_not_manager($user2->id, $tenant2);
        $this->assert_is_not_manager($user3->id, $tenant2);

        // Undo changes and delete managers.

        $DB->set_field('user', 'tenantid', $tenant1->id, ['id' => $user1->id]);
        $DB->set_field('user', 'deleted', '0', ['id' => $user2->id]);
        $DB->delete_records('tool_mutenancy_manager', []);
        manager::sync();
        $this->assert_is_not_manager($user0->id, $tenant1);
        $this->assert_is_not_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_not_manager($user3->id, $tenant1);
        $this->assert_is_not_manager($user0->id, $tenant2);
        $this->assert_is_not_manager($user1->id, $tenant2);
        $this->assert_is_not_manager($user2->id, $tenant2);
        $this->assert_is_not_manager($user3->id, $tenant2);

        // Add missing roles.

        $this->assertTrue(manager::add($tenant1->id, $user0->id));
        $this->assertTrue(manager::add($tenant1->id, $user1->id));
        $this->assertTrue(manager::add($tenant2->id, $user2->id));
        $this->assertTrue(manager::add($tenant1->id, $user3->id));

        role_unassign_all(['component' => 'tool_mutenancy']);
        manager::sync();
        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user3->id, $tenant1);
        $this->assert_is_not_manager($user0->id, $tenant2);
        $this->assert_is_not_manager($user1->id, $tenant2);
        $this->assert_is_manager($user2->id, $tenant2);
        $this->assert_is_not_manager($user3->id, $tenant2);

        $role = manager::get_role();
        $DB->delete_records('role', ['id' => $role->id]);
        manager::sync(null, null);
        $this->assert_is_manager($user0->id, $tenant1);
        $this->assert_is_manager($user1->id, $tenant1);
        $this->assert_is_not_manager($user2->id, $tenant1);
        $this->assert_is_manager($user3->id, $tenant1);
        $this->assert_is_not_manager($user0->id, $tenant2);
        $this->assert_is_not_manager($user1->id, $tenant2);
        $this->assert_is_manager($user2->id, $tenant2);
        $this->assert_is_not_manager($user3->id, $tenant2);
    }

    /**
     * @covers ::get_manager_users
     */
    public function test_get_manager_users(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => '0',
            'lastname' => '0',
        ]);
        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => '1',
            'lastname' => '1',
            'tenantid' => $tenant1->id,
        ]);
        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => '2',
            'lastname' => '2',
            'tenantid' => $tenant2->id,
        ]);

        $this->assertTrue(manager::add($tenant1->id, $user0->id));

        $result = manager::get_manager_users($tenant1->id);
        $this->assertSame([$user0->id => '0 0'], $result);

        $result = manager::get_manager_users($tenant2->id);
        $this->assertSame([], $result);

        $this->assertTrue(manager::add($tenant1->id, $user1->id));
        $result = manager::get_manager_users($tenant1->id);
        $this->assertSame([$user0->id => '0 0', $user1->id => '1 1'], $result);
    }

    /**
     * @covers ::user_deleted
     */
    public function test_user_deleted(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => '0',
            'lastname' => '0',
        ]);
        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => '1',
            'lastname' => '1',
            'tenantid' => $tenant1->id,
        ]);
        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => '2',
            'lastname' => '2',
            'tenantid' => $tenant2->id,
        ]);

        manager::add($tenant1->id, $user0->id);
        manager::add($tenant1->id, $user1->id);
        manager::add($tenant2->id, $user2->id);
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['userid' => $user1->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['userid' => $user0->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['userid' => $user2->id, 'tenantid' => $tenant2->id]));

        delete_user($user1);
        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['userid' => $user0->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['userid' => $user2->id, 'tenantid' => $tenant2->id]));
    }
}
