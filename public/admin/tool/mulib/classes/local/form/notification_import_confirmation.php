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

use tool_mulib\local\notification\manager;

/**
 * Notification import confirmation form.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_import_confirmation extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $component = $this->_customdata['component'];
        $instanceid = $this->_customdata['instanceid'];
        $frominstance = $this->_customdata['frominstance'];
        /** @var class-string<\tool_mulib\local\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setConstant('instanceid', $instanceid);

        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_COMPONENT);
        $mform->setConstant('component', $component);

        $mform->addElement('hidden', 'frominstance');
        $mform->setType('frominstance', PARAM_INT);
        $mform->setConstant('frominstance', $frominstance);

        $fromname = $manager::get_instance_name($instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_import_from', 'tool_mulib'), $fromname);

        $types = $manager::get_all_types();

        $notifications = $DB->get_records(
            'tool_mulib_notification',
            ['instanceid' => $frominstance, 'component' => $component, 'enabled' => 1]
        );
        foreach ($notifications as $notification) {
            if (!isset($types[$notification->notificationtype])) {
                continue;
            }
            $classname = $types[$notification->notificationtype];
            $mform->addElement(
                'advcheckbox',
                'notificationid_' . $notification->id,
                $classname::get_name(),
                null,
                ['group' => 1]
            );
        }
        $this->add_checkbox_controller(1);

        $this->add_action_buttons(true, get_string('notification_import', 'tool_mulib'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
