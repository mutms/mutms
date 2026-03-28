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

use tool_murelation\external\form_autocomplete\team_create_userid;
use tool_murelation\external\form_autocomplete\team_create_subuserids;

/**
 * Create team.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class team_create extends \tool_mulib\local\ajax_form {
    /** @var array */
    protected $wsarguments;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $framework = $this->_customdata['framework'];
        $tenantid = $this->_customdata['tenantid'];
        $context = $this->_customdata['context'];
        $this->wsarguments = ['frameworkid' => $framework->id, 'tenantid' => $tenantid];

        $supervisortitle = format_string($framework->supervisortitle);
        $subordinatestitle = format_string($framework->subordinatestitle);

        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->setDefault('frameworkid', $framework->id);

        $mform->addElement('static', 'fwname', get_string('framework_name', 'tool_murelation'), format_string($framework->name));
        if ($framework->idnumber !== null) {
            $mform->addElement('static', 'fwidnumber', get_string('framework_idnumber', 'tool_murelation'), s($framework->idnumber));
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active() && $tenantid) {
            $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
            $mform->addElement('static', 'tenant', get_string('tenant', 'tool_mutenancy'), format_string($tenant->name));
        }

        $mform->addElement('text', 'teamname', get_string('team_name', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->setType('teamname', PARAM_TEXT);
        $mform->addRule('teamname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'teamidnumber', get_string('team_idnumber', 'tool_murelation'), 'maxlength="100" size="50"');
        $mform->setType('teamidnumber', PARAM_RAW); // Idnumbers are plain text.

        team_create_userid::add_element($mform, $this->wsarguments, 'userid', $supervisortitle, $context);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('advcheckbox', 'supmanaged', get_string('team_supmanaged', 'tool_murelation'));

        $mform->addElement('text', 'maxsubordinates', get_string('team_maxsubordinates', 'tool_murelation'), ['size' => 3]);
        $mform->setType('maxsubordinates', PARAM_INT);

        team_create_subuserids::add_element($mform, $this->wsarguments, 'subuserids', $subordinatestitle, $context);

        $mform->addElement('advcheckbox', 'teamcohortcreate', get_string('team_cohort_create', 'tool_murelation'));

        $mform->addElement('text', 'teamcohortname', get_string('team_cohort_name', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->setType('teamcohortname', PARAM_TEXT);
        $mform->hideIf('teamcohortname', 'teamcohortcreate', 'notchecked');

        $this->add_action_buttons(true, get_string('team_create', 'tool_murelation'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        if (trim($data['teamname']) === '') {
            $errors['teamname'] = get_string('required');
        }

        if (trim($data['teamidnumber']) !== $data['teamidnumber']) {
            $errors['teamidnumber'] = get_string('error');
        } else if ($data['teamidnumber'] !== '') {
            if ($DB->record_exists_select('tool_murelation_supervisor', "LOWER(teamidnumber) = LOWER(?)", [$data['teamidnumber']])) {
                $errors['teamidnumber'] = get_string('error');
            }
        }

        if ($data['userid']) {
            $error = team_create_userid::validate_value($data['userid'], $this->wsarguments, $context);
            if ($error !== null) {
                $errors['userid'] = $error;
            }
        }

        if ($data['maxsubordinates'] < 0) {
            $errors['maxsubordinates'] = get_string('error');
        }

        if (isset($data['subuserids'])) {
            foreach ($data['subuserids'] as $userid) {
                $error = team_create_subuserids::validate_value($userid, $this->wsarguments, $context);
                if ($error !== null) {
                    $errors['subuserids'] = $error;
                    break;
                }
            }
            if ($data['maxsubordinates'] && count($data['subuserids']) > $data['maxsubordinates']) {
                $errors['subuserids'] = get_string('error');
            }
        }

        if ($data['teamcohortcreate']) {
            if (trim($data['teamcohortname']) === '') {
                $errors['teamcohortname'] = get_string('required');
            }
        }

        return $errors;
    }
}
