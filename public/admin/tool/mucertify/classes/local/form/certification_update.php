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
 * Update certification.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification_update extends \tool_mulib\local\ajax_form {
    /** @var \tool_mucertify\customfield\certification_handler */
    protected $handler;

    #[\Override]
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $mform->addElement('text', 'fullname', get_string('certificationname', 'tool_mucertify'), 'maxlength="254" size="50"');
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('certificationidnumber', 'tool_mucertify'), 'maxlength="254" size="50"');
        $mform->addRule('idnumber', get_string('required'), 'required', null, 'client');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text.

        certification_contextid::add_element($mform, [], 'contextid', get_string('category'), $context);

        if ($CFG->usetags) {
            $mform->addElement('tags', 'tags', get_string('tags'), ['itemtype' => 'tool_mucertify_certification', 'component' => 'tool_mucertify']);
        }

        $options = \tool_mucertify\local\certification::get_image_filemanager_options();
        $mform->addElement('filemanager', 'image', get_string('certificationimage', 'tool_mucertify'), null, $options);

        $mform->addElement('editor', 'description_editor', get_string('description'), ['rows' => 5], $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('select', 'archived', get_string('archived', 'tool_mucertify'), [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->hardFreeze('archived');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Add custom fields to the form.
        $this->handler = \tool_mucertify\customfield\certification_handler::create();
        $this->handler->instance_form_definition($mform, $data->id);

        $this->add_action_buttons(true, get_string('certification_update', 'tool_mucertify'));

        // Prepare custom fields data.
        $this->handler->instance_form_before_set_data($data);

        $this->set_data($data);
    }

    #[\Override]
    public function definition_after_data() {
        parent::definition_after_data();
        $data = $this->_customdata['data'];
        $mform = $this->_form;
        $this->handler->instance_form_definition_after_data($mform, $data->id);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $context = $this->_customdata['context'];

        $errors = parent::validation($data, $files);

        if (trim($data['fullname']) === '') {
            $errors['fullname'] = get_string('required');
        }

        if (trim($data['idnumber']) === '') {
            $errors['idnumber'] = get_string('required');
        } else if (trim($data['idnumber']) !== $data['idnumber']) {
            $errors['idnumber'] = get_string('error');
        } else {
            if ($DB->record_exists_select('tool_mucertify_certification', "LOWER(idnumber) = LOWER(?) AND id <> ?", [$data['idnumber'], $data['id']])) {
                $errors['idnumber'] = get_string('error');
            }
        }

        $error = certification_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        // Add the custom fields validation.
        $errors = array_merge($errors, $this->handler->instance_form_validation($data, $files));

        return $errors;
    }
}
