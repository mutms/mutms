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

/**
 * Compromised password blocking core integration.
 *
 * @package    tool_mupwned
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mupwned\local\blocker;

/**
 * Description of password policy.
 *
 * @return array
 */
function tool_mupwned_print_password_policy(): array {
    if (!get_config('tool_mupwned', 'enabled')) {
        return [];
    }
    return [get_string('passwordpolicy', 'tool_mupwned')];
}

/**
 * Compromised password check.
 *
 * @param mixed $password
 * @param stdClass|null $user
 * @return string|null
 */
function tool_mupwned_check_password_policy($password, $user = null): ?string {
    global $DB;

    if (!get_config('tool_mupwned', 'enabled')) {
        return null;
    }

    if (!blocker::is_password_compromised($password)) {
        return null;
    }

    if (!$user) {
        // This should not be a normal log in, must likely a change or reset of password.
        return get_string('enduser_compromisedpassword', 'tool_mupwned');
    }

    $resetpassword = get_config('tool_mupwned', 'resetpassword');
    if (!$resetpassword) {
        return get_string('enduser_compromisedpassword', 'tool_mupwned');
    }

    $service = blocker::guess_service();
    if ($service === null && !PHPUNIT_TEST) {
        // Not sure what this is, this should not happen.
        return get_string('enduser_compromisedpassword', 'tool_mupwned');
    }

    $DB->set_field('user', 'password', AUTH_PASSWORD_NOT_CACHED, ['id' => $user->id]);
    \core\session\manager::destroy_user_sessions($user->id);

    $event = \tool_mupwned\event\user_login_blocked::create_from_user($user, (int)$service);
    $event->trigger();

    $expiretokens = get_config('tool_mupwned', 'expiretokens');
    if ($expiretokens) {
        // Do not delete the tokens, instead expire them with distinctive date.
        $DB->set_field('external_tokens', 'validuntil', blocker::TOKEN_EXPIRATION, ['userid' => $user->id]);
    }

    if ($service === blocker::SERVICE_LOGIN) {
        $message = get_string('enduser_resetpassword_info', 'tool_mupwned');
        if ($expiretokens) {
            $message .= ' ' . get_string('enduser_expiretokens_info', 'tool_mupwned');
        }
        redirect(new moodle_url('/login/forgot_password.php'), $message, null, \core\output\notification::NOTIFY_ERROR);
    } else {
        // Must be a login/token.php or a web service, just show an error there.
        throw new moodle_exception('invalidlogin');
    }
}
