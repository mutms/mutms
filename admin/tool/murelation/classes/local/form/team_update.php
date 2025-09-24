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

use tool_murelation\external\form_autocomplete\team_update_userid;

/**
 * Update team.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class team_update extends \tool_mulib\local\ajax_form {
    /** @var array */
    protected $wsarguments;

    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $supervisor = $this->_customdata['supervisor'];
        $framework = $this->_customdata['framework'];
        $context = $this->_customdata['context'];
        $this->wsarguments = ['supervisorid' => $supervisor->id];

        $supervisortitle = format_string($framework->supervisortitle);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('static', 'fwname', get_string('framework_name', 'tool_murelation'), format_string($framework->name));
        if ($framework->idnumber !== null) {
            $mform->addElement('static', 'fwidnumber', get_string('framework_idnumber', 'tool_murelation'), s($framework->idnumber));
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active() && $supervisor->tenantid) {
            $tenant = \tool_mutenancy\local\tenant::fetch($supervisor->tenantid);
            $mform->addElement('static', 'tenant', get_string('tenant', 'tool_mutenancy'), format_string($tenant->name));
        }

        $mform->addElement('text', 'teamname', get_string('team_name', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->setType('teamname', PARAM_TEXT);
        $mform->addRule('teamname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'teamidnumber', get_string('team_idnumber', 'tool_murelation'), 'maxlength="100" size="50"');
        $mform->setType('teamidnumber', PARAM_RAW); // Idnumbers are plain text.

        team_update_userid::add_element($mform, $this->wsarguments, 'userid', $supervisortitle, $context);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('advcheckbox', 'supmanaged', get_string('team_supmanaged', 'tool_murelation'));

        $mform->addElement('text', 'maxsubordinates', get_string('team_maxsubordinates', 'tool_murelation'), ['size' => 3]);
        $mform->setType('maxsubordinates', PARAM_INT);

        $cohort = false;
        if ($supervisor->teamcohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid]);
        }

        if (!$cohort) {
            $mform->addElement('advcheckbox', 'teamcohortcreate', get_string('team_cohort_create', 'tool_murelation'));
        }
        $mform->addElement('text', 'teamcohortname', get_string('team_cohort_name', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->setType('teamcohortname', PARAM_TEXT);
        if (!$cohort) {
            $mform->hideIf('teamcohortname', 'teamcohortcreate', 'notchecked');
        } else {
            $mform->setDefault('teamcohortname', $cohort->name);
            $mform->addRule('teamcohortname', get_string('required'), 'required', null, 'client');
        }

        $this->add_action_buttons(true, get_string('team_update', 'tool_murelation'));

        $this->set_data($supervisor);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $supervisor = $this->_customdata['supervisor'];
        $context = $this->_customdata['context'];

        if (trim($data['teamname']) === '') {
            $errors['teamname'] = get_string('required');
        }

        if (trim($data['teamidnumber']) !== $data['teamidnumber']) {
            $errors['teamidnumber'] = get_string('error');
        } else if ($data['teamidnumber'] !== '') {
            $select = "LOWER(teamidnumber) = LOWER(?) AND id <> ?";
            $params = [$data['teamidnumber'], $supervisor->id];
            if ($DB->record_exists_select('tool_murelation_supervisor', $select, $params)) {
                $errors['teamidnumber'] = get_string('error');
            }
        }

        if ($data['userid']) {
            $error = team_update_userid::validate_value($data['userid'], $this->wsarguments, $context);
            if ($error !== null) {
                $errors['userid'] = $error;
            }
        }

        if ($data['maxsubordinates'] < 0) {
            $errors['maxsubordinates'] = get_string('error');
        }

        $cohort = false;
        if ($supervisor->teamcohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid]);
        }
        if ($cohort || $data['teamcohortcreate']) {
            if (trim($data['teamcohortname']) === '') {
                $errors['teamcohortname'] = get_string('required');
            }
        }

        return $errors;
    }
}
