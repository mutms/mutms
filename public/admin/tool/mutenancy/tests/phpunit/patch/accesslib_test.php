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
// phpcs:disable moodle.Files.LineLength.MaxExceeded
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_mutenancy\phpunit\patch;

use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy tests for lib/accesslib.php modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class accesslib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers \context_tenant
     */
    public function test_context_tenant(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant1 = $generator->create_tenant();

        $this->assertSame(12, CONTEXT_TENANT);

        $context2 = \context_tenant::instance($tenant1->id);
        $this->assertInstanceOf(\core\context\tenant::class, $context2);
        $this->assertSame(CONTEXT_TENANT, $context2->contextlevel);
    }

    /**
     * @covers ::has_capability()
     */
    public function test_has_capability(): void {
        global $DB, $CFG;
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();
        $category0 = $DB->get_record('course_categories', ['parent' => 0], '*', MUST_EXIST);

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $categorycontext0  = \context_coursecat::instance($category0->id);
        $categorycontext1  = \context_coursecat::instance($tenant1->categoryid);
        $categorycontext2  = \context_coursecat::instance($tenant2->categoryid);
        $course0 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $coursecontext0 = \context_course::instance($course0->id);
        $course1 = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);
        $coursecontext1 = \context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $tenant2->categoryid]);
        $coursecontext2 = \context_course::instance($course2->id);

        // Any capability will do here, the default user role override.
        $capability = 'moodle/course:view';
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability($capability, CAP_ALLOW, $userrole->id, $syscontext->id);
        $guestrole = $DB->get_record('role', ['shortname' => 'guest'], '*', MUST_EXIST);
        assign_capability($capability, CAP_ALLOW, $guestrole->id, $syscontext->id);

        $admin = get_admin();
        $admincontext = \context_user::instance($admin->id);
        $guest = guest_user();
        $user0 = $this->getDataGenerator()->create_user();
        $usercontext0 = \context_user::instance($user0->id);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usercontext1 = \context_user::instance($user1->id);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $usercontext2 = \context_user::instance($user2->id);

        // System context - no changes.

        $this->setUser($admin);
        $this->assertTrue(has_capability($capability, $syscontext, $admin));
        $this->assertTrue(has_capability($capability, $syscontext, $guest));
        $this->assertTrue(has_capability($capability, $syscontext, $user0));
        $this->assertTrue(has_capability($capability, $syscontext, $user1));
        $this->assertTrue(has_capability($capability, $syscontext, $user2));
        $this->assertTrue(has_capability($capability, $syscontext));
        $this->setUser(null);
        $this->assertTrue(has_capability($capability, $syscontext));
        $this->setUser($guest);
        $this->assertTrue(has_capability($capability, $syscontext));
        $this->setUser($user0);
        $this->assertTrue(has_capability($capability, $syscontext));
        $this->setUser($user1);
        $this->assertTrue(has_capability($capability, $syscontext));
        $this->setUser($user2);
        $this->assertTrue(has_capability($capability, $syscontext));

        // Normal category context - no changes.

        $this->setUser($admin);
        $this->assertTrue(has_capability($capability, $categorycontext0, $admin));
        $this->assertTrue(has_capability($capability, $categorycontext0, $guest));
        $this->assertTrue(has_capability($capability, $categorycontext0, $user0));
        $this->assertTrue(has_capability($capability, $categorycontext0, $user1));
        $this->assertTrue(has_capability($capability, $categorycontext0, $user2));
        $this->assertTrue(has_capability($capability, $categorycontext0));
        $this->setUser(null);
        $this->assertTrue(has_capability($capability, $categorycontext0));
        $this->setUser($guest);
        $this->assertTrue(has_capability($capability, $categorycontext0));
        $this->setUser($user0);
        $this->assertTrue(has_capability($capability, $categorycontext0));
        $this->setUser($user1);
        $this->assertTrue(has_capability($capability, $categorycontext0));
        $this->setUser($user2);
        $this->assertTrue(has_capability($capability, $categorycontext0));

        // Normal user context - no changes.

        $this->setUser($admin);
        $this->assertTrue(has_capability($capability, $usercontext0, $admin));
        $this->assertTrue(has_capability($capability, $usercontext0, $guest));
        $this->assertTrue(has_capability($capability, $usercontext0, $user0));
        $this->assertTrue(has_capability($capability, $usercontext0, $user1));
        $this->assertTrue(has_capability($capability, $usercontext0, $user2));
        $this->assertTrue(has_capability($capability, $usercontext0));
        $this->setUser(null);
        $this->assertTrue(has_capability($capability, $usercontext0));
        $this->setUser($guest);
        $this->assertTrue(has_capability($capability, $usercontext0));
        $this->setUser($user0);
        $this->assertTrue(has_capability($capability, $usercontext0));
        $this->setUser($user1);
        $this->assertTrue(has_capability($capability, $usercontext0));
        $this->setUser($user2);
        $this->assertTrue(has_capability($capability, $usercontext0));

        // Tenant context - restricted.

        $this->setUser($admin);
        $this->assertTrue(has_capability($capability, $tenantcontext1, $admin));
        $this->assertFalse(has_capability($capability, $tenantcontext1, $guest));
        $this->assertTrue(has_capability($capability, $tenantcontext1, $user0));
        $this->assertTrue(has_capability($capability, $tenantcontext1, $user1));
        $this->assertFalse(has_capability($capability, $tenantcontext1, $user2));
        $this->assertTrue(has_capability($capability, $tenantcontext1));
        $this->setUser(null);
        $this->assertFalse(has_capability($capability, $tenantcontext1));
        $this->setUser($guest);
        $this->assertFalse(has_capability($capability, $tenantcontext1));
        $this->setUser($user0);
        $this->assertTrue(has_capability($capability, $tenantcontext1));
        $this->setUser($user1);
        $this->assertTrue(has_capability($capability, $tenantcontext1));
        $this->setUser($user2);
        $this->assertFalse(has_capability($capability, $tenantcontext1));

        // Tenant user context - restricted.

        $this->setUser($admin);
        $this->assertTrue(has_capability($capability, $usercontext1, $admin));
        $this->assertFalse(has_capability($capability, $usercontext1, $guest));
        $this->assertTrue(has_capability($capability, $usercontext1, $user0));
        $this->assertTrue(has_capability($capability, $usercontext1, $user1));
        $this->assertFalse(has_capability($capability, $usercontext1, $user2));
        $this->assertTrue(has_capability($capability, $usercontext1));
        $this->setUser(null);
        $this->assertFalse(has_capability($capability, $usercontext1));
        $this->setUser($guest);
        $this->assertFalse(has_capability($capability, $usercontext1));
        $this->setUser($user0);
        $this->assertTrue(has_capability($capability, $usercontext1));
        $this->setUser($user1);
        $this->assertTrue(has_capability($capability, $usercontext1));
        $this->setUser($user2);
        $this->assertFalse(has_capability($capability, $usercontext1));

        // Tenant course context - restricted.
        $this->setUser($admin);
        $this->assertTrue(has_capability($capability, $coursecontext1, $admin));
        $this->assertFalse(has_capability($capability, $coursecontext1, $guest));
        $this->assertTrue(has_capability($capability, $coursecontext1, $user0));
        $this->assertTrue(has_capability($capability, $coursecontext1, $user1));
        $this->assertFalse(has_capability($capability, $coursecontext1, $user2));
        $this->assertTrue(has_capability($capability, $coursecontext1));
        $this->setUser(null);
        $this->assertFalse(has_capability($capability, $coursecontext1));
        $this->setUser($guest);
        $this->assertFalse(has_capability($capability, $coursecontext1));
        $this->setUser($user0);
        $this->assertTrue(has_capability($capability, $coursecontext1));
        $this->setUser($user1);
        $this->assertTrue(has_capability($capability, $coursecontext1));
        $this->setUser($user2);
        $this->assertFalse(has_capability($capability, $coursecontext1));

        $this->assertSame('0', get_config('tool_mutenancy', 'allowguests'));

        assign_capability('moodle/course:view', CAP_ALLOW, $CFG->guestroleid, $syscontext->id);
        assign_capability('moodle/course:view', CAP_ALLOW, $CFG->notloggedinroleid, $syscontext->id);
        assign_capability('moodle/course:update', CAP_ALLOW, $CFG->guestroleid, $syscontext->id);
        assign_capability('moodle/course:update', CAP_ALLOW, $CFG->notloggedinroleid, $syscontext->id);
        assign_capability('moodle/course:update', CAP_ALLOW, $userrole->id, $syscontext->id);

        $this->setUser();

        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $guest));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $user0));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $user1));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $user2));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext0));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext0, $guest));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext0, $user0));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext0, $user1));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext0, $user2));

        $this->assertfalse(has_capability('moodle/course:view', $coursecontext1));
        $this->assertfalse(has_capability('moodle/course:view', $coursecontext1, $guest));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext1, $user0));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext1, $user1));
        $this->assertfalse(has_capability('moodle/course:view', $coursecontext1, $user2));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext1));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext1, $guest));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext1, $user0));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext1, $user1));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext1, $user2));

        set_config('allowguests', '1', 'tool_mutenancy');

        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $guest));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $user0));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $user1));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext0, $user2));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext0));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext0, $guest));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext0, $user0));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext0, $user1));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext0, $user2));

        $this->assertTrue(has_capability('moodle/course:view', $coursecontext1));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext1, $guest));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext1, $user0));
        $this->assertTrue(has_capability('moodle/course:view', $coursecontext1, $user1));
        $this->assertfalse(has_capability('moodle/course:view', $coursecontext1, $user2));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext1));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext1, $guest));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext1, $user0));
        $this->assertTrue(has_capability('moodle/course:update', $coursecontext1, $user1));
        $this->assertfalse(has_capability('moodle/course:update', $coursecontext1, $user2));
    }

    /**
     * @covers ::get_role_archetypes()
     */
    public function test_get_role_archetypes(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $expected = [
            'manager' => 'manager',
            'coursecreator' => 'coursecreator',
            'editingteacher' => 'editingteacher',
            'teacher' => 'teacher',
            'student' => 'student',
            'guest' => 'guest',
            'user' => 'user',
            'frontpage' => 'frontpage',
        ];
        $this->assertSame($expected, get_role_archetypes());

        tenancy::activate();
        $expected = [
            'manager' => 'manager',
            'coursecreator' => 'coursecreator',
            'editingteacher' => 'editingteacher',
            'teacher' => 'teacher',
            'student' => 'student',
            'guest' => 'guest',
            'user' => 'user',
            'frontpage' => 'frontpage',
            'tenantmanager' => 'tenantmanager',
            'tenantuser' => 'tenantuser',
        ];
        $this->assertSame($expected, get_role_archetypes());
    }

    /**
     * @covers ::get_default_capabilities()
     */
    public function test_get_default_capabilities(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $result = get_default_capabilities('user');
        $this->assertSame(CAP_ALLOW, $result['tool/policy:accept']);

        $result = get_default_capabilities('tenantmanager');
        $this->assertSame([], $result);

        $result = get_default_capabilities('tenantuser');
        $this->assertSame([], $result);

        tenancy::activate();

        $result = get_default_capabilities('user');
        $this->assertSame(CAP_ALLOW, $result['tool/policy:accept']);

        $result = get_default_capabilities('tenantmanager');
        $this->assertSame(CAP_ALLOW, $result['tool/mutenancy:view']);

        $result = get_default_capabilities('tenantuser');
        $this->assertSame(['moodle/category:viewcourselist' => CAP_ALLOW], $result);
    }

    /**
     * @covers ::get_default_role_archetype_allows()
     */
    public function test_get_default_role_archetype_allows(): void {
        global $DB;
        tenancy::activate();

        $tenantmanager = \tool_mutenancy\local\manager::get_role();
        $tenantuser = \tool_mutenancy\local\user::get_role();
        $manager = $DB->get_record('role', ['archetype' => 'manager'], '*', MUST_EXIST);
        $coursecreator = $DB->get_record('role', ['archetype' => 'coursecreator'], '*', MUST_EXIST);
        $editingteacher = $DB->get_record('role', ['archetype' => 'editingteacher'], '*', MUST_EXIST);
        $teacher = $DB->get_record('role', ['archetype' => 'teacher'], '*', MUST_EXIST);
        $student = $DB->get_record('role', ['archetype' => 'student'], '*', MUST_EXIST);
        $guest = $DB->get_record('role', ['archetype' => 'guest'], '*', MUST_EXIST);
        $user = $DB->get_record('role', ['archetype' => 'user'], '*', MUST_EXIST);

        $result = get_default_role_archetype_allows('assign', 'manager');
        $this->assertArrayNotHasKey($tenantmanager->id, $result);
        $this->assertArrayNotHasKey($tenantuser->id, $result);

        $result = get_default_role_archetype_allows('override', 'manager');
        $this->assertArrayHasKey($tenantmanager->id, $result);
        $this->assertArrayHasKey($tenantuser->id, $result);

        $result = get_default_role_archetype_allows('switch', 'manager');
        $this->assertArrayNotHasKey($tenantmanager->id, $result);
        $this->assertArrayNotHasKey($tenantuser->id, $result);

        $result = get_default_role_archetype_allows('view', 'manager');
        $this->assertArrayHasKey($tenantmanager->id, $result);
        $this->assertArrayHasKey($tenantuser->id, $result);

        $result = get_default_role_archetype_allows('assign', 'tenantmanager');
        $expected = [
            $coursecreator->id => $coursecreator->id,
            $editingteacher->id => $editingteacher->id,
            $teacher->id => $teacher->id,
            $student->id => $student->id,
        ];
        $this->assertSame($expected, $result);

        $result = get_default_role_archetype_allows('override', 'tenantmanager');
        $expected = [
            $coursecreator->id => $coursecreator->id,
            $editingteacher->id => $editingteacher->id,
            $teacher->id => $teacher->id,
            $student->id => $student->id,
            $guest->id => $guest->id,
            $user->id => $user->id,
            $tenantuser->id => $tenantuser->id,
        ];
        $this->assertSame($expected, $result);

        $result = get_default_role_archetype_allows('switch', 'tenantmanager');
        $expected = [
            $editingteacher->id => $editingteacher->id,
            $teacher->id => $teacher->id,
            $student->id => $student->id,
        ];
        $this->assertSame($expected, $result);

        $result = get_default_role_archetype_allows('view', 'tenantmanager');
        $expected = [
            $tenantmanager->id => $tenantmanager->id,
            $coursecreator->id => $coursecreator->id,
            $editingteacher->id => $editingteacher->id,
            $teacher->id => $teacher->id,
            $student->id => $student->id,
            $guest->id => $guest->id,
            $user->id => $user->id,
            $tenantuser->id => $tenantuser->id,
        ];
        $this->assertSame($expected, $result);

        $result = get_default_role_archetype_allows('assign', 'tenantuser');
        $this->assertSame([], $result);

        $result = get_default_role_archetype_allows('override', 'tenantuser');
        $this->assertSame([], $result);

        $result = get_default_role_archetype_allows('switch', 'tenantuser');
        $this->assertSame([], $result);

        $result = get_default_role_archetype_allows('view', 'tenantuser');
        $this->assertSame([], $result);
    }

    /**
     * @covers ::get_users_by_capability()
     */
    public function test_get_users_by_capability(): void {
        global $DB;
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();
        $category0 = $DB->get_record('course_categories', ['parent' => 0], '*', MUST_EXIST);

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $categorycontext0  = \context_coursecat::instance($category0->id);
        $categorycontext1  = \context_coursecat::instance($tenant1->categoryid);
        $categorycontext2  = \context_coursecat::instance($tenant2->categoryid);
        $course0 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $coursecontext0 = \context_course::instance($course0->id);
        $course1 = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);
        $coursecontext1 = \context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $tenant2->categoryid]);
        $coursecontext2 = \context_course::instance($course2->id);

        // Any capability will do here, the default user role override.
        $capability = 'moodle/course:view';
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability($capability, CAP_ALLOW, $userrole->id, $syscontext->id);
        $guestrole = $DB->get_record('role', ['shortname' => 'guest'], '*', MUST_EXIST);
        assign_capability($capability, CAP_ALLOW, $guestrole->id, $syscontext->id);

        $admin = get_admin();
        $admincontext = \context_user::instance($admin->id);
        $guest = guest_user();
        $user0 = $this->getDataGenerator()->create_user();
        $usercontext0 = \context_user::instance($user0->id);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usercontext1 = \context_user::instance($user1->id);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $usercontext2 = \context_user::instance($user2->id);

        $result = get_users_by_capability($syscontext, $capability, 'u.id,u.email', 'u.id ASC');
        $expected = [$admin->id, $user0->id, $user1->id, $user2->id];
        $this->assertEquals($expected, array_keys($result));

        $result = get_users_by_capability($coursecontext0, $capability, 'u.id,u.email', 'u.id ASC');
        $expected = [$admin->id, $user0->id, $user1->id, $user2->id];
        $this->assertEquals($expected, array_keys($result));

        $result = get_users_by_capability($usercontext0, $capability, 'u.id,u.email', 'u.id ASC');
        $expected = [$admin->id, $user0->id, $user1->id, $user2->id];
        $this->assertEquals($expected, array_keys($result));

        $result = get_users_by_capability($tenantcontext1, $capability, 'u.id,u.email', 'u.id ASC');
        $expected = [$admin->id, $user0->id, $user1->id];
        $this->assertEquals($expected, array_keys($result));

        $result = get_users_by_capability($coursecontext1, $capability, 'u.id,u.email', 'u.id ASC');
        $expected = [$admin->id, $user0->id, $user1->id];
        $this->assertEquals($expected, array_keys($result));

        $result = get_users_by_capability($usercontext1, $capability, 'u.id,u.email', 'u.id ASC');
        $expected = [$admin->id, $user0->id, $user1->id];
        $this->assertEquals($expected, array_keys($result));
    }

    /**
     * @covers ::get_with_capability_sql()
     */
    public function test_get_with_capability_sql(): void {
        global $DB;
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();
        $category0 = $DB->get_record('course_categories', ['parent' => 0], '*', MUST_EXIST);

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $categorycontext0  = \context_coursecat::instance($category0->id);
        $categorycontext1  = \context_coursecat::instance($tenant1->categoryid);
        $categorycontext2  = \context_coursecat::instance($tenant2->categoryid);
        $course0 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $coursecontext0 = \context_course::instance($course0->id);
        $course1 = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);
        $coursecontext1 = \context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $tenant2->categoryid]);
        $coursecontext2 = \context_course::instance($course2->id);

        // Any capability will do here, the default user role override.
        $capability = 'moodle/course:view';
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability($capability, CAP_ALLOW, $userrole->id, $syscontext->id);
        $guestrole = $DB->get_record('role', ['shortname' => 'guest'], '*', MUST_EXIST);
        assign_capability($capability, CAP_ALLOW, $guestrole->id, $syscontext->id);

        $admin = get_admin();
        $admincontext = \context_user::instance($admin->id);
        $guest = guest_user();
        $user0 = $this->getDataGenerator()->create_user();
        $usercontext0 = \context_user::instance($user0->id);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usercontext1 = \context_user::instance($user1->id);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $usercontext2 = \context_user::instance($user2->id);

        [$sql, $params] = get_with_capability_sql($coursecontext0, $capability);
        $this->assertEqualsCanonicalizing(
            [$admin->id, $user0->id, $user1->id, $user2->id],
            $DB->get_fieldset_sql($sql, $params)
        );

        [$sql, $params] = get_with_capability_sql($coursecontext1, $capability);
        $this->assertEqualsCanonicalizing(
            [$admin->id, $user0->id, $user1->id],
            $DB->get_fieldset_sql($sql, $params)
        );

        [$sql, $params] = get_with_capability_sql($coursecontext2, $capability);
        $this->assertEqualsCanonicalizing(
            [$admin->id, $user0->id, $user2->id],
            $DB->get_fieldset_sql($sql, $params)
        );
    }

    /**
     * @covers ::role_get_name()
     */
    public function test_role_get_name(): void {
        tenancy::activate();

        $role = \tool_mutenancy\local\manager::get_role();
        $this->assertSame('', $role->name);
        $this->assertSame('Tenant manager', role_get_name($role));
    }

    /**
     * @covers ::role_get_description()
     */
    public function test_role_get_description(): void {
        tenancy::activate();

        $role = \tool_mutenancy\local\manager::get_role();
        $this->assertSame('', $role->description);
        $this->assertStringContainsString(
            'Tenant manager role gets assigned to all tenant mangers automatically',
            role_get_description($role)
        );
    }

    /**
     * @covers ::get_sorted_contexts()
     */
    public function test_get_sorted_contexts(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $site = get_site();
        $category0 = \core_course_category::get_default();
        $guest = guest_user();
        $admin = get_admin();

        $syscontext = \context_system::instance();
        $sitecontext = \context_course::instance($site->id);
        $categorycontext0 = \context_coursecat::instance($category0->id);
        $guestcontext = \context_user::instance($guest->id);
        $admincontext = \context_user::instance($admin->id);

        $contexts = get_sorted_contexts('ctx.contextlevel <= 50');
        $this->assertEquals(
            [$syscontext->id, $guestcontext->id, $admincontext->id, $categorycontext0->id, $sitecontext->id],
            array_keys($contexts)
        );

        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $categorycontext1 = \context_coursecat::instance($tenant1->categoryid);
        $categorycontext2 = \context_coursecat::instance($tenant2->categoryid);

        $contexts = get_sorted_contexts('ctx.contextlevel <= 50');
        $this->assertEquals(
            [$syscontext->id, $tenantcontext1->id, $tenantcontext2->id, $guestcontext->id, $admincontext->id, $categorycontext0->id, $categorycontext1->id, $categorycontext2->id, $sitecontext->id],
            array_keys($contexts)
        );
    }
}
