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

namespace tool_mulib\local\form;

/**
 * Notification delete form.
 *
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $notification = $this->_customdata['notification'];
        /** @var class-string<\tool_mulib\local\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $notification->id);

        $instance = $manager::get_instance_name($notification->instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_instance', 'tool_mulib'), $instance);

        $types = $manager::get_all_types();
        $type = $types[$notification->notificationtype] ?? null;
        if ($type) {
            $type = $type::get_name();
        } else {
            $type = get_string('error');
        }
        $mform->addElement('static', 'staticnotificationtype', get_string('notification_type', 'tool_mulib'), $type);

        $warning = '<em>' . get_string('notification_delete_confirm', 'tool_mulib') . '</em>';
        $mform->addElement('static', 'staticwarning', '', $warning);

        $this->add_action_buttons(true, get_string('notification_delete', 'tool_mulib'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
