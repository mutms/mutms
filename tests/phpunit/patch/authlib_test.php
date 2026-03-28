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
 * Multi-tenancy tests for lib/authlib.php modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class authlib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::signup_captcha_enabled()
     */
    public function test_signup_captcha_enabled(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        set_config('auth', 'manual,email');
        set_config('recaptcha', 1, 'auth_email');
        set_config('recaptchapublickey', 'xyz');
        set_config('recaptchaprivatekey', 'xyz');
        set_config('registerauth', 'email');
        $this->assertTrue(signup_captcha_enabled());

        set_config('registerauth', '');
        $this->assertFalse(signup_captcha_enabled());

        tenancy::activate();
        $tenant1 = $generator->create_tenant();

        set_config('registerauth', 'email');
        $this->assertTrue(signup_captcha_enabled());

        tenancy::switch($tenant1->id);
        $this->assertTrue(signup_captcha_enabled());

        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', '', 'core');
        $this->assertFalse(signup_captcha_enabled());

        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', 'email', 'core');
        $this->assertTrue(signup_captcha_enabled());

        set_config('registerauth', '');
        $this->assertTrue(signup_captcha_enabled());
    }

    /**
     * @covers ::signup_validate_data()
     */
    public function test_signup_validate_data(): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();
        $tenant1 = $generator->create_tenant();

        set_config('auth', 'manual,email');
        set_config('registerauth', 'email');
        set_config('passwordpolicy', '0');
        set_config('registerauth', 'email');

        $formdata = [
            'username' => 'newuser',
            'firstname' => 'First',
            'lastname' => 'Last',
            'password' => 'weak',
            'email' => 'abc@example.com',
            'email2' => 'abc@example.com',
        ];
        $errors = signup_validate_data($formdata, []);
        $this->assertSame([], $errors);

        $formdata = [
            'username' => 'newuser',
            'firstname' => 'First',
            'lastname' => 'Last',
            'password' => 'weak',
            'email' => 'abc@example.com',
            'email2' => 'abc@example.com',
            'tenantid' => $tenant1->id,
        ];
        $errors = signup_validate_data($formdata, []);
        $this->assertSame([], $errors);

        tenancy::switch($tenant1->id);
        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', '', 'core');
        try {
            signup_validate_data($formdata, []);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Authentication plugin  not found.', $ex->getMessage());
        }

        tenancy::switch($tenant1->id);
        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', 'email', 'core');
        $errors = signup_validate_data($formdata, []);
        $this->assertSame([], $errors);
    }

    /**
     * @covers ::signup_setup_new_user()
     */
    public function test_signup_setup_new_user(): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/editlib.php');
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        set_config('auth', 'manual,email');
        set_config('registerauth', 'email');

        tenancy::activate();
        $tenant1 = $generator->create_tenant();

        $user = (object)['id' => 0];
        $user = signup_setup_new_user($user);
        $this->assertSame('email', $user->auth);

        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', 'email', 'core');
        $user = (object)['id' => 0];
        $user = signup_setup_new_user($user);
        $this->assertSame('email', $user->auth);

        set_config('registerauth', '');
        tenancy::switch($tenant1->id);
        $user = signup_setup_new_user($user);
        $this->assertSame('email', $user->auth);
    }

    /**
     * @covers ::signup_get_user_confirmation_authplugin()
     */
    public function test_signup_get_user_confirmation_authplugin(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        set_config('auth', 'manual,email');
        set_config('registerauth', 'email');

        $auth = signup_get_user_confirmation_authplugin();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        set_config('registerauth', '');
        $auth = signup_get_user_confirmation_authplugin();
        $this->assertFalse($auth);

        tenancy::activate();
        $tenant1 = $generator->create_tenant();

        $auth = signup_get_user_confirmation_authplugin();
        $this->assertFalse($auth);

        set_config('registerauth', 'email');
        $auth = signup_get_user_confirmation_authplugin();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        tenancy::switch($tenant1->id);
        $auth = signup_get_user_confirmation_authplugin();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', '', 'core');
        $auth = signup_get_user_confirmation_authplugin();
        $this->assertFalse($auth);

        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', 'email', 'core');
        $auth = signup_get_user_confirmation_authplugin();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        set_config('registerauth', '');
        $auth = signup_get_user_confirmation_authplugin();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);
    }

    /**
     * @covers ::signup_is_enabled()
     */
    public function test_signup_is_enabled(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        set_config('auth', 'manual,email');
        set_config('registerauth', 'email');

        $auth = signup_is_enabled();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        set_config('registerauth', '');
        $auth = signup_is_enabled();
        $this->assertFalse($auth);

        tenancy::activate();
        $tenant1 = $generator->create_tenant();

        $auth = signup_is_enabled();
        $this->assertFalse($auth);

        set_config('registerauth', 'email');
        $auth = signup_is_enabled();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        tenancy::switch($tenant1->id);
        $auth = signup_is_enabled();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', '', 'core');
        $auth = signup_is_enabled();
        $this->assertFalse($auth);

        \tool_mutenancy\local\config::override($tenant1->id, 'registerauth', 'email', 'core');
        $auth = signup_is_enabled();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);

        set_config('registerauth', '');
        $auth = signup_is_enabled();
        $this->assertInstanceOf(\auth_plugin_email::class, $auth);
    }
}
