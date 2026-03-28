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

namespace tool_mutenancy\local\form;

use tool_mutenancy\external\form_autocomplete\tenant_assoccohortid;
use tool_mutenancy\local\tenant;
use tool_mutenancy\local\tenancy;

/**
 * Create a new tenant form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $context = $this->_customdata['context'];

        $mform->addElement('text', 'name', get_string('tenant_name', 'tool_mutenancy'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'idnumber', get_string('tenant_idnumber', 'tool_mutenancy'), ['maxlength' => 50]);
        $mform->setType('idnumber', PARAM_RAW);
        $mform->addRule('idnumber', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'loginshow', get_string('tenant_loginshow', 'tool_mutenancy'));

        $mform->addElement('text', 'memberlimit', get_string('tenant_memberlimit', 'tool_mutenancy'), ['size' => 5]);
        $mform->setType('memberlimit', PARAM_INT);
        $mform->addHelpButton('memberlimit', 'tenant_memberlimit', 'tool_mutenancy');

        tenant_assoccohortid::add_element(
            $mform,
            ['tenantid' => 0],
            'assoccohortid',
            get_string('associate_cohort', 'tool_mutenancy'),
            $context
        );
        $mform->setType('assoccohortid', PARAM_INT);
        $mform->addHelpButton('assoccohortid', 'associate_cohort', 'tool_mutenancy');

        if (has_capability('moodle/cohort:manage', $context)) {
            $mform->addElement('advcheckbox', 'assoccohortcreate', get_string('associate_cohort_create', 'tool_mutenancy'));
            $mform->addHelpButton('assoccohortcreate', 'associate_cohort_create', 'tool_mutenancy');
            $mform->hideIf('assoccohortid', 'assoccohortcreate', 'eq', 1);
        }

        $mform->addElement('text', 'sitefullname', get_string('tenant_sitefullname', 'tool_mutenancy'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('sitefullname', PARAM_TEXT);

        $mform->addElement('text', 'siteshortname', get_string('tenant_siteshortname', 'tool_mutenancy'), ['maxlength' => 255]);
        $mform->setType('siteshortname', PARAM_TEXT);

        $mform->addElement('text', 'categoryname', get_string('tenant_categoryname', 'tool_mutenancy'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('categoryname', PARAM_TEXT);

        $mform->addElement('text', 'categoryidnumber', get_string('tenant_categoryidnumber', 'tool_mutenancy'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('categoryidnumber', PARAM_TEXT);

        $mform->addElement('text', 'cohortname', get_string('tenant_cohortname', 'tool_mutenancy'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('cohortname', PARAM_TEXT);

        $mform->addElement('text', 'cohortidnumber', get_string('tenant_cohortidnumber', 'tool_mutenancy'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('cohortidnumber', PARAM_TEXT);

        $this->add_action_buttons(true, tenancy::get_tenant_string('tenant_create'));
    }

    #[\Override]
    public function validation($data, $files): array {
        global $DB;
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }

        if (trim($data['idnumber']) === '') {
            $errors['idnumber'] = get_string('required');
        } else if (!preg_match(tenant::IDNUMBER_REGEX, $data['idnumber'])) {
            $errors['idnumber'] = get_string('error');
        } else if ($DB->record_exists_select('tool_mutenancy_tenant', 'LOWER(idnumber) = LOWER(?)', [$data['idnumber']])) {
            $errors['idnumber'] = get_string('duplicate');
        }

        if (trim($data['categoryidnumber']) !== '') {
            if ($DB->record_exists_select('course_categories', 'LOWER(idnumber) = LOWER(?)', [$data['categoryidnumber']])) {
                $errors['categoryidnumber'] = get_string('duplicate');
            }
        }

        if (trim($data['cohortidnumber']) !== '') {
            if ($DB->record_exists_select('cohort', 'LOWER(idnumber) = LOWER(?)', [$data['cohortidnumber']])) {
                $errors['cohortidnumber'] = get_string('duplicate');
            }
        }

        if ($data['assoccohortid']) {
            $error = tenant_assoccohortid::validate_value($data['assoccohortid'], ['tenantid' => 0], $context);
            if ($error !== null) {
                $errors['assoccohortid'] = $error;
            }
        }

        return $errors;
    }
}
