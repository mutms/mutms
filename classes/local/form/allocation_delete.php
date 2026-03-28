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
 * Delete user allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $allocation = $this->_customdata['allocation'];
        $user = $this->_customdata['user'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $mform->addElement('date_time_selector', 'timeallocated', get_string('allocationdate', 'tool_muprog'), ['optional' => false]);
        $mform->freeze('timeallocated');

        $mform->addElement('date_time_selector', 'timestart', get_string('programstart_date', 'tool_muprog'), ['optional' => false]);
        $mform->freeze('timestart');

        $mform->addElement('date_time_selector', 'timedue', get_string('programdue_date', 'tool_muprog'), ['optional' => true]);
        $mform->freeze('timedue');

        $mform->addElement('date_time_selector', 'timeend', get_string('programend_date', 'tool_muprog'), ['optional' => true]);
        $mform->freeze('timeend');

        $mform->addElement('date_time_selector', 'timecompleted', get_string('completiondate', 'tool_muprog'), ['optional' => true]);
        $mform->freeze('timecompleted');

        $mform->addElement('select', 'archived', get_string('archived', 'tool_muprog'), [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->freeze('archived');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $allocation->id);

        $this->add_action_buttons(true, get_string('deleteallocation', 'tool_muprog'));

        $this->set_data($allocation);
    }
}
