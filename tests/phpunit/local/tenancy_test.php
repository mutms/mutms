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

use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy tenancy tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\tenancy
 */
final class tenancy_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::activate
     */
    public function test_activate(): void {
        global $DB;

        tenancy::activate();
        $this->assertSame('1', get_config('tool_mutenancy', 'active'));
        $role = $DB->get_record('role', ['shortname' => 'tenantmanager'], '*', MUST_EXIST);
        $this->assertSame('tenantmanager', $role->archetype);

        $this->assertSame('1', get_config('tool_mutenancy', 'active'));
        $role = $DB->get_record('role', ['shortname' => 'tenantuser'], '*', MUST_EXIST);
        $this->assertSame('tenantuser', $role->archetype);
    }

    /**
     * @covers ::deactivate
     */
    public function test_deactivate(): void {
        global $DB;
        tenancy::activate();

        tenancy::deactivate();
        $this->assertSame('0', get_config('tool_mutenancy', 'active'));
        $role = $DB->get_record('role', ['shortname' => 'tenantmanager']);
        $this->assertSame(false, $role);
        $role = $DB->get_record('role', ['shortname' => 'tenantuser']);
        $this->assertSame(false, $role);
    }

    /**
     * @covers ::is_active
     */
    public function test_is_active(): void {
        if (defined('TEST_MUTENANCY_INIT_ACTIVATE') && constant('TEST_MUTENANCY_INIT_ACTIVATE')) {
            $this->assertTrue(tenancy::is_active());
        } else {
            $this->assertFalse(tenancy::is_active());
        }

        tenancy::activate();
        $this->assertTrue(tenancy::is_active());

        tenancy::deactivate();
        $this->assertFalse(tenancy::is_active());
    }

    /**
     * @covers ::get_user_tenantid
     */
    public function test_get_user_tenantid(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $user0 = $this->getDataGenerator()->create_user();
        $this->assertSame(null, tenancy::get_user_tenantid($user0->id));

        tenancy::activate();
        $this->assertSame(null, tenancy::get_user_tenantid($user0->id));

        $tenant1 = $generator->create_tenant();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $this->assertSame((int)$tenant1->id, tenancy::get_user_tenantid($user1->id));
    }

    /**
     * @covers ::get_current_tenantid, ::force_current_tenantid, ::unforce_current_tenantid
     */
    public function test_get_current_tenantid(): void {
        global $SESSION;

        // phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $this->assertSame(null, tenancy::get_current_tenantid());

        tenancy::activate();
        $this->assertSame(null, tenancy::get_current_tenantid());

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->assertObjectNotHasProperty('tool_mutenancy_tenantid', $SESSION);

        $SESSION->tool_mutenancy_tenantid = 0;
        $this->assertSame(null, tenancy::get_current_tenantid());

        $SESSION->tool_mutenancy_tenantid = null;
        $this->assertSame(null, tenancy::get_current_tenantid());

        $SESSION->tool_mutenancy_tenantid = (int)$tenant1->id;
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        $GLOBALS['USER'] = $user0;
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        unset($SESSION->tool_mutenancy_tenantid);
        $this->assertSame(null, tenancy::get_current_tenantid());

        $GLOBALS['USER'] = $user2;
        $this->assertSame((int)$tenant2->id, tenancy::get_current_tenantid());

        $SESSION->tool_mutenancy_tenantid = (int)$tenant1->id;
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        tenancy::force_current_tenantid($tenant2->id);
        $this->assertSame((int)$tenant2->id, tenancy::get_current_tenantid());
        tenancy::unforce_current_tenantid();
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        tenancy::force_current_tenantid(null);
        $this->assertSame(null, tenancy::get_current_tenantid());
        tenancy::unforce_current_tenantid();
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());
    }

    /**
     * @covers ::can_switch
     */
    public function test_can_switch(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['archived' => 1, 'assoccohortid' => $cohort2->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user4 = $this->getDataGenerator()->create_user();

        $syscontext = \context_system::instance();
        $viewroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutenancy:view', CAP_ALLOW, $viewroleid, $syscontext);
        $switchrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutenancy:switch', CAP_ALLOW, $switchrole, $syscontext);

        $this->setUser($user1);
        $this->assertFalse(tenancy::can_switch());
        role_assign($switchrole, $user1->id, $syscontext->id);
        $this->assertFalse(tenancy::can_switch());
        role_assign($viewroleid, $user1->id, $syscontext->id);
        $this->assertTrue(tenancy::can_switch());

        $this->setUser($user2);
        $this->assertFalse(tenancy::can_switch());
        role_assign($switchrole, $user2->id, $syscontext->id);
        $this->assertFalse(tenancy::can_switch());
        cohort_add_member($cohort1->id, $user2->id);
        $this->assertTrue(tenancy::can_switch());

        $this->setUser($user3);
        $this->assertFalse(tenancy::can_switch());
        role_assign($switchrole, $user3->id, $syscontext->id);
        $this->assertFalse(tenancy::can_switch());
        role_assign($viewroleid, $user3->id, $syscontext->id);
        $this->assertFalse(tenancy::can_switch());
        cohort_add_member($cohort1->id, $user3->id);
        $this->assertFalse(tenancy::can_switch());

        $this->setUser($user4);
        $this->assertFalse(tenancy::can_switch());
        role_assign($switchrole, $user4->id, $syscontext->id);
        $this->assertFalse(tenancy::can_switch());
        cohort_add_member($cohort2->id, $user4->id);
        $this->assertFalse(tenancy::can_switch());
    }

    /**
     * @covers ::switch
     */
    public function test_switch(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cname = tenancy::get_cookie_name();

        tenancy::activate();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setUser(null);
        $this->assertSame(null, tenancy::get_current_tenantid());

        tenancy::switch($tenant1->id);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());
        $this->assertSame($tenant1->idnumber, $_COOKIE[$cname]);

        tenancy::switch($tenant2->id);
        $this->assertSame((int)$tenant2->id, tenancy::get_current_tenantid());
        $this->assertSame($tenant2->idnumber, $_COOKIE[$cname]);

        tenancy::switch(null);
        $this->assertSame(null, tenancy::get_current_tenantid());
        $this->assertSame('0', $_COOKIE[$cname]);

        $this->setUser(guest_user());
        $this->assertSame(null, tenancy::get_current_tenantid());

        tenancy::switch($tenant1->id);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());
        $this->assertSame($tenant1->idnumber, $_COOKIE[$cname]);

        tenancy::switch($tenant2->id);
        $this->assertSame((int)$tenant2->id, tenancy::get_current_tenantid());
        $this->assertSame($tenant2->idnumber, $_COOKIE[$cname]);

        tenancy::switch(null);
        $this->assertSame(null, tenancy::get_current_tenantid());
        $this->assertSame('0', $_COOKIE[$cname]);

        tenancy::switch(null);
        $this->assertSame(null, tenancy::get_current_tenantid());
        $this->assertSame('0', $_COOKIE[$cname]);

        $this->setUser($user0);
        $this->assertSame(null, tenancy::get_current_tenantid());

        tenancy::switch($tenant1->id);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());
        $this->assertSame($tenant1->idnumber, $_COOKIE[$cname]);

        tenancy::switch($tenant2->id);
        $this->assertSame((int)$tenant2->id, tenancy::get_current_tenantid());
        $this->assertSame($tenant2->idnumber, $_COOKIE[$cname]);

        tenancy::switch(null);
        $this->assertSame(null, tenancy::get_current_tenantid());
        $this->assertSame('0', $_COOKIE[$cname]);

        try {
            tenancy::switch(-1);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Invalid tenant id)', $ex->getMessage());
        }

        $this->setUser($user1);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        tenancy::switch($tenant1->id);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());
        $this->assertSame($tenant1->idnumber, $_COOKIE[$cname]);

        try {
            tenancy::switch($tenant2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Tenant members cannot switch tenant', $ex->getMessage());
        }

        try {
            tenancy::switch(null);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Tenant members cannot switch tenant', $ex->getMessage());
        }
    }

    /**
     * @covers ::fix_site
     */
    public function test_fix_site(): void {
        global $SITE, $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();

        $tenant1 = $generator->create_tenant([
            'sitefullname' => 'Site tenant 1',
            'siteshortname' => 'ST1',
        ]);
        $tenant2 = $generator->create_tenant([
            'sitefullname' => 'Site tenant 2',
            'siteshortname' => null,
        ]);
        $tenant3 = $generator->create_tenant([
            'sitefullname' => null,
            'siteshortname' => 'ST3',
        ]);
        $tenant4 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $this->setUser($user1);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        // Reset site.
        $site = $DB->get_record('course', ['category' => 0], '*', MUST_EXIST);
        $GLOBALS['SITE'] = (object)(array)$site;
        $this->assertSame('PHPUnit test site', $site->fullname);
        $this->assertSame('phpunit', $site->shortname);
        $this->assertSame((array)$site, (array)$SITE);

        tenancy::fix_site();
        $this->assertSame($tenant1->sitefullname, $SITE->fullname);
        $this->assertSame($tenant1->siteshortname, $SITE->shortname);

        tenancy::fix_site($tenant1->id);
        $this->assertSame($tenant1->sitefullname, $SITE->fullname);
        $this->assertSame($tenant1->siteshortname, $SITE->shortname);

        tenancy::fix_site($tenant2->id);
        $this->assertSame($tenant2->sitefullname, $SITE->fullname);
        $this->assertSame($tenant2->idnumber, $SITE->shortname);

        tenancy::fix_site($tenant3->id);
        $this->assertSame($tenant3->name, $SITE->fullname);
        $this->assertSame($tenant3->siteshortname, $SITE->shortname);

        tenancy::fix_site($tenant4->id);
        $this->assertSame($tenant4->name, $SITE->fullname);
        $this->assertSame($tenant4->idnumber, $SITE->shortname);

        tenancy::fix_site(null);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        tenancy::fix_site(0);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);
    }

    /**
     * @covers ::set_cookie
     */
    public function test_set_cookie(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();

        $cname = tenancy::get_cookie_name();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setUser(null);
        $this->assertSame('0', $_COOKIE[$cname]);
        $this->assertSame(null, tenancy::get_current_tenantid());
        unset($_COOKIE[$cname]);

        $this->setUser(guest_user());
        $this->assertSame('0', $_COOKIE[$cname]);
        $this->assertSame(null, tenancy::get_current_tenantid());
        unset($_COOKIE[$cname]);

        $this->setUser($user0);
        $this->assertSame('0', $_COOKIE[$cname]);
        $this->assertSame(null, tenancy::get_current_tenantid());

        $this->setUser($user1);
        $this->assertSame($tenant1->idnumber, $_COOKIE[$cname]);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        $this->setUser($user2);
        $this->assertSame($tenant2->idnumber, $_COOKIE[$cname]);
        $this->assertSame((int)$tenant2->id, tenancy::get_current_tenantid());
    }

    /**
     * @covers ::get_related_users_exists
     */
    public function test_get_related_users_exists(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $syscontext = \context_system::instance();
        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $generator->create_tenant();
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $user0x1 = $this->getDataGenerator()->create_user();
        $user0x2 = $this->getDataGenerator()->create_user();
        $user0x3 = $this->getDataGenerator()->create_user();
        $user1x1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2x1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        cohort_add_member($cohort1->id, $user0x2->id);
        cohort_add_member($cohort2->id, $user0x3->id);

        $restriction = tenancy::get_related_users_exists('u.id', $catcontext1);
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.deleted = 0 $restriction
              ORDER BY u.id ASC";
        $result = $DB->get_fieldset_sql($sql);
        $this->assertSame([$user0x2->id, $user1x1->id], $result);

        $restriction = tenancy::get_related_users_exists('u.id', $catcontext1, '');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE $restriction
              ORDER BY u.id ASC";
        $result = $DB->get_fieldset_sql($sql);
        $this->assertSame([$user0x2->id, $user1x1->id], $result);

        $restriction = tenancy::get_related_users_exists('u.id', $catcontext1, 'OR');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.id = {$user0x1->id} $restriction
              ORDER BY u.id ASC";
        $result = $DB->get_fieldset_sql($sql);
        $this->assertSame([$user0x1->id, $user0x2->id, $user1x1->id], $result);

        $restriction = tenancy::get_related_users_exists('u.id', $catcontext2);
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.deleted = 0 $restriction
              ORDER BY u.id ASC";
        $result = $DB->get_fieldset_sql($sql);
        $this->assertSame([$user2x1->id], $result);

        $restriction = tenancy::get_related_users_exists('u.id', $syscontext);
        $this->assertSame('', $restriction);

        $restriction = tenancy::get_related_users_exists('u.id', $syscontext, '');
        $this->assertSame('1=1', $restriction);

        tenancy::force_current_tenantid($tenant1->id);
        $restriction = tenancy::get_related_users_exists('u.id', $syscontext, '');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE $restriction
              ORDER BY u.id ASC";
        $result = $DB->get_fieldset_sql($sql);
        $this->assertSame([$user0x2->id, $user1x1->id], $result);

        $restriction = tenancy::get_related_users_exists('u.id', $catcontext2);
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.deleted = 0 $restriction
              ORDER BY u.id ASC";
        $result = $DB->get_fieldset_sql($sql);
        $this->assertSame([$user2x1->id], $result);
    }

    /**
     * @covers ::callback_lib_setup
     */
    public function test_callback_lib_setup(): void {
        global $SESSION, $SITE, $DB;

        $cname = tenancy::get_cookie_name();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $site = $DB->get_record('course', ['category' => 0], '*', MUST_EXIST);

        tenancy::activate();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setUser(null);
        unset($SESSION->tool_mutenancy_tenantid);
        unset($_COOKIE[$cname]);
        $GLOBALS['SITE'] = (object)(array)$site;
        tenancy::callback_lib_setup();
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame('0', $_COOKIE[$cname]);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        $_COOKIE[$cname] = '0';
        unset($SESSION->tool_mutenancy_tenantid);
        tenancy::callback_lib_setup();
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame('0', $_COOKIE[$cname]);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        $_COOKIE[$cname] = $tenant1->idnumber;
        unset($SESSION->tool_mutenancy_tenantid);
        tenancy::callback_lib_setup();
        $this->assertSame((int)$tenant1->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant1->idnumber, $_COOKIE[$cname]);
        $this->assertSame($tenant1->name, $SITE->fullname);
        $this->assertSame($tenant1->idnumber, $SITE->shortname);

        unset($_COOKIE[$cname]);
        tenancy::callback_lib_setup();
        $this->assertSame((int)$tenant1->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertArrayNotHasKey($cname, $_COOKIE);
        $this->assertSame($tenant1->name, $SITE->fullname);
        $this->assertSame($tenant1->idnumber, $SITE->shortname);

        // Test non-tenant user.

        $this->setUser($user0);
        unset($SESSION->tool_mutenancy_tenantid);
        unset($_COOKIE[$cname]);
        tenancy::callback_lib_setup();
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame('0', $_COOKIE[$cname]);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        $_COOKIE[$cname] = $tenant1->idnumber;
        unset($SESSION->tool_mutenancy_tenantid);
        tenancy::callback_lib_setup();
        $this->assertSame((int)$tenant1->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant1->idnumber, $_COOKIE[$cname]);
        $this->assertSame($tenant1->name, $SITE->fullname);
        $this->assertSame($tenant1->idnumber, $SITE->shortname);

        unset($_COOKIE[$cname]);
        $SESSION->tool_mutenancy_tenantid = (int)$tenant1->id;
        tenancy::callback_lib_setup();
        $this->assertSame((int)$tenant1->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertArrayNotHasKey($cname, $_COOKIE);
        $this->assertSame($tenant1->name, $SITE->fullname);
        $this->assertSame($tenant1->idnumber, $SITE->shortname);

        // Test tenant member.

        $this->setUser($user2);
        unset($SESSION->tool_mutenancy_tenantid);
        unset($_COOKIE[$cname]);
        tenancy::callback_lib_setup();
        $this->assertSame((int)$tenant2->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant2->idnumber, $_COOKIE[$cname]);
        $this->assertSame($tenant2->name, $SITE->fullname);
        $this->assertSame($tenant2->idnumber, $SITE->shortname);

        unset($SESSION->tool_mutenancy_tenantid);
        $_COOKIE[$cname] = $tenant1->idnumber;
        tenancy::callback_lib_setup();
        $this->assertSame((int)$tenant2->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant2->idnumber, $_COOKIE[$cname]);
        $this->assertSame($tenant2->name, $SITE->fullname);
        $this->assertSame($tenant2->idnumber, $SITE->shortname);

        unset($_COOKIE[$cname]);
        tenancy::callback_lib_setup();
        $this->assertSame((int)$tenant2->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertArrayNotHasKey($cname, $_COOKIE);
        $this->assertSame($tenant2->name, $SITE->fullname);
        $this->assertSame($tenant2->idnumber, $SITE->shortname);
    }

    /**
     * @covers ::callback_session_set_user
     */
    public function test_callback_session_set_user(): void {
        global $SESSION, $SITE, $DB, $CFG;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $site = $DB->get_record('course', ['category' => 0], '*', MUST_EXIST);

        tenancy::activate();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $nouser = (object)[
            'id' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ];
        $guestuser = guest_user();

        unset($SESSION->tool_mutenancy_tenantid);

        \core\session\manager::set_user($nouser);
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        \core\session\manager::set_user($user1);
        $this->assertSame((int)$tenant1->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant1->name, $SITE->fullname);
        $this->assertSame($tenant1->idnumber, $SITE->shortname);

        \core\session\manager::set_user($user2);
        $this->assertSame((int)$tenant2->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant2->name, $SITE->fullname);
        $this->assertSame($tenant2->idnumber, $SITE->shortname);

        \core\session\manager::set_user($nouser);
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        \core\session\manager::set_user($user2);
        $this->assertSame((int)$tenant2->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant2->name, $SITE->fullname);
        $this->assertSame($tenant2->idnumber, $SITE->shortname);

        \core\session\manager::set_user($guestuser);
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        \core\session\manager::set_user($nouser);
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        \core\session\manager::set_user($guestuser);
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        unset($SESSION->tool_mutenancy_tenantid);
        \core\session\manager::set_user($guestuser);
        $this->assertSame(0, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        unset($SESSION->tool_mutenancy_tenantid);
        \core\session\manager::set_user($user2);
        $this->assertSame((int)$tenant2->id, $SESSION->tool_mutenancy_tenantid);
        $this->assertSame($tenant2->name, $SITE->fullname);
        $this->assertSame($tenant2->idnumber, $SITE->shortname);
    }

    /**
     * @covers ::callback_login_page
     */
    public function test_callback_login_page(): void {
        global $SESSION, $SITE, $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $site = $DB->get_record('course', ['category' => 0], '*', MUST_EXIST);

        tenancy::activate();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant(['archived' => 1]);

        $user0 = $this->getDataGenerator()->create_user();

        unset($SESSION->tool_mutenancy_tenantid);

        // Nothing should happen if user is already logged in.
        $this->setUser($user0);
        tenancy::callback_login_page();
        $this->assertSame(null, tenancy::get_current_tenantid());
        $_GET['tenant'] = $tenant1->idnumber;
        tenancy::callback_login_page();

        // Not-logged gets redirect and switches tenant.
        $this->setUser(null);
        $this->assertSame(null, tenancy::get_current_tenantid());
        $_GET['tenant'] = $tenant1->idnumber;
        try {
            tenancy::callback_login_page();
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        $_GET['tenant'] = '0';
        try {
            tenancy::callback_login_page();
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $this->assertSame(null, tenancy::get_current_tenantid());
        $this->assertSame($site->fullname, $SITE->fullname);
        $this->assertSame($site->shortname, $SITE->shortname);

        // The same for guests.
        $this->setUser(guest_user());
        $this->setUser(null);
        $this->assertSame(null, tenancy::get_current_tenantid());
        $_GET['tenant'] = $tenant1->idnumber;
        try {
            tenancy::callback_login_page();
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());

        $_GET['tenant'] = '0';
        try {
            tenancy::callback_login_page();
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $this->assertSame(null, tenancy::get_current_tenantid());

        // Archived tenants must be ignored.
        $_GET['tenant'] = $tenant2->idnumber;
        tenancy::callback_login_page();
        $this->assertSame(null, tenancy::get_current_tenantid());
    }
}
