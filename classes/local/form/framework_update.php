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

namespace tool_murelation\local\form;

use tool_murelation\local\framework;
use tool_murelation\external\form_autocomplete\framework_cohortid;
use tool_murelation\external\form_autocomplete\framework_tenantids;
use tool_murelation\local\util;

/**
 * Update relation framework.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        global $DB;
        $mform = $this->_form;
        $framework = $this->_customdata['data'];
        $editoroptions = $this->_customdata['editoroptions'];
        $context = \context_system::instance();

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('framework_name', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'idnumber', get_string('framework_idnumber', 'tool_murelation'), 'maxlength="100" size="50"');
        $mform->setType('idnumber', PARAM_RAW); // Idnumbers are plain text.

        $uimodes = framework::get_uimodes();
        $mform->addElement('static', 'staticuimode', get_string('framework_uimode', 'tool_murelation'), $uimodes[$framework->uimode]);

        $mform->addElement('editor', 'description_editor', get_string('description'), ['rows' => 10], $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $options = framework::get_visibility_options();
        $mform->addElement('select', 'visibility', get_string('framework_visibility', 'tool_murelation'), $options);

        framework_cohortid::add_element(
            $mform,
            ['currentValue' => $framework->managecohortid],
            'managecohortid',
            get_string('framework_managecohort', 'tool_murelation'),
            $context
        );
        $mform->setType('managecohortid', PARAM_INT);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $mform->addElement('advcheckbox', 'alltenants', get_string('framework_alltenants', 'tool_murelation'));
            framework_tenantids::add_element(
                $mform,
                [],
                'tenantids',
                get_string('tenants', 'tool_mutenancy'),
                $context
            );
            $mform->hideIf('tenantids', 'alltenants', 'checked');
            $mform->setDefault('tenantids', $DB->get_fieldset('tool_murelation_tenant_allow', 'tenantid', ['frameworkid' => $framework->id]));
        }

        $mform->addElement('header', 'supervisorheader', get_string('supervisor', 'tool_murelation'));

        $mform->addElement('text', 'supervisortitle', get_string('supervisortitle', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->addRule('supervisortitle', get_string('required'), 'required', null, 'client');
        $mform->setType('supervisortitle', PARAM_TEXT);

        $mform->addElement('text', 'supervisorstitle', get_string('supervisorstitle', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->addRule('supervisorstitle', get_string('required'), 'required', null, 'client');
        $mform->setType('supervisorstitle', PARAM_TEXT);

        framework_cohortid::add_element(
            $mform,
            ['currentValue' => $framework->supervisorcohortid],
            'supervisorcohortid',
            get_string('framework_supervisorcohort', 'tool_murelation'),
            $context
        );
        $mform->setType('supervisorcohortid', PARAM_INT);

        $roles = \tool_murelation\local\framework::get_allowed_supervisor_roles($framework->supervisorroleid);
        if ($roles) {
            $roles = ['' => get_string('choosedots')] + $roles;
            $mform->addElement('select', 'supervisorroleid', get_string('framework_supervisorrole', 'tool_murelation'), $roles);
        }

        $mform->addElement('header', 'subordinateheader', get_string('subordinate', 'tool_murelation'));

        $mform->addElement('text', 'subordinatetitle', get_string('subordinatetitle', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->addRule('subordinatetitle', get_string('required'), 'required', null, 'client');
        $mform->setType('subordinatetitle', PARAM_TEXT);

        $mform->addElement('text', 'subordinatestitle', get_string('subordinatestitle', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->addRule('subordinatestitle', get_string('required'), 'required', null, 'client');
        $mform->setType('subordinatestitle', PARAM_TEXT);

        framework_cohortid::add_element(
            $mform,
            ['currentValue' => $framework->subordinatecohortid],
            'subordinatecohortid',
            get_string('framework_subordinatecohort', 'tool_murelation'),
            $context
        );
        $mform->setType('subordinatecohortid', PARAM_INT);

        $this->add_action_buttons(true, get_string('framework_update', 'tool_murelation'));

        $this->set_data($framework);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $framework = $this->_customdata['data'];
        $context = \context_system::instance();

        if (trim($data['idnumber']) !== $data['idnumber']) {
            $errors['idnumber'] = get_string('error');
        } else if ($data['idnumber'] !== '') {
            if ($DB->record_exists_select('tool_murelation_framework', "LOWER(idnumber) = LOWER(?) AND id <> ?", [$data['idnumber'], $data['id']])) {
                $errors['idnumber'] = get_string('error');
            }
        }

        foreach (['managecohortid', 'supervisorcohortid', 'subordinatecohortid'] as $field) {
            if (!$data[$field]) {
                continue;
            }
            $args = ['currentValue' => $framework->$field];
            $error = framework_cohortid::validate_value($data[$field], $args, $context);
            if ($error !== null) {
                $errors[$field] = $error;
            }
        }

        if (!empty($data['tenantids'])) {
            foreach ($data['tenantids'] as $tenantid) {
                $error = framework_tenantids::validate_value($tenantid, [], $context);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }
}
