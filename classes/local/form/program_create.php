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

namespace tool_muprog\local\form;

use tool_muprog\external\form_autocomplete\program_contextid;

/**
 * Add program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_create extends \tool_mulib\local\ajax_form {
    /** @var \tool_muprog\customfield\allocation_handler */
    protected $handler;

    #[\Override]
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $mform->addElement('text', 'fullname', get_string('programname', 'tool_muprog'), 'maxlength="254" size="50"');
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('programidnumber', 'tool_muprog'), 'maxlength="254" size="50"');
        $mform->addRule('idnumber', get_string('required'), 'required', null, 'client');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text.

        program_contextid::add_element($mform, [], 'contextid', get_string('category'), $context);

        $mform->addElement('select', 'creategroups', get_string('creategroups', 'tool_muprog'), [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->addHelpButton('creategroups', 'creategroups', 'tool_muprog');

        if ($CFG->usetags) {
            $mform->addElement('tags', 'tags', get_string('tags'), ['itemtype' => 'tool_muprog_program', 'component' => 'tool_muprog']);
        }

        $options = \tool_muprog\local\program::get_image_filemanager_options();
        $mform->addElement('filemanager', 'image', get_string('programimage', 'tool_muprog'), null, $options);

        $mform->addElement('editor', 'description_editor', get_string('description'), ['rows' => 5], $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $sources = [];
        /** @var \tool_muprog\local\source\base[] $sourceclasses */
        $sourceclasses = \tool_muprog\local\allocation::get_source_classes();
        foreach ($sourceclasses as $sourceclass) {
            if ($sourceclass::is_new_allowed_in_new()) {
                $sources[] = $mform->createElement('advcheckbox', $sourceclass::get_type(), $sourceclass::get_name());
            }
        }
        if ($sources) {
            $mform->addElement('group', 'addsources', get_string('allocationsources', 'tool_muprog'), $sources, '<div class="w-100 mb-2" />');
        }

        // Add custom fields to the form.
        $this->handler = \tool_muprog\customfield\program_handler::create();
        $this->handler->instance_form_definition($mform);

        $this->add_action_buttons(true, get_string('program_create', 'tool_muprog'));

        // Prepare custom fields data.
        $this->handler->instance_form_before_set_data($data);

        $this->set_data($data);
    }

    #[\Override]
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;
        $this->handler->instance_form_definition_after_data($mform, 0);
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
            if ($DB->record_exists_select('tool_muprog_program', "LOWER(idnumber) = LOWER(?)", [$data['idnumber']])) {
                $errors['idnumber'] = get_string('error');
            }
        }

        $error = program_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        // Add the custom fields validation.
        $errors = array_merge($errors, $this->handler->instance_form_validation($data, $files));

        return $errors;
    }
}
