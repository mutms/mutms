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

use tool_mucertify\local\period;
use tool_mucertify\external\form_autocomplete\certification_periods_programid;

/**
 * Edit user period.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class period_create extends \tool_mulib\local\ajax_form {
    /** @var array autocompletion arguments */
    protected $arguments;

    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $certification = $this->_customdata['certification'];
        $assignment = $this->_customdata['assignment'];
        $user = $this->_customdata['user'];
        $context = $this->_customdata['context'];
        $now = time();

        $firstperiod = $DB->get_record('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user->id, 'first' => 1]);

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $this->arguments = ['certificationid' => $certification->id];
        $settings = \tool_mucertify\local\certification::get_periods_settings($certification);

        $defaultdates = period::get_default_dates($certification, $user->id, []);

        certification_periods_programid::add_element(
            $mform,
            $this->arguments,
            'programid',
            get_string('program', 'tool_muprog'),
            $context
        );
        if ($firstperiod) {
            $mform->setDefault('programid', $settings->programid2);
        } else {
            $mform->setDefault('programid', $settings->programid1);
        }
        $mform->addRule('programid', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_time_selector', 'timewindowstart', get_string('windowstartdate', 'tool_mucertify'), ['optional' => false]);
        $mform->setDefault('timewindowstart', $defaultdates['timewindowstart']);

        $mform->addElement('date_time_selector', 'timewindowdue', get_string('windowduedate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timewindowdue', $defaultdates['timewindowdue']);

        $mform->addElement('date_time_selector', 'timewindowend', get_string('windowenddate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timewindowend', $defaultdates['timewindowend']);

        $mform->addElement('date_time_selector', 'timefrom', get_string('fromdate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timefrom', $defaultdates['timefrom']);

        $mform->addElement('date_time_selector', 'timeuntil', get_string('untildate', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timeuntil', $defaultdates['timeuntil']);

        $mform->addElement('hidden', 'assignmentid');
        $mform->setType('assignmentid', PARAM_INT);
        $mform->setDefault('assignmentid', $assignment->id);

        $this->add_action_buttons(true, get_string('period_create', 'tool_mucertify'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        if ($data['timewindowdue'] && $data['timewindowdue'] <= $data['timewindowstart']) {
            $errors['timewindowdue'] = get_string('error');
        }
        if ($data['timewindowend'] && $data['timewindowend'] <= $data['timewindowstart']) {
            $errors['timewindowend'] = get_string('error');
        }
        if ($data['timewindowdue'] && $data['timewindowend'] && $data['timewindowend'] < $data['timewindowdue']) {
            $errors['timewindowend'] = get_string('error');
        }
        if ($data['timefrom'] && $data['timeuntil'] && $data['timefrom'] >= $data['timeuntil']) {
            $errors['timeuntil'] = get_string('error');
        }

        $error = certification_periods_programid::validate_value($data['programid'], $this->arguments, $context);
        if ($error !== null) {
            $errors['programid'] = $error;
        }

        return $errors;
    }
}
