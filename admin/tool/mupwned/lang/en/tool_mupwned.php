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
 * Compromised password blocking plugin lang pack.
 *
 * @package     tool_mupwned
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['enabled'] = 'Detect compromised passwords';
$string['enabled_desc'] = 'If enabled compromised user passwords are automatically detected as part of password policy checks
using the [Have I Been Pwned](https://haveibeenpwned.com/) service with [k-Anonymity model](https://en.wikipedia.org/wiki/K-anonymity).

**Before enabling please make sure that site administrators may reset their passwords via email.**';
$string['enduser_compromisedpassword'] = 'This password was compromised during a data breach.';
$string['enduser_expiretokens_info'] = 'You may also need to create new web service tokens and re-authenticate in your Mobile app.';
$string['enduser_resetpassword_info'] = 'Your password has previously appeared in a data breach, for security reasons it cannot be used to access this site. You need to manually reset your password to log in.';
$string['event_user_login_blocked'] = 'Log-in with compromised password blocked';
$string['expiretokens'] = 'Expire web service and mobile tokens';
$string['expiretokens_desc'] = 'If enabled users who are forced to reset compromised passwords must also recreate all web service tokens
and log into their mobile apps again.';
$string['passwordpolicy'] = 'not been included in known data breaches';
$string['passwordpolicyinactive'] = '***Either "Password policy" or "Check password on login" is not enabled, blocking of compromised passwords will NOT work.***';
$string['pluginname'] = 'Compromised password blocking';
$string['privacy:metadata'] = 'Compromised password blocking plugin does not store any personal information';
$string['resetpassword'] = 'Require password reset';
$string['resetpassword_desc'] = 'When enabled users with compromised passwords cannot log in until they reset their password.';
$string['warning_passwordpolicy_disabled'] = '"Password policy" setting is disabled, detection of compromised passwords will not be possible.';
$string['warning_passwordpolicycheckonlogin_disabled'] = '"Check password on login" setting is disabled, logins with compromised password cannot be blocked.';
