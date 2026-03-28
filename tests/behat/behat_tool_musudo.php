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
require_once(__DIR__ . '/../../../../../lib/behat/behat_field_manager.php');

/**
 * Sudo behat steps.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_musudo extends behat_base {
    /**
     * Sets the given field with a valid email code created in tool_mfa_secrets table
     *
     * @Given I set the MFA secret field with valid code for :username
     *
     * @param string $username
     */
    public function i_set_valid_email_code_for_user(string $username): void {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $select = "userid = :userid AND revoked=0 AND factor='email' AND secret IS NOT NULL";
        $records = $DB->get_records_select('tool_mfa', $select, ['userid' => $user->id], 'id DESC');
        $record = reset($records);
        $field = behat_field_manager::get_form_field_from_label('Enter code', $this);

        // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        try {
            $field->set_value($record->secret);
        } catch (Exception $ex) {
            // Ignore problems with fancy MFA javascript...
        }
    }
}
