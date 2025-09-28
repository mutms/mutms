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
 * Edit item completion data.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_completion_override extends \tool_mulib\local\ajax_form {
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

        if ($allocation->timecompleted) {
            $mform->addElement(
                'static',
                'staticprogramcompletion',
                get_string('programcompletion', 'tool_muprog'),
                userdate($allocation->timecompleted)
            );
        }

        if ($evidence) {
            $mform->addElement(
                'static',
                'staticevidencedate',
                get_string('evidencedate', 'tool_muprog'),
                userdate($evidence->timecompleted)
            );
        }

        $mform->addElement('date_time_selector', 'timecompleted', get_string('completiondate', 'tool_muprog'), ['optional' => true]);
        if ($completion && $completion->timecompleted) {
            $mform->setDefault('timecompleted', $completion->timecompleted);
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

        return $errors;
    }
}
