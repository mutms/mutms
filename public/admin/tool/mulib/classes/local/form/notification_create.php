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

namespace tool_mulib\local\form;

use tool_mulib\local\mulib;

/**
 * Notification create form.
 *
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $component = $this->_customdata['component'];
        $instanceid = $this->_customdata['instanceid'];
        /** @var class-string<\tool_mulib\local\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setConstant('instanceid', $instanceid);

        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_COMPONENT);
        $mform->setConstant('component', $component);

        $instance = $manager::get_instance_name($instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_instance', 'tool_mulib'), $instance);

        $showcc = false;
        $types = $manager::get_candidate_types($instanceid);
        $elements = [];
        foreach ($types as $type => $typename) {
            $classname = $manager::get_classname($type);
            if ($classname && $classname::is_cc_supervisor_supported()) {
                $showcc = true;
            }
            $elements[] = $mform->createElement('checkbox', $type, $typename);
        }
        $mform->addGroup($elements, 'types', get_string('notification_types', 'tool_mulib'), '<div class="w-100 mb-2" />');

        if ($showcc && mulib::is_murelatio_active()) {
            $options = $manager::get_supervisor_options($instanceid, null);
            $mform->addElement('select', 'supervisorframeworkid', get_string('notification_cc_supervisor', 'tool_mulib'), $options);
        }

        $mform->addElement('advcheckbox', 'enabled', get_string('notification_enabled', 'tool_mulib'), ' ');
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons(true, get_string('notification_create', 'tool_mulib'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['types'])) {
            $errors['types'] = get_string('required');
        }

        return $errors;
    }
}
