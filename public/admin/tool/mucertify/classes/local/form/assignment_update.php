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
 * Update user assignment.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_update extends \tool_mulib\local\ajax_form {
    /** @var \tool_mucertify\customfield\assignment_handler */
    protected $handler;

    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $certification = $this->_customdata['certification'];
        $assignment = $this->_customdata['assignment'];
        $user = $this->_customdata['user'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        if ($certification->recertify !== null) {
            $stoprecertify = !$DB->record_exists('tool_mucertify_period', [
                'certificationid' => $assignment->certificationid,
                'userid' => $assignment->userid,
                'recertifiable' => 1,
            ]);

            $mform->addElement('advcheckbox', 'stoprecertify', get_string('stoprecertify', 'tool_mucertify'), ' ');
            $mform->setDefault('stoprecertify', $stoprecertify);
        }

        $mform->addElement('date_time_selector', 'timecertifiedtemp', get_string('certifieduntiltemporary', 'tool_mucertify'), ['optional' => true]);
        $mform->setDefault('timecertifiedtemp', $assignment->timecertifiedtemp);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $assignment->id);

        // Add custom fields to the form.
        $this->handler = \tool_mucertify\customfield\assignment_handler::create();
        $this->handler->instance_form_definition($mform);

        $this->add_action_buttons(true, get_string('assignment_update', 'tool_mucertify'));

        // Prepare custom fields data.
        $data = (object)['id' => $assignment->id];
        $this->handler->instance_form_before_set_data($data);
        $this->set_data($data);
    }

    #[\Override]
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;
        $assignment = $this->_customdata['assignment'];
        $this->handler->instance_form_definition_after_data($mform, $assignment->id);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Add the custom fields validation.
        $errors = array_merge($errors, $this->handler->instance_form_validation($data, $files));

        return $errors;
    }
}
