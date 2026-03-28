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

namespace tool_muprog\local\form;

/**
 * Edit item completion evidence data.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_evidence_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $allocation = $this->_customdata['allocation'];
        $item = $this->_customdata['item'];
        $completion = $this->_customdata['completion'];
        $evidence = $this->_customdata['evidence'];

        $mform->addElement(
            'static',
            'staticitem',
            get_string('item', 'tool_muprog'),
            format_string($item->fullname)
        );

        if ($completion && $completion->timecompleted) {
            $strcompleted = userdate($completion->timecompleted);
        } else {
            $strcompleted = get_string('notset', 'tool_muprog');
        }
        $mform->addElement('static', 'statictimecompleted', get_string('completiondate', 'tool_muprog'), $strcompleted);

        $mform->addElement('date_time_selector', 'evidencetimecompleted', get_string('evidencedate', 'tool_muprog'), ['optional' => true]);
        if ($evidence && $evidence->timecompleted) {
            $mform->setDefault('evidencetimecompleted', $evidence->timecompleted);
        }

        $mform->addElement('textarea', 'evidencedetails', get_string('evidence_details', 'tool_muprog'));
        $mform->setType('evidencedetails', PARAM_RAW); // Plain text only.
        if ($evidence && $evidence->evidencejson) {
            $data = (object)json_decode($evidence->evidencejson);
            if ($data->details) {
                $mform->setDefault('evidencedetails', $data->details);
            }
        }
        $mform->hideIf('evidencedetails', 'evidencetimecompleted[enabled]', 'notchecked');

        $mform->addElement('advcheckbox', 'itemrecalculate', get_string('itemrecalculate', 'tool_muprog'));
        if (!$item->topitem && $evidence && $completion && $evidence->timecompleted == $completion->timecompleted) {
            $mform->setDefault('itemrecalculate', 1);
        }

        $mform->addElement('hidden', 'allocationid');
        $mform->setType('allocationid', PARAM_INT);
        $mform->setDefault('allocationid', $allocation->id);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);
        $mform->setDefault('itemid', $item->id);

        $this->add_action_buttons(true, get_string('update'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['evidencetimecompleted']) {
            if (trim($data['evidencedetails']) === '') {
                $errors['evidencedetails'] = get_string('required');
            }
        }

        return $errors;
    }
}
