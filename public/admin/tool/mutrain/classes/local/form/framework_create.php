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

namespace tool_mutrain\local\form;

/**
 * Create a new training framework.
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

        $mform->addElement('text', 'name', get_string('framework_name', 'tool_mutrain'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('framework_idnumber', 'tool_mutrain'), 'maxlength="100" size="50"');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text.

        $mform->addElement('autocomplete', 'contextid', get_string('context', 'role'), $this->get_category_options());
        $mform->addRule('contextid', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'publicaccess', get_string('publicaccess', 'tool_mutrain'), ' ');

        $mform->addElement('editor', 'description_editor', get_string('description'), ['rows' => 3], $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('text', 'requiredtraining', get_string('requiredtraining', 'tool_mutrain'));
        $mform->setType('requiredtraining', PARAM_INT);
        $mform->addRule('requiredtraining', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'restrictedcompletion', get_string('restrictedcompletion', 'tool_mutrain'), ' ');

        $this->add_action_buttons(true, get_string('framework_create', 'tool_mutrain'));

        $this->set_data($data);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        if (trim($data['idnumber']) !== '') {
            if ($DB->record_exists_select('tool_mutrain_framework', "LOWER(idnumber) = LOWER(?)", [$data['idnumber']])) {
                $errors['idnumber'] = get_string('error');
            }
        }

        if ($data['requiredtraining'] <= 0) {
            $errors['requiredtraining'] = get_string('error');
        }

        $context = \context::instance_by_id($data['contextid'], IGNORE_MISSING);
        if (!$context) {
            $errors['contextid'] = get_string('required');
        } else if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            $errors['contextid'] = get_string('error');
        } else if (!has_capability('tool/mutrain:manageframeworks', $context)) {
            // There is a problem in category caching it seems.
            $errors['contextid'] = get_string('error');
        }

        return $errors;
    }

    /**
     * Returns categories.
     *
     * @return array
     */
    protected function get_category_options(): array {
        $syscontext = \context_system::instance();
        $options = [];
        if (has_capability('tool/mutrain:manageframeworks', $syscontext)) {
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        $categories = \core_course_category::make_categories_list('tool/mutrain:manageframeworks');
        foreach ($categories as $catid => $categoryname) {
            $catcontext = \context_coursecat::instance($catid);
            $options[$catcontext->id] = $categoryname;
        }
        return $options;
    }
}
