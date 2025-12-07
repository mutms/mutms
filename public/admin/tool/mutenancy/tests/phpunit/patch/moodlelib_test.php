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
use tool_mutenancy\local\config;

/**
 * Multi-tenancy tests for lib/moodlelib.php modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class moodlelib_test extends \advanced_testcase {
    /** @var null|string previous error log target */
    protected $oldlog = null;

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function tearDown(): void {
        if (isset($this->oldlog)) {
            ini_set('error_log', $this->oldlog);
        }
        parent::tearDown();
    }

    /**
     * @covers ::authenticate_user_login()
     */
    public function test_authenticate_user_login(): void {
        global $USER, $CFG;
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant(['archived' => 1]);

        $user0 = $this->getDataGenerator()->create_user(['password' => 'pass']);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'password' => 'pass']);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'password' => 'pass']);

        $this->setUser();
        $_SERVER['HTTP_USER_AGENT'] = 'fake agent';

        $u = authenticate_user_login($user0->username, 'pass');
        $this->assertSame($user0->id, $u->id);
        $this->assertSame(0, $USER->id);

        $u = authenticate_user_login($user1->username, 'pass');
        $this->assertSame($user1->id, $u->id);
        $this->assertSame(0, $USER->id);

        $this->oldlog = ini_get('error_log');
        $logfile = "$CFG->dataroot/testlog.log";
        ini_set('error_log', $logfile);

        @unlink($logfile);
        $u = authenticate_user_login($user2->username, 'pass');
        $this->assertSame(false, $u);
        $this->assertSame(0, $USER->id);
        $errors = file_get_contents($logfile);
        $this->assertStringContainsString('Suspended Login:  username3', $errors);
    }

    /**
     * @covers ::require_login()
     */
    public function test_require_login(): void {
        global $DB;
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();
        $category0 = $DB->get_record('course_categories', ['parent' => 0], '*', MUST_EXIST);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $course0 = $this->getDataGenerator()->create_course(
            ['category' => $category0->id, 'enrol_guest_status_0' => 0, 'enrol_guest_password_0' => '']
        );
        $course1 = $this->getDataGenerator()->create_course(
            ['category' => $tenant1->categoryid, 'enrol_guest_status_0' => 0, 'enrol_guest_password_0' => '']
        );
        $course2 = $this->getDataGenerator()->create_course(
            ['category' => $tenant2->categoryid, 'enrol_guest_status_0' => 0, 'enrol_guest_password_0' => '']
        );

        $admin = get_admin();
        $guest = guest_user();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        // Enrol everybody everywhere.
        $this->getDataGenerator()->enrol_user($admin->id, $course0->id, 'student');
        $this->getDataGenerator()->enrol_user($admin->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($admin->id, $course2->id, 'student');
        $this->getDataGenerator()->enrol_user($user0->id, $course0->id, 'student');
        $this->getDataGenerator()->enrol_user($user0->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user0->id, $course2->id, 'student');
        $this->getDataGenerator()->enrol_user($user1->id, $course0->id, 'student');
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course0->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, 'student');

        $this->assertSame('0', get_config('tool_mutenancy', 'allowguests'));

        $this->setUser(null);
        try {
            require_login($course0, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
        try {
            require_login($course1, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
        try {
            require_login($course2, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }

        $this->setUser($guest);
        require_login($course0, false, null, false, true);
        try {
            require_login($course1, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (No guest)', $ex->getMessage());
        }
        try {
            require_login($course2, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (No guest)', $ex->getMessage());
        }

        $this->setUser($admin);
        require_login($course0, false, null, false, true);
        require_login($course1, false, null, false, true);
        require_login($course2, false, null, false, true);

        $this->setUser($user1);
        require_login($course0, false, null, false, true);
        require_login($course1, false, null, false, true);
        try {
            require_login($course2, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (No other tenant access)', $ex->getMessage());
        }

        set_config('allowguests', '1', 'tool_mutenancy');

        $this->setUser(null);
        try {
            require_login($course0, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
        try {
            require_login($course1, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
        try {
            require_login($course2, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }

        $this->setUser($guest);
        require_login($course0, false, null, false, true);
        require_login($course1, false, null, false, true);
        require_login($course2, false, null, false, true);

        $this->setUser($admin);
        require_login($course0, false, null, false, true);
        require_login($course1, false, null, false, true);
        require_login($course2, false, null, false, true);

        $this->setUser($user1);
        require_login($course0, false, null, false, true);
        require_login($course1, false, null, false, true);
        try {
            require_login($course2, false, null, false, true);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\require_login_exception::class, $ex);
            $this->assertSame('Course or activity not accessible. (No other tenant access)', $ex->getMessage());
        }
    }

    /**
     * @covers ::require_logout()
     */
    public function test_require_logout(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $this->setUser($user0);
        $this->assertSame(null, tenancy::get_current_tenantid());
        require_logout();
        $this->assertSame('0', $_COOKIE['TENANT']);
        $this->assertSame(null, tenancy::get_current_tenantid());

        $this->setUser($user0);
        tenancy::switch($tenant1->id);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());
        require_logout();
        $this->assertSame('0', $_COOKIE['TENANT']);
        $this->assertSame(null, tenancy::get_current_tenantid());

        $this->setUser($user1);
        $this->assertSame((int)$tenant1->id, tenancy::get_current_tenantid());
        require_logout();
        $this->assertSame($tenant1->idnumber, $_COOKIE['TENANT']);
        $this->assertSame(null, tenancy::get_current_tenantid());
    }

    /**
     * @covers ::unset_all_config_for_plugin()
     */
    public function test_unset_all_config_for_plugin(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        config::override($tenant1->id, 'tool_xyz_abc', 'x1', 'core');
        config::override($tenant2->id, 'tool_xyz_abc', 'y1', 'core');
        config::override($tenant1->id, 'opr', 'x2', 'tool_xyz');
        config::override($tenant1->id, 'tool_mulib_def', 'x3', 'core');
        config::override($tenant1->id, 'opr', 'x4', 'tool_mulib');

        $this->assertTrue(config::is_overridden($tenant1->id, 'core', 'tool_xyz_abc'));
        $this->assertTrue(config::is_overridden($tenant2->id, 'core', 'tool_xyz_abc'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'tool_xyz', 'opr'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'core', 'tool_mulib_def'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'tool_mulib', 'opr'));

        unset_all_config_for_plugin('tool_xyz');
        $this->assertFalse(config::is_overridden($tenant1->id, 'core', 'tool_xyz_abc'));
        $this->assertFalse(config::is_overridden($tenant2->id, 'core', 'tool_xyz_abc'));
        $this->assertFalse(config::is_overridden($tenant1->id, 'tool_xyz', 'opr'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'core', 'tool_mulib_def'));
        $this->assertTrue(config::is_overridden($tenant1->id, 'tool_mulib', 'opr'));

        $this->assertSame('x3', config::get($tenant1->id, 'core', 'tool_mulib_def'));
        $this->assertSame('x4', config::get($tenant1->id, 'tool_mulib', 'opr'));
    }

    /**
     * @covers ::email_to_user()
     */
    public function test_email_to_user(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant([
            'name' => 'Tenant 1',
            'idnumber' => 'ten1',
            'sitefullname' => 'Tenant full site name 1',
            'siteshortname' => 'Tenant short 1',
        ]);
        $tenant2 = $generator->create_tenant([
            'name' => 'Tenant 2',
            'idnumber' => 'ten2',
            'sitefullname' => 'Tenant full site name 2',
            'siteshortname' => 'Tenant short 2',
        ]);

        $admin = get_admin();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $sink = $this->redirectEmails();
        $this->assertTrue(email_to_user($user0, $admin, 'Some subject', 'Some body'));
        $emails = $sink->get_messages();
        $email = reset($emails);
        $this->assertStringContainsString('"Admin User (via phpunit)"', $email->header);

        $sink->clear();
        $this->assertTrue(email_to_user($user1, $admin, 'Some subject', 'Some body'));
        $emails = $sink->get_messages();
        $email = reset($emails);
        $this->assertStringContainsString('"Admin User (via Tenant short 1)"', $email->header);

        \tool_mutenancy\local\tenant::archive($tenant1->id);
        $sink->clear();
        $this->assertTrue(email_to_user($user1, $admin, 'Some subject', 'Some body'));
        $emails = $sink->get_messages();
        $this->assertSame([], $emails);

        $sink->close();
    }

    /**
     * @covers ::email_is_not_allowed()
     */
    public function test_email_is_not_allowed(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant([
            'name' => 'Tenant 1',
            'idnumber' => 'ten1',
            'sitefullname' => 'Tenant full site name 1',
            'siteshortname' => 'Tenant short 1',
        ]);
        $tenant2 = $generator->create_tenant([
            'name' => 'Tenant 2',
            'idnumber' => 'ten2',
            'sitefullname' => 'Tenant full site name 2',
            'siteshortname' => 'Tenant short 2',
        ]);

        set_config('allowemailaddresses', 'example.net');
        config::override($tenant2->id, 'allowemailaddresses', 'example.org', 'core');

        $this->assertSame(
            false,
            email_is_not_allowed('user@example.net')
        );
        $this->assertSame(
            'This email cannot be used. Allowed email domains are: example.net.',
            email_is_not_allowed('user@example.org')
        );

        tenancy::force_current_tenantid($tenant1->id);
        $this->assertSame(
            false,
            email_is_not_allowed('user@example.net')
        );
        $this->assertSame(
            'This email cannot be used. Allowed email domains are: example.net.',
            email_is_not_allowed('user@example.org')
        );
        tenancy::unforce_current_tenantid();

        tenancy::force_current_tenantid($tenant2->id);
        $this->assertSame(
            'This email cannot be used. Allowed email domains are: example.org.',
            email_is_not_allowed('user@example.net')
        );
        $this->assertSame(
            false,
            email_is_not_allowed('user@example.org')
        );
        tenancy::unforce_current_tenantid();
    }
}
