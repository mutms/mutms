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
 * Notification update form.
 *
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $notification = $this->_customdata['notification'];
        /** @var class-string<\tool_mulib\local\notification\manager> $manager */
        $manager = $this->_customdata['manager'];
        /** @var class-string<\tool_mulib\local\notification\notificationtype> $classname */
        $classname = $manager::get_classname($notification->notificationtype);

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

        $mform->addElement('advcheckbox', 'enabled', get_string('notification_enabled', 'tool_mulib'), ' ');
        $mform->setDefault('enabled', $notification->enabled);

        // Note: Add aux data support here.

        $mform->addElement('advcheckbox', 'custom', get_string('notification_custom', 'tool_mulib'), ' ');
        $mform->setDefault('custom', $notification->custom);

        $subject = '';
        $body = '';
        if ($notification->custom) {
            if ($notification->customjson) {
                $decoded = json_decode($notification->customjson, true);
                $subject = $decoded['subject'] ?? '';
                $body = $decoded['body'] ?? '';
            }
        } else {
            $subject = $classname::get_default_subject();
            $body = markdown_to_html($classname::get_default_body());
            $body = str_replace('{$a->', '{$a-&gt;', $body);
        }

        $mform->addElement('text', 'subject', get_string('notification_subject', 'tool_mulib'), ['size' => 100]);
        $mform->setType('subject', PARAM_RAW);
        $mform->setDefault('subject', $subject);
        $mform->hideIf('subject', 'custom', 'notchecked');

        // Hack: put editor into group to work around hideIf editor incompatibility.
        $editor = $mform->createElement('editor', 'body', get_string('notification_body', 'tool_mulib'));
        $mform->addElement('group', 'bodygroup', get_string('notification_body', 'tool_mulib'), [$editor], null, false);
        $mform->setType('body', PARAM_RAW);
        $mform->setDefault('body', ['text' => $body, 'format' => FORMAT_HTML]);
        $mform->hideIf('bodygroup', 'custom', 'notchecked');

        $this->add_action_buttons(true, get_string('notification_update', 'tool_mulib'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
