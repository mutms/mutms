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

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Compromised passwords blocker steps.
 *
 * @package     tool_mupwned
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_mupwned extends behat_base {
    /**
     * Emulate clicking on confirmation link from the email
     *
     * @When /^I open password reset confirmation for user "(?P<username>(?:[^"]|\\")*)"$/
     *
     * @param string $username
     */
    public function i_reset_password_for_user($username) {
        global $DB;
        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $token = $DB->get_field('user_password_resets', 'token', ['userid' => $user->id], MUST_EXIST);
        $url = new moodle_url('/login/forgot_password.php', ['token' => $token]);
        $this->execute('behat_general::i_visit', [$url->out_as_local_url(false)]);
    }
}
