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

namespace tool_mucertify\local\form;

/**
 * Restore certification.
 *
 * @package    tool_mucertify
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification_restore extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $certification = $this->_customdata['certification'];

        $info = '<div class="alert alert-info">' . markdown_to_html(get_string('certification_restore_info', 'tool_mucertify')) . '</div>';
        $mform->addElement('html', $info);

        $mform->addElement('static', 'fullname', get_string('certificationname', 'tool_mucertify'), format_string($certification->fullname));

        $mform->addElement('static', 'idnumber', get_string('certificationidnumber', 'tool_mucertify'), format_string($certification->idnumber));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $certification->id);

        $this->add_action_buttons(true, get_string('certification_restore', 'tool_mucertify'));
    }
}
