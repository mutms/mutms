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
 * Assign users via file upload.
 *
 * @package    tool_mucertify
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class history_upload_options extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $certification = $this->_customdata['certification'];
        $source = $this->_customdata['source'];
        $context = $this->_customdata['context'];
        $csvfile = $this->_customdata['csvfile'];
        $filedata = $this->_customdata['filedata'];

        $preview = new \html_table();
        $preview->data = [];
        $i = 0;
        foreach ($filedata as $row) {
            $i++;
            if ($i > 5) {
                $preview->data[] = array_fill(0, count($row), '...');
                break;
            }
            $preview->data[] = array_map('s', $row);
        }
        $mform->addElement('static', 'preview', get_string('preview'), \html_writer::table($preview));

        $fileoptions = reset($filedata);
        $mform->addElement('select', 'usercolumn', get_string('source_manual_usercolumn', 'tool_mucertify'), $fileoptions);
        $firstcolumn = reset($fileoptions);

        $options = [
            'username' => get_string('username'),
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
        ];
        $mform->addElement('select', 'usermapping', get_string('source_manual_usermapping', 'tool_mucertify'), $options);
        if (isset($options[$firstcolumn])) {
            $mform->setDefault('usermapping', $firstcolumn);
        }

        $mform->addElement('advcheckbox', 'hasheaders', get_string('source_manual_hasheaders', 'tool_mucertify'));
        if (isset($options[$filedata[0][0]])) {
            $mform->setDefault('hasheaders', 1);
        }

        if ($source && has_capability('tool/mucertify:assign', $context)) {
            $mform->addElement('advcheckbox', 'assign', get_string('history_upload_assign', 'tool_mucertify'));
            $mform->setDefault('assign', 0);

            $mform->addElement('advcheckbox', 'skipassigned', get_string('history_upload_skipassigned', 'tool_mucertify'));
            $mform->setDefault('skipassigned', 0);
            $mform->hideIf('skipassigned', 'assign', 'eq', '0');
        }

        $options = ['' => get_string('choosedots')] + $fileoptions;
        $mform->addElement('select', 'timefromcolumn', get_string('history_upload_timefromcolumn', 'tool_mucertify'), $options);
        $mform->addRule('timefromcolumn', get_string('required'), 'required', null, 'client');
        if (in_array('from', $fileoptions)) {
            $mform->setDefault('timefromcolumn', array_search('from', $fileoptions));
        }
        $mform->addElement('select', 'timeuntilcolumn', get_string('history_upload_timeuntilcolumn', 'tool_mucertify'), $options);
        $mform->addRule('timeuntilcolumn', get_string('required'), 'required', null, 'client');
        if (in_array('expiration', $fileoptions)) {
            $mform->setDefault('timeuntilcolumn', array_search('expiration', $fileoptions));
        }
        $mform->addElement('select', 'timecertifiedcolumn', get_string('history_upload_timecertifiedcolumn', 'tool_mucertify'), $options);
        $mform->addRule('timecertifiedcolumn', get_string('required'), 'required', null, 'client');
        if (in_array('certified', $fileoptions)) {
            $mform->setDefault('timecertifiedcolumn', array_search('certified', $fileoptions));
        } else if (in_array('from', $fileoptions)) {
            $mform->setDefault('timecertifiedcolumn', array_search('from', $fileoptions));
        }

        $mform->addElement('select', 'evidencecolumn', get_string('history_upload_evidencecolumn', 'tool_mucertify'), $options);
        if (in_array('evidence', $fileoptions)) {
            $mform->setDefault('evidencecolumn', array_search('evidence', $fileoptions));
        }

        $mform->addElement('textarea', 'evidencedefault', get_string('evidence_default', 'tool_mucertify'));
        $mform->addRule('evidencedefault', get_string('required'), 'required', null, 'client');
        $mform->setDefault('evidencedefault', get_string('evidence_default_text', 'tool_mucertify'));

        $mform->addElement('hidden', 'certificationid');
        $mform->setType('certificationid', PARAM_INT);
        $mform->setDefault('certificationid', $certification->id);

        $mform->addElement('hidden', 'csvfile');
        $mform->setType('csvfile', PARAM_INT);
        $mform->setDefault('csvfile', $csvfile);

        $this->add_action_buttons(true, get_string('history_upload', 'tool_mucertify'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $columns = ['timefromcolumn', 'timeuntilcolumn', 'timecertifiedcolumn'];
        foreach ($columns as $column) {
            if ($data[$column] != '' && $data[$column] === $data['usercolumn']) {
                $errors[$column] = get_string('columnusedalready', 'tool_mucertify');
            }
        }
        if ($data['timefromcolumn'] !== '' && $data['timefromcolumn'] === $data['timeuntilcolumn']) {
            $errors['timeuntilcolumn'] = get_string('columnusedalready', 'tool_mucertify');
        }
        if ($data['timecertifiedcolumn'] !== '' && $data['timecertifiedcolumn'] === $data['timeuntilcolumn']) {
            $errors['timeuntilcolumn'] = get_string('columnusedalready', 'tool_mucertify');
        }

        return $errors;
    }
}
