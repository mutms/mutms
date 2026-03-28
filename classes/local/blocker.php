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

namespace tool_mupwned\local;

/**
 * Compromised passwords blocker.
 *
 * @package     tool_mupwned
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class blocker {
    /** @var int login page access */
    public const SERVICE_LOGIN = 1;
    /** @var int login token creation access */
    public const SERVICE_CREATE_TOKEN = 2;
    /** @var int WS password check, likely confirmation resending */
    public const SERVICE_PUBLIC_WS = 3;

    /** @var int magic date 2000/01/01 indicating token was expired due to compromised password */
    public const TOKEN_EXPIRATION = 946724400;

    /**
     * Is the given password compromised?
     *
     * This is using k-Anonymity model to verify password using https://haveibeenpwned.com/API/v3
     *
     * @param string $password
     * @return bool
     */
    public static function is_password_compromised(string $password): bool {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $hash = strtoupper(sha1($password));
        $partialhash = substr($hash, 0, 5);
        $hashsuffix = substr($hash, 5);
        $apiurl = 'https://api.pwnedpasswords.com/range';
        // Use shorter timeouts, we should not block login for 20 seconds.
        $result = download_file_content($apiurl . '/' . $partialhash, null, null, false, 5, 5);
        if ($result === false) {
            if (PHPUNIT_TEST || defined('BEHAT_SITE_RUNNING')) {
                throw new \Exception("Cannot access $apiurl/abcde to verify compromised passwords");
            }
            // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
            error_log("Cannot access $apiurl/abcde to verify compromised passwords");
            return false;
        }

        return str_contains($result, $hashsuffix);
    }

    /**
     * Guest if password policy validation comes from a login request.
     *
     * @return int|null SERVICE_ constants
     */
    public static function guess_service(): ?int {
        global $SCRIPT;
        if ($SCRIPT === '/login/index.php') {
            return self::SERVICE_LOGIN;
        }
        if ($SCRIPT === '/login/token.php') {
            return self::SERVICE_CREATE_TOKEN;
        }
        if ($SCRIPT === '/lib/ajax/service-nologin.php') {
            // Most likely "core_auth_resend_confirmation_email" WS,
            // there should not be any other web services that validate user passwords.
            return self::SERVICE_PUBLIC_WS;
        }
        return null;
    }
}
