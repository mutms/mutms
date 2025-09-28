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

/**
 * Delete team.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class team_delete extends \tool_mulib\local\ajax_form {
    /** @var int */
    protected $subcount;

    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $supervisor = $this->_customdata['supervisor'];
        $framework = $this->_customdata['framework'];

        $supervisortitle = format_string($framework->supervisortitle);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $supervisor->id);

        $mform->addElement('static', 'fwname', get_string('framework_name', 'tool_murelation'), format_string($framework->name));
        if ($framework->idnumber !== null) {
            $mform->addElement('static', 'fwidnumber', get_string('framework_idnumber', 'tool_murelation'), s($framework->idnumber));
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active() && $supervisor->tenantid) {
            $tenant = \tool_mutenancy\local\tenant::fetch($supervisor->tenantid);
            $mform->addElement('static', 'tenant', get_string('tenant', 'tool_mutenancy'), format_string($tenant->name));
        }

        if ($supervisor->userid) {
            $user = $DB->get_record('user', ['id' => $supervisor->userid, 'deleted' => 0]);
            if ($user) {
                $username = fullname($user);
            } else {
                $username = get_string('error');
            }
        } else {
            $username = get_string('notset', 'tool_mulib');
        }
        $mform->addElement('static', 'user', $supervisortitle, $username);

        if ($supervisor->teamname !== null) {
            $mform->addElement('static', 'teamname', get_string('team_name', 'tool_murelation'), s($supervisor->teamname));
        }

        if ($supervisor->teamidnumber !== null) {
            $mform->addElement('static', 'teamidnumber', get_string('team_idnumber', 'tool_murelation'), s($supervisor->teamidnumber));
        }

        $this->subcount = $DB->count_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id]);
        if ($this->subcount) {
            $mform->addElement('checkbox', 'confirm', get_string('team_delete_confirm', 'tool_murelation', $this->subcount));
            $mform->addRule('confirm', get_string('required'), 'required', null, 'client');
        }

        $this->add_action_buttons(true, get_string('team_delete', 'tool_murelation'));

        $this->set_data($supervisor);
    }
}
