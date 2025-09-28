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

use tool_muprog\external\form_autocomplete\program_content_import_fromprogram;

/**
 * Add program content items.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_content_import extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $targetprogram = $this->_customdata['targetprogram'];
        $context = $this->_customdata['context'];

        $ars = ['programid' => $targetprogram->id];
        program_content_import_fromprogram::add_element(
            $mform,
            $ars,
            'fromprogram',
            get_string('importselectprogram', 'tool_muprog'),
            $context
        );
        $mform->addRule('fromprogram', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $targetprogram->id);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $targetprogram = $this->_customdata['targetprogram'];
        $context = $this->_customdata['context'];

        $args = ['programid' => $targetprogram->id];
        $error = program_content_import_fromprogram::validate_value($data['fromprogram'], $args, $context);
        if ($error !== null) {
            $errors['fromprogram'] = $error;
        }

        return $errors;
    }
}
