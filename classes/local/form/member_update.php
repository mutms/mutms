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
 * Update team member.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class member_update extends \tool_mulib\local\ajax_form {
    /** @var array */
    protected $wsarguments;

    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $subordinate = $this->_customdata['subordinate'];
        $supervisor = $this->_customdata['supervisor'];
        $framework = $this->_customdata['framework'];
        $this->wsarguments = ['supervisorid' => $supervisor->id];

        $supervisortitle = format_string($framework->supervisortitle);
        $subordinatetitle = format_string($framework->subordinatetitle);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $subordinate->id);

        $mform->addElement('static', 'fwname', get_string('framework_name', 'tool_murelation'), format_string($framework->name));
        if ($framework->idnumber !== null) {
            $mform->addElement('static', 'fwidnumber', get_string('framework_idnumber', 'tool_murelation'), s($framework->idnumber));
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active() && $supervisor->tenantid) {
            $tenant = \tool_mutenancy\local\tenant::fetch($supervisor->tenantid);
            $mform->addElement('static', 'tenant', get_string('tenant', 'tool_mutenancy'), format_string($tenant->name));
        }

        if ($supervisor->teamname !== null) {
            $mform->addElement('static', 'stteamname', get_string('team_name', 'tool_murelation'), s($supervisor->teamname));
        }

        if ($supervisor->teamidnumber !== null) {
            $mform->addElement('static', 'stteamidnumber', get_string('team_idnumber', 'tool_murelation'), s($supervisor->teamidnumber));
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
        $mform->addElement('static', 'supuser', $supervisortitle, $username);

        $subuser = null;
        if ($subordinate->userid) {
            $subuser = $DB->get_record('user', ['id' => $subordinate->userid, 'deleted' => 0]);
        }
        if ($subuser) {
            $fullname = fullname($subuser);
        } else {
            $fullname = get_string('error');
        }
        $mform->addElement('static', 'user', $subordinatetitle, $fullname);

        $mform->addElement('text', 'teamposition', get_string('team_position', 'tool_murelation'), 'maxlength="254" size="50"');
        $mform->setType('teamposition', PARAM_TEXT);
        $mform->setDefault('teamposition', $subordinate->teamposition);

        $this->add_action_buttons(true, get_string('member_update_a', 'tool_murelation', $subordinatetitle));
    }
}
