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

use tool_mucertify\external\form_autocomplete\certification_contextid;

/**
 * Move certification to a different context.
 *
 * @package    tool_mucertify
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification_move extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $certification = $this->_customdata['certification'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'fullname', get_string('certificationname', 'tool_mucertify'), format_string($certification->fullname));

        $mform->addElement('static', 'idnumber', get_string('certificationidnumber', 'tool_mucertify'), format_string($certification->idnumber));

        certification_contextid::add_element($mform, [], 'contextid', get_string('category'), $context);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('certification_move', 'tool_mucertify'));

        $this->set_data($certification);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $context = $this->_customdata['context'];

        $error = certification_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        return $errors;
    }
}
