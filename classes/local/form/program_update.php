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

/**
 * Update program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_update extends \tool_mulib\local\ajax_form {
    /** @var \tool_muprog\customfield\allocation_handler */
    protected $handler;

    #[\Override]
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $data = $this->_customdata['data'];

        $mform->addElement('text', 'fullname', get_string('programname', 'tool_muprog'), 'maxlength="254" size="50"');
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('programidnumber', 'tool_muprog'), 'maxlength="254" size="50"');
        $mform->addRule('idnumber', get_string('required'), 'required', null, 'client');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text.

        $options = $this->get_category_options($data->contextid);
        $mform->addElement('autocomplete', 'contextid', get_string('context', 'role'), $options);
        $mform->addRule('contextid', null, 'required', null, 'client');

        $mform->addElement('select', 'creategroups', get_string('creategroups', 'tool_muprog'), [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->addHelpButton('creategroups', 'creategroups', 'tool_muprog');

        if ($CFG->usetags) {
            $mform->addElement('tags', 'tags', get_string('tags'), ['itemtype' => 'tool_muprog_program', 'component' => 'tool_muprog']);
        }

        $options = \tool_muprog\local\program::get_image_filemanager_options();
        $mform->addElement('filemanager', 'image', get_string('programimage', 'tool_muprog'), null, $options);

        $mform->addElement('editor', 'description_editor', get_string('description'), ['rows' => 5], $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('select', 'archived', get_string('archived', 'tool_muprog'), [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->hardFreeze('archived');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Add custom fields to the form.
        $this->handler = \tool_muprog\customfield\program_handler::create();
        $this->handler->instance_form_definition($mform, $data->id);

        $this->add_action_buttons(true, get_string('program_update', 'tool_muprog'));

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

        $olddata = $this->_customdata['data'];

        $errors = parent::validation($data, $files);

        if (trim($data['fullname']) === '') {
            $errors['fullname'] = get_string('required');
        }

        if (trim($data['idnumber']) === '') {
            $errors['idnumber'] = get_string('required');
        } else if (trim($data['idnumber']) !== $data['idnumber']) {
            $errors['idnumber'] = get_string('error');
        } else {
            if ($olddata->idnumber !== $data['idnumber']) {
                $select = 'idnumber = :idnumber AND id <> :id';
                $params = ['idnumber' => $data['idnumber'], 'id' => $olddata->id];
                if ($DB->record_exists_select('tool_muprog_program', $select, $params)) {
                    $errors['idnumber'] = get_string('error');
                }
            }
        }

        $context = \context::instance_by_id($data['contextid'], IGNORE_MISSING);
        if (!$context) {
            $errors['contextid'] = get_string('required');
        } else if ($olddata->contextid != $data['contextid']) {
            if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
                $errors['contextid'] = get_string('error');
            } else if (!has_capability('tool/muprog:edit', $context)) {
                $errors['contextid'] = get_string('error');
            }
        }

        // Add the custom fields validation.
        $errors = array_merge($errors, $this->handler->instance_form_validation($data, $files));

        return $errors;
    }

    /**
     * Returns categories.
     *
     * @param int $currentcontextid
     * @return array
     */
    protected function get_category_options(int $currentcontextid): array {
        $displaylist = \core_course_category::make_categories_list('tool/muprog:edit');
        $options = [];
        $syscontext = \context_system::instance();
        if (has_capability('tool/muprog:edit', $syscontext)) {
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        foreach ($displaylist as $cid => $name) {
            $context = \context_coursecat::instance($cid);
            $options[$context->id] = $name;
        }
        if (!isset($options[$currentcontextid])) {
            $context = \context::instance_by_id($currentcontextid, MUST_EXIST);
            $options[$context->id] = $syscontext->get_context_name();
        }
        return $options;
    }
}
