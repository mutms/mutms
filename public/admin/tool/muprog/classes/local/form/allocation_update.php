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
 * Edit user allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_update extends \tool_mulib\local\ajax_form {
    /** @var \tool_muprog\customfield\allocation_handler */
    protected $handler;

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

        $mform->addElement('date_time_selector', 'timedue', get_string('programdue_date', 'tool_muprog'), ['optional' => true]);

        $mform->addElement('date_time_selector', 'timeend', get_string('programend_date', 'tool_muprog'), ['optional' => true]);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $allocation->id);

        // Add custom fields to the form.
        $this->handler = \tool_muprog\customfield\allocation_handler::create();
        $this->handler->instance_form_definition($mform);

        $this->add_action_buttons(true, get_string('allocation_update', 'tool_muprog'));

        // Prepare custom fields data.
        $this->handler->instance_form_before_set_data($allocation);

        $this->set_data($allocation);
    }

    #[\Override]
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;
        $allocation = $this->_customdata['allocation'];
        $this->handler->instance_form_definition_after_data($mform, $allocation->id);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $errors = array_merge($errors, \tool_muprog\local\allocation::validate_allocation_dates(
            $data['timestart'],
            $data['timedue'],
            $data['timeend']
        ));

        // Add the custom fields validation.
        $errors = array_merge($errors, $this->handler->instance_form_validation($data, $files));

        return $errors;
    }
}
