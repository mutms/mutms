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

namespace tool_muprog\local\form;

use tool_muprog\external\form_autocomplete\program_contextid;

/**
 * Move program to a different context.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_move extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'fullname', get_string('programname', 'tool_muprog'), format_string($program->fullname));

        $mform->addElement('static', 'idnumber', get_string('idnumber'), format_string($program->idnumber));

        program_contextid::add_element($mform, [], 'contextid', get_string('category'), $context);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('program_move', 'tool_muprog'));

        $this->set_data($program);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $context = $this->_customdata['context'];

        $error = program_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        return $errors;
    }
}
