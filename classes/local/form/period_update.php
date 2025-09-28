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

/**
 * Edit user period.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class period_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $period = $this->_customdata['period'];
        $user = $this->_customdata['user'];
        $program = $this->_customdata['program'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'programname', get_string('program', 'tool_muprog'), format_string($program->fullname ?? null));

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $mform->addElement('date_time_selector', 'timewindowstart', get_string('windowstartdate', 'tool_mucertify'), ['optional' => false]);
        $mform->setDefault('timewindowstart', $period->timewindowstart);

        $mform->addElement('date_time_selector', 'timewindowdue', get_string('windowduedate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timewindowdue', $period->timewindowdue);

        $mform->addElement('date_time_selector', 'timewindowend', get_string('windowenddate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timewindowend', $period->timewindowend);

        $mform->addElement('date_time_selector', 'timefrom', get_string('fromdate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timefrom', $period->timefrom);

        $mform->addElement('date_time_selector', 'timeuntil', get_string('untildate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timeuntil', $period->timeuntil);

        $mform->addElement('date_time_selector', 'timecertified', get_string('certifieddate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timecertified', $period->timecertified);

        $mform->addElement('date_time_selector', 'timerevoked', get_string('revokeddate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timerevoked', $period->timerevoked);

        $mform->addElement('textarea', 'evidencedetails', get_string('evidence_details', 'tool_mucertify'));
        $mform->addHelpButton('evidencedetails', 'evidence_details', 'tool_mucertify');
        $mform->setType('evidencedetails', PARAM_RAW); // Plain text only.
        if ($period->evidencejson) {
            $jsondata = (object)json_decode($period->evidencejson);
            if (isset($jsondata->details)) {
                $mform->setDefault('evidencedetails', $jsondata->details);
            }
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $period->id);

        $this->add_action_buttons(true, get_string('period_update', 'tool_mucertify'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['timewindowdue'] && $data['timewindowdue'] <= $data['timewindowstart']) {
            $errors['timewindowdue'] = get_string('error');
        }
        if ($data['timewindowend'] && $data['timewindowend'] <= $data['timewindowstart']) {
            $errors['timewindowend'] = get_string('error');
        }
        if ($data['timewindowdue'] && $data['timewindowend'] && $data['timewindowend'] < $data['timewindowdue']) {
            $errors['timewindowend'] = get_string('error');
        }

        if ($data['timecertified']) {
            if (!$data['timefrom']) {
                $errors['timefrom'] = get_string('required');
            }
        }
        if ($data['timefrom'] && $data['timeuntil'] && $data['timefrom'] >= $data['timeuntil']) {
            $errors['timeuntil'] = get_string('error');
        }

        return $errors;
    }
}
