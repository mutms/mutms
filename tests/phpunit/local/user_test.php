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

use tool_mutenancy\local\user;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\tenant;
use tool_mutenancy\local\manager;

/**
 * Multi-tenancy tenant user tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\user
 */
final class user_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @coversNothing
     */
    public function test_userarchetype_constant(): void {
        $this->assertSame('tenantuser', user::ROLESHORTNAME);
    }

    /**
     * @covers ::get_default_capabilities
     */
    public function test_get_default_capabilities(): void {
        $capabilities = user::get_default_capabilities();

        $this->assertSame(['moodle/category:viewcourselist' => CAP_ALLOW], $capabilities);
    }

    /**
     * @covers ::create_role
     */
    public function test_create_role(): void {
        global $DB;

        tenancy::activate();
        $DB->delete_records('role', ['shortname' => user::ROLESHORTNAME]);

        $roleid = user::create_role();
        $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
        $this->assertSame('tenantuser', $role->shortname);
        $this->assertSame('tenantuser', $role->archetype);

        $user = $this->getDataGenerator()->create_user();
        $syscontext = \context_system::instance();
        role_assign($roleid, $user->id, $syscontext->id);

        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        $capabilities = get_default_capabilities('user');
        foreach ($capabilities as $capability => $permission) {
            unassign_capability($capability, $userrole->id, $syscontext);
        }

        $capabilities = user::get_default_capabilities();
        foreach ($capabilities as $capability => $permission) {
            if ($permission == CAP_ALLOW) {
                $this->assertTrue(has_capability($capability, $syscontext, $user));
            } else {
                $this->assertFalse(has_capability($capability, $syscontext, $user));
            }
        }
    }

    /**
     * @covers ::get_role
     */
    public function test_get_role(): void {
        global $DB;

        tenancy::activate();

        $role = user::get_role();
        $this->assertSame(user::ROLESHORTNAME, $role->shortname);
        $this->assertSame(user::ROLESHORTNAME, $role->archetype);

        // UI prevents role deleting, but there are other ways.

        delete_role($role->id);
        $role2 = user::get_role();
        $this->assertSame(user::ROLESHORTNAME, $role2->shortname);
        $this->assertSame(user::ROLESHORTNAME, $role2->archetype);
        $this->assertNotEquals($role->id, $role2->id);

        // UI does not prevent role renaming.

        $DB->set_field('role', 'shortname', 'cyz', ['id' => $role2->id]);
        $role3 = user::get_role();
        $this->assertSame(user::ROLESHORTNAME, $role3->shortname);
        $this->assertSame(user::ROLESHORTNAME, $role3->archetype);
        $this->assertNotEquals($role2->id, $role3->id);
    }

    /**
     * @covers ::delete_role
     */
    public function test_delete_role(): void {
        global $DB;

        tenancy::activate();
        $role = user::get_role();

        user::delete_role();
        $this->assertFalse($DB->record_exists('role', ['id' => $role->id]));
        $this->assertFalse($DB->record_exists('role', ['shortname' => user::ROLESHORTNAME]));
    }

    /**
     * Assert that user is tenant user.
     *
     * @param int $userid
     * @param \stdClass $tenant
     */
    protected function assert_is_user(int $userid, \stdClass $tenant): void {
        global $DB;

        $role = user::get_role();

        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $userid]));
        $categorycontext = \context_coursecat::instance($tenant->categoryid);
        $this->assertTrue(user_has_role_assignment($userid, $role->id, $categorycontext->id));
    }

    /**
     * Assert that user is NOT tenant user.
     *
     * @param int $userid
     * @param \stdClass $tenant
     */
    protected function assert_is_not_user(int $userid, \stdClass $tenant): void {
        global $DB;

        $role = user::get_role();

        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $userid]));
        $categorycontext = \context_coursecat::instance($tenant->categoryid);
        $this->assertFalse(user_has_role_assignment($userid, $role->id, $categorycontext->id));
    }

    /**
     * @covers ::sync
     */
    public function test_sync(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenant3 = $generator->create_tenant([]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id]);

        cohort_add_member($cohort1->id, $user0->id);
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort2->id, $user0->id);

        $this->assert_is_user($user0->id, $tenant1);
        $this->assert_is_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_user($user1->id, $tenant1);
        $this->assert_is_not_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        // Nothing should change after sync.

        $prevcms = $DB->get_records('cohort_members', [], 'id ASC');
        $prevras = $DB->get_records('role_assignments', [], 'id ASC');
        user::sync(null, null);
        user::sync($tenant1->id, $user0->id);
        $cms = $DB->get_records('cohort_members', [], 'id ASC');
        $this->assertEquals($prevcms, $cms);
        $ras = $DB->get_records('role_assignments', [], 'id ASC');
        $this->assertEquals($prevras, $ras);

        // Restore everything.

        $DB->delete_records('cohort_members', ['cohortid' => $tenant1->cohortid]);
        $DB->delete_records('cohort_members', ['cohortid' => $tenant2->cohortid]);
        $DB->delete_records('cohort_members', ['cohortid' => $tenant3->cohortid]);
        role_unassign_all(['component' => 'tool_mutenancy']);

        user::sync($tenant3->id, null);

        $this->assert_is_not_user($user0->id, $tenant1);
        $this->assert_is_not_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_not_user($user1->id, $tenant1);
        $this->assert_is_not_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_not_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        user::sync(null, $user0->id);

        $this->assert_is_user($user0->id, $tenant1);
        $this->assert_is_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_not_user($user1->id, $tenant1);
        $this->assert_is_not_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_not_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        user::sync(null, null);

        $this->assert_is_user($user0->id, $tenant1);
        $this->assert_is_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_user($user1->id, $tenant1);
        $this->assert_is_not_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        // Drop stale.

        $DB->delete_records('cohort_members', ['cohortid' => $cohort1->id, 'userid' => $user0->id]);
        user::sync(null, null);

        $this->assert_is_not_user($user0->id, $tenant1);
        $this->assert_is_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_user($user1->id, $tenant1);
        $this->assert_is_not_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        $DB->set_field('user', 'deleted', 1, ['id' => $user0->id]);
        user::sync(null, null);

        $this->assert_is_not_user($user0->id, $tenant1);
        $this->assert_is_not_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_user($user1->id, $tenant1);
        $this->assert_is_not_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        $DB->set_field('user', 'tenantid', $tenant2->id, ['id' => $user1->id]);
        user::sync(null, null);

        $this->assert_is_not_user($user0->id, $tenant1);
        $this->assert_is_not_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_not_user($user1->id, $tenant1);
        $this->assert_is_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        $DB->set_field('user', 'deleted', 1, ['id' => $user2->id]);
        user::sync(null, null);

        $this->assert_is_not_user($user0->id, $tenant1);
        $this->assert_is_not_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_not_user($user1->id, $tenant1);
        $this->assert_is_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_not_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);

        $role = user::get_role();
        $DB->delete_records('role', ['id' => $role->id]);
        user::sync(null, null);

        $this->assert_is_not_user($user0->id, $tenant1);
        $this->assert_is_not_user($user0->id, $tenant2);
        $this->assert_is_not_user($user0->id, $tenant3);

        $this->assert_is_not_user($user1->id, $tenant1);
        $this->assert_is_user($user1->id, $tenant2);
        $this->assert_is_not_user($user1->id, $tenant3);

        $this->assert_is_not_user($user2->id, $tenant1);
        $this->assert_is_not_user($user2->id, $tenant2);
        $this->assert_is_not_user($user2->id, $tenant3);

        $this->assert_is_not_user($user3->id, $tenant1);
        $this->assert_is_not_user($user3->id, $tenant2);
        $this->assert_is_user($user3->id, $tenant3);
    }

    /**
     * @covers ::cohort_member_added
     */
    public function test_cohort_member_added(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $categorycontext1 = \context_coursecat::instance($tenant1->categoryid);
        $categorycontext2 = \context_coursecat::instance($tenant2->categoryid);

        $role = user::get_role();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        cohort_add_member($cohort1->id, $user0->id);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user0->id]));
        $this->assertTrue(user_has_role_assignment($user0->id, $role->id, $categorycontext1->id));

        cohort_add_member($cohort2->id, $user1->id);
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant2->cohortid, 'userid' => $user1->id]));
        $this->assertFalse(user_has_role_assignment($user1->id, $role->id, $categorycontext2->id));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user1->id]));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $categorycontext1->id));
    }

    /**
     * @covers ::cohort_member_removed
     */
    public function test_cohort_member_removed(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $categorycontext1 = \context_coursecat::instance($tenant1->categoryid);
        $categorycontext2 = \context_coursecat::instance($tenant2->categoryid);

        $role = user::get_role();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        cohort_add_member($cohort1->id, $user0->id);
        cohort_add_member($cohort2->id, $user1->id);

        cohort_remove_member($cohort1->id, $user0->id);
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user0->id]));
        $this->assertFalse(user_has_role_assignment($user0->id, $role->id, $categorycontext1->id));

        cohort_remove_member($cohort2->id, $user1->id);
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user1->id]));
        $this->assertTrue(user_has_role_assignment($user1->id, $role->id, $categorycontext1->id));
    }

    /**
     * @covers ::allocate_user
     */
    public function test_allocate_user(): void {
        global $DB;

        tenancy::activate();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1 = tenant::create($data);
        $data = (object)[
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
        ];
        $tenant2 = tenant::create($data);
        $data = (object)[
            'name' => 'Some tenant 3',
            'idnumber' => 't3',
            'archived' => 1,
        ];
        $tenant3 = tenant::create($data);
        $this->assertSame('1', $tenant3->archived);

        $admin = get_admin();
        $guest = guest_user();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $user0 = user::allocate($user0->id, $tenant1->id);
        $this->assertSame($tenant1->id, $user0->tenantid);
        $this->assertSame('0', $user0->suspended);
        $usercontext0 = \context_user::instance($user0->id);
        $this->assertSame((int)$tenant1->id, $usercontext0->tenantid);

        $user0 = user::allocate($user0->id, $tenant2->id);
        $this->assertSame($tenant2->id, $user0->tenantid);
        $this->assertSame('0', $user0->suspended);
        $usercontext0 = \context_user::instance($user0->id);
        $this->assertSame((int)$tenant2->id, $usercontext0->tenantid);

        $user0 = user::allocate($user0->id, null);
        $this->assertSame(null, $user0->tenantid);
        $this->assertSame('0', $user0->suspended);
        $usercontext0 = \context_user::instance($user0->id);
        $this->assertSame(null, $usercontext0->tenantid);

        manager::add($tenant1->id, $user0->id);
        manager::add($tenant2->id, $user0->id);
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant2->id, 'userid' => $user0->id]));

        $user0 = user::allocate($user0->id, $tenant1->id);
        $this->assertSame($tenant1->id, $user0->tenantid);
        $this->assertSame('0', $user0->suspended);
        $usercontext0 = \context_user::instance($user0->id);
        $this->assertSame((int)$tenant1->id, $usercontext0->tenantid);
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id]));
        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant2->id, 'userid' => $user0->id]));
        $this->assertTrue(has_capability('tool/mutenancy:membercreate', \context_tenant::instance($tenant1->id), $user0));
        $this->assertFalse(has_capability('tool/mutenancy:membercreate', \context_tenant::instance($tenant2->id), $user0));

        $user0 = user::allocate($user0->id, $tenant2->id);
        $this->assertSame($tenant2->id, $user0->tenantid);
        $this->assertSame('0', $user0->suspended);
        $usercontext0 = \context_user::instance($user0->id);
        $this->assertSame((int)$tenant2->id, $usercontext0->tenantid);
        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id]));
        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant2->id, 'userid' => $user0->id]));
        $this->assertFalse(has_capability('tool/mutenancy:membercreate', \context_tenant::instance($tenant1->id), $user0));
        $this->assertFalse(has_capability('tool/mutenancy:membercreate', \context_tenant::instance($tenant2->id), $user0));

        $user1 = user::allocate($user1->id, $tenant3->id);
        $this->assertSame($tenant3->id, $user1->tenantid);
        $this->assertSame('0', $user1->suspended);

        $user1 = user::allocate($user1->id, $tenant1->id);
        $this->assertSame($tenant1->id, $user1->tenantid);
        $this->assertSame('0', $user1->suspended);

        try {
            user::allocate($admin->id, $tenant1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Admins cannot be tenant members', $ex->getMessage());
        }

        try {
            user::allocate($guest->id, $tenant1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Guest cannot be a tenant member', $ex->getMessage());
        }
    }

    /**
     * @covers ::count_users
     */
    public function test_count_users(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenant3 = $generator->create_tenant([]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        cohort_add_member($cohort1->id, $user0->id);

        $this->assertSame(2, user::count_users($tenant1->id));
        $this->assertSame(1, user::count_users($tenant2->id));
        $this->assertSame(0, user::count_users($tenant3->id));
        $this->assertSame(0, user::count_users(0));
    }

    /**
     * @covers ::count_members
     */
    public function test_count_members(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenant3 = $generator->create_tenant([]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        cohort_add_member($cohort1->id, $user0->id);

        $this->assertSame(1, user::count_members($tenant1->id));
        $this->assertSame(1, user::count_members($tenant2->id));
        $this->assertSame(0, user::count_members($tenant3->id));
        $this->assertSame(0, user::count_members(0));
    }

    /**
     * @covers ::get_associated_tenants
     */
    public function test_get_associated_tenants(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenant3 = $generator->create_tenant([]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->assertEquals([], user::get_associated_tenants($user0->id));

        cohort_add_member($cohort1->id, $user0->id);
        $expected = [$tenant1->id => $tenant1];
        $this->assertEquals($expected, user::get_associated_tenants($user0->id));

        cohort_add_member($cohort2->id, $user0->id);
        $expected = [$tenant1->id => $tenant1, $tenant2->id => $tenant2];
        $this->assertEquals($expected, user::get_associated_tenants($user0->id));

        $this->assertEquals([], user::get_associated_tenants($user1->id));

        cohort_add_member($cohort2->id, $user1->id);
        $this->assertEquals([], user::get_associated_tenants($user1->id));
    }
}
