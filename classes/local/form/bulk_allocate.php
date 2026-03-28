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

namespace tool_mutenancy\local\form;

/**
 * Bulk user allocation form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class bulk_allocate extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        global $DB, $SESSION;
        $mform = $this->_form;

        [$in, $params] = $DB->get_in_or_equal($SESSION->bulk_users);
        $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,' . $DB->sql_fullname() . ' AS fullname', 0, 2000);
        $usernames = implode(', ', $userlist);
        if (count($SESSION->bulk_users) > 2000) {
            $usernames .= ', ...';
        }

        $info = '<div class="alert alert-warning">'
            . clean_text(markdown_to_html(get_string('bulk_allocate_info', 'tool_mutenancy', $usernames)))
            . '</div>';
        $mform->addElement('html', $info);

        $tenants = $DB->get_records_menu('tool_mutenancy_tenant', ['archived' => 0], 'name ASC', 'id, name');
        $tenants = array_map('format_string', $tenants);
        $tenants = ['' => get_string('choosedots')] + $tenants;

        $mform->addElement('select', 'tenantid', get_string('tenant', 'tool_mutenancy'), $tenants);
        $mform->addRule('tenantid', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);
        $mform->setDefault('returnurl', $this->_customdata['returnurl']);

        $this->add_action_buttons(true, get_string('bulk_allocate', 'tool_mutenancy'));
    }
}
