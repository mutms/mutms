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

namespace tool_mutrain\local\form;

use tool_mutrain\external\form_autocomplete\framework_contextid;

/**
 * Create a new credit framework.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $editoroptions = $this->_customdata['editoroptions'];
        $context = $this->_customdata['context'];

        $mform->addElement('text', 'name', get_string('framework_name', 'tool_mutrain'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('framework_idnumber', 'tool_mutrain'), 'maxlength="100" size="50"');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text.

        framework_contextid::add_element($mform, [], 'contextid', get_string('category'), $context);

        $mform->addElement('advcheckbox', 'publicaccess', get_string('publicaccess', 'tool_mutrain'), ' ');

        $mform->addElement('editor', 'description_editor', get_string('description'), ['rows' => 3], $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('text', 'requiredcredits', get_string('requiredcredits', 'tool_mutrain'));
        $mform->setType('requiredcredits', PARAM_RAW);
        $mform->addRule('requiredcredits', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'restrictcontext', get_string('restrictcontext', 'tool_mutrain'), ' ');

        $mform->addElement('date_time_selector', 'restrictafter', get_string('restrictafter', 'tool_mutrain'), ['optional' => true]);

        $this->add_action_buttons(true, get_string('framework_create', 'tool_mutrain'));

        $this->set_data($data);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $context = $this->_customdata['context'];

        $errors = parent::validation($data, $files);

        if (trim($data['idnumber']) !== '') {
            if ($DB->record_exists_select('tool_mutrain_framework', "LOWER(idnumber) = LOWER(?)", [$data['idnumber']])) {
                $errors['idnumber'] = get_string('error');
            }
        }

        $requiredcredits = str_replace(',', '.', $data['requiredcredits']);
        if (!is_numeric($requiredcredits) || $requiredcredits <= 0) {
            $errors['requiredcredits'] = get_string('error');
        }

        $error = framework_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        return $errors;
    }
}
