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

use tool_mucertify\external\form_autocomplete\source_manual_assign_users;
use tool_mulib\local\mulib;

/**
 * assign users and cohorts manually.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_assign extends \tool_mulib\local\ajax_form {
    /** @var array $arguments for WS call to get candidate users */
    protected $arguments;
    /** @var bool is due date optional? */
    protected $dueoptional = true;
    /** @var \tool_mucertify\customfield\assignment_handler */
    protected $handler;
    /** @var \stdClass */
    protected $settings;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $certification = $this->_customdata['certification'];
        $source = $this->_customdata['source'];
        $context = $this->_customdata['context'];

        $settings = \tool_mucertify\local\certification::get_periods_settings($certification);
        $this->settings = $settings;

        $this->arguments = ['certificationid' => $certification->id];
        source_manual_assign_users::add_element(
            $mform,
            $this->arguments,
            'users',
            get_string('users'),
            $context
        );

        $options = ['contextid' => $context->id, 'multiple' => false];
        $mform->addElement('cohort', 'cohortid', get_string('cohort', 'cohort'), $options);

        $now = time();
        $mform->addElement(
            'date_time_selector',
            'timewindowstart',
            get_string('windowstartdate', 'tool_mucertify'),
            ['optional' => false]
        );
        $mform->setDefault('timewindowstart', $now);

        if (
            $settings->valid1 === 'windowdue'
            || $settings->windowend1 === 'windowdue'
            || $settings->expiration1 === 'windowdue'
        ) {
            $this->dueoptional = false;
        }
        $mform->addElement(
            'date_time_selector',
            'timewindowdue',
            get_string('windowduedate', 'tool_mucertify'),
            ['optional' => $this->dueoptional]
        );
        if (!$this->dueoptional) {
            $mform->addRule('timewindowdue', get_string('required'), 'required', null, 'client');
        }
        if ($settings->due1 !== null) {
            $mform->setDefault('timewindowdue', $now + $settings->due1);
        }

        if ($settings->recertify) {
            $mform->addElement(
                'date_time_selector',
                'timeuntil',
                get_string('untildate', 'tool_mucertify'),
                ['optional' => true]
            );
        }

        $mform->addElement('hidden', 'certificationid');
        $mform->setType('certificationid', PARAM_INT);
        $mform->setDefault('certificationid', $source->certificationid);

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        // Add custom fields to the form.
        $this->handler = \tool_mucertify\customfield\assignment_handler::create();
        $this->handler->set_new_item_context($context);
        $this->handler->instance_form_definition($mform);

        $this->add_action_buttons(true, get_string('source_manual_assignusers', 'tool_mucertify'));

        // Prepare custom fields data.
        $data = (object)[];
        $this->handler->instance_form_before_set_data($data);
        $this->set_data($data);
    }

    #[\Override]
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;
        $this->handler->instance_form_definition_after_data($mform, 0);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $context = $this->_customdata['context'];

        if ($data['cohortid']) {
            $cohort = $DB->get_record('cohort', ['id' => $data['cohortid']], '*', MUST_EXIST);
            $cohortcontext = \context::instance_by_id($cohort->contextid);
            if (!$cohort->visible && !has_capability('moodle/cohort:view', $cohortcontext)) {
                $errors['cohortid'] = get_string('error');
            }
            if (mulib::is_mutenancy_active()) {
                if ($context->tenantid) {
                    if ($cohortcontext->tenantid && $context->tenantid != $cohortcontext->tenantid) {
                        $errors['cohortid'] = get_string('error');
                    }
                }
            }
        }

        if ($data['users']) {
            foreach ($data['users'] as $userid) {
                $error = source_manual_assign_users::validate_value(
                    $userid,
                    $this->arguments,
                    $context
                );
                if ($error !== null) {
                    $errors['users'] = $error;
                    break;
                }
            }
        }

        if (!$data['users'] && !$data['cohortid']) {
            $errors['users'] = get_string('required');
            $errors['cohortid'] = get_string('required');
        }

        if ($this->settings->recertify && !empty($data['timeuntil'])) {
            if (
                $data['timeuntil'] - $this->settings->recertify <= $data['timewindowstart']
                || $data['timeuntil'] - $this->settings->recertify <= time()
            ) {
                $errors['timeuntil'] = get_string('error');
            }
        }

        // Add the custom fields validation.
        $errors = array_merge($errors, $this->handler->instance_form_validation($data, $files));

        return $errors;
    }
}
