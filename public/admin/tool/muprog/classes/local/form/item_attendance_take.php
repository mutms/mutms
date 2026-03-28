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

use tool_muprog\local\content\attendance;

/**
 * Take offline attendance data.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_attendance_take extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $attendance = $this->_customdata['attendance'];
        $user = $this->_customdata['user'];
        $item = $this->_customdata['item'];

        $mform->addElement('static', 'staticuser', get_string('user'), fullname($user));

        $mform->addElement('static', 'staticfullname', get_string('fullname'), format_string($item->fullname));

        $radios = [];
        foreach (attendance::get_statuses() as $k => $v) {
            $radios[] = $mform->createElement('radio', 'status', '', $v, $k);
        }
        $mform->addElement('group', 'status_group', get_string('attendance_status', 'tool_muprog'), $radios, '<div class="w-100" />', false);

        $mform->addElement('date_time_selector', 'timeeffective', get_string('attendance_effective', 'tool_muprog'), ['optional' => false]);
        $mform->hideIf('timeeffective', 'status', 'eq', attendance::STATUS_NOTSET);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $this->add_action_buttons(true, get_string('attendance_take', 'tool_muprog'));

        $this->set_data($attendance);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!isset($data['status'])) {
            $errors['status_group'] = get_string('required');
        }

        return $errors;
    }
}
