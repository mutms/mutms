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

namespace tool_muprog\local\form;

use tool_muprog\external\form_autocomplete\source_manual_allocate_users;

/**
 * Allocate users and cohorts manually.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_allocate extends \tool_mulib\local\ajax_form {
    /** @var array $arguments for WS call to get candidate users */
    protected $arguments;
    /** @var \tool_muprog\customfield\allocation_handler */
    protected $handler;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $source = $this->_customdata['source'];
        $context = $this->_customdata['context'];

        $this->arguments = ['programid' => $program->id];
        source_manual_allocate_users::add_element(
            $mform,
            $this->arguments,
            'users',
            get_string('users'),
            $context
        );

        $options = ['contextid' => $context->id, 'multiple' => false];
        $mform->addElement('cohort', 'cohortid', get_string('cohort', 'cohort'), $options);

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);
        $mform->setDefault('programid', $source->programid);

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        // Add custom fields to the form.
        $this->handler = \tool_muprog\customfield\allocation_handler::create();
        $this->handler->set_new_item_context($context);
        $this->handler->instance_form_definition($mform);

        $this->add_action_buttons(true, get_string('source_manual_allocateusers', 'tool_muprog'));

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
            if (\tool_muprog\local\util::is_mutenancy_active()) {
                if ($context->tenantid) {
                    if ($cohortcontext->tenantid && $cohortcontext->tenantid != $context->tenantid) {
                        $errors['cohortid'] = get_string('error');
                    }
                }
            }
        }

        if ($data['users']) {
            foreach ($data['users'] as $userid) {
                $error = source_manual_allocate_users::validate_value(
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

        // Add the custom fields validation.
        $errors = array_merge($errors, $this->handler->instance_form_validation($data, $files));

        return $errors;
    }
}
