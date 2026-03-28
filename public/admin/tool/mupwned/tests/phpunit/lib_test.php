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

namespace tool_mupwned\phpunit;

/**
 * Compromised passwords blocking tests.
 *
 * @group       MuTMS
 * @package     tool_mupwned
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::tool_mupwned_print_password_policy
     */
    public function test_tool_mupwned_print_password_policy(): void {
        set_config('enabled', 0, 'tool_mupwned');
        set_config('passwordpolicy', 0);
        $this->assertSame('', print_password_policy());

        set_config('passwordpolicy', 1);
        set_config('minpasswordlength', 0);
        set_config('minpassworddigits', 0);
        set_config('minpasswordlower', 0);
        set_config('minpasswordupper', 0);
        set_config('minpasswordnonalphanum', 0);
        set_config('maxconsecutiveidentchars', 0);
        $this->assertSame('', print_password_policy());

        set_config('enabled', 1, 'tool_mupwned');
        $this->assertSame('The password must have not been included in known data breaches', print_password_policy());

        set_config('minpasswordlength', 4);
        $this->assertSame('The password must have at least 4 characters, not been included in known data breaches', print_password_policy());
    }

    /**
     * @covers ::tool_mupwned_check_password_policy
     */
    public function test_tool_mupwned_check_password_policy(): void {
        global $DB;

        set_config('passwordpolicy', 0);
        set_config('passwordpolicy', 1);
        set_config('minpasswordlength', 4);
        set_config('minpassworddigits', 0);
        set_config('minpasswordlower', 0);
        set_config('minpasswordupper', 0);
        set_config('minpasswordnonalphanum', 0);
        set_config('maxconsecutiveidentchars', 0);

        $user = $this->getDataGenerator()->create_user(['password' => 'something']);
        $this->assertStringStartsWith('$6$rounds=', $user->password); // Anything but 'not set'.

        set_config('enabled', 0, 'tool_mupwned');
        set_config('resetpassword', 0, 'tool_mupwned');
        set_config('expiretokens', 0, 'tool_mupwned');

        $errmsg = false;
        $this->assertTrue(check_password_policy('123456', $errmsg, null));
        $this->assertSame('', $errmsg);

        $errmsg = false;
        $this->assertFalse(check_password_policy('123', $errmsg, null));
        $this->assertSame('<div>Passwords must be at least 4 characters long.</div>', $errmsg);

        $errmsg = false;
        $this->assertTrue(check_password_policy('123456', $errmsg, $user));
        $this->assertSame('', $errmsg);

        $errmsg = false;
        $this->assertFalse(check_password_policy('123', $errmsg, $user));
        $this->assertSame('<div>Passwords must be at least 4 characters long.</div>', $errmsg);

        set_config('enabled', 1, 'tool_mupwned');

        $errmsg = false;
        $this->assertTrue(check_password_policy('fjDEos847KJH-=+', $errmsg, null));
        $this->assertSame('', $errmsg);

        $errmsg = false;
        $this->assertfalse(check_password_policy('123456', $errmsg, null));
        $this->assertSame('<div>This password was compromised during a data breach.</div>', $errmsg);

        $errmsg = false;
        $this->assertFalse(check_password_policy('123', $errmsg, null));
        $this->assertSame('<div>Passwords must be at least 4 characters long.</div><div>This password was compromised during a data breach.</div>', $errmsg);

        $errmsg = false;
        $this->assertTrue(check_password_policy('fjDEos847KJH-=+', $errmsg, $user));
        $this->assertSame('', $errmsg);

        $errmsg = false;
        $this->assertFalse(check_password_policy('123456', $errmsg, $user));
        $this->assertSame('<div>This password was compromised during a data breach.</div>', $errmsg);

        $errmsg = false;
        $this->assertFalse(check_password_policy('123', $errmsg, $user));
        $this->assertSame('<div>Passwords must be at least 4 characters long.</div><div>This password was compromised during a data breach.</div>', $errmsg);

        set_config('resetpassword', 1, 'tool_mupwned');

        $this->setUser($user);
        $service = $DB->get_record('external_services', ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE]);
        $token = \core_external\util::generate_token_for_current_user($service);
        $this->assertIsObject($token);
        $this->setUser(null);

        $errmsg = false;
        $this->assertTrue(check_password_policy('fjDEos847KJH-=+', $errmsg, null));
        $this->assertSame('', $errmsg);

        $errmsg = false;
        $this->assertfalse(check_password_policy('123456', $errmsg, null));
        $this->assertSame('<div>This password was compromised during a data breach.</div>', $errmsg);

        $sink = $this->redirectEvents();

        $errmsg = false;
        $this->assertTrue(check_password_policy('fjDEos847KJH-=+', $errmsg, $user));
        $this->assertSame('', $errmsg);
        $newuser = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $this->assertSame($user->password, $newuser->password);
        $this->assertCount(0, $sink->get_events());

        $newtoken = $DB->get_record('external_tokens', ['id' => $token->id], '*', MUST_EXIST);
        $this->assertEquals($token->validuntil, $newtoken->validuntil);

        try {
            $errmsg = false;
            check_password_policy('123456', $errmsg, $user);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertStringStartsWith('error/invalidlogin', $ex->getMessage());
        }
        $newuser = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        $this->assertSame('not cached', $newuser->password);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(\tool_mupwned\event\user_login_blocked::class, $event);
        $this->assertSame('tool_mupwned', $event->component);
        $this->assertSame('user', $event->objecttable);
        $this->assertSame($user->id, $event->objectid);
        $this->assertSame($user->id, $event->userid);
        $this->assertSame($user->id, $event->relateduserid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);

        $newtoken = $DB->get_record('external_tokens', ['id' => $token->id], '*', MUST_EXIST);
        $this->assertEquals($token->validuntil, $newtoken->validuntil);

        set_config('expiretokens', 1, 'tool_mupwned');

        try {
            $errmsg = false;
            check_password_policy('123456', $errmsg, $user);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertStringStartsWith('error/invalidlogin', $ex->getMessage());
        }
        $newtoken = $DB->get_record('external_tokens', ['id' => $token->id], '*', MUST_EXIST);
        $this->assertEquals(\tool_mupwned\local\blocker::TOKEN_EXPIRATION, $newtoken->validuntil);
    }
}
