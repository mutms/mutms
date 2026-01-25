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

namespace tool_muhome\local\form;

use tool_muhome\external\form_autocomplete\page_cohortvisible;
use tool_muhome\local\page;

/**
 * Update a page.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $currentdata = $this->_customdata['currentdata'];
        $context = $this->_customdata['context'];

        $mform->addElement('text', 'name', get_string('page_name', 'tool_muhome'), 'maxlength="1333" size="100"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'title', get_string('page_title', 'tool_muhome'), 'maxlength="1333" size="100"');
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('text', 'priority', get_string('page_priority', 'tool_muhome'), 'size="5"');
        $mform->setType('priority', PARAM_INT);

        $mform->addElement('advcheckbox', 'guestvisible', get_string('guestvisible', 'tool_muhome'), ' ');

        $mform->addElement('advcheckbox', 'uservisible', get_string('uservisible', 'tool_muhome'), ' ');

        page_cohortvisible::add_element(
            $mform,
            ['pageid' => $currentdata->id, 'contextid' => $context->id],
            'cohortvisible',
            get_string('cohortvisible', 'tool_muhome'),
            $context
        );
        $mform->hideIf('cohortvisible', 'uservisible', 'eq', 1);

        $mform->addElement('date_time_selector', 'hiddenbefore', get_string('hiddenbefore', 'tool_muhome'), ['optional' => true]);

        $mform->addElement('date_time_selector', 'hiddenafter', get_string('hiddenafter', 'tool_muhome'), ['optional' => true]);

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $mform->addElement('advcheckbox', 'hiddenfromtenants', get_string('hiddenfromtenants', 'tool_muhome'), ' ');
        }

        $options = page::get_statuses_menu();
        $radios = [];
        foreach ($options as $k => $v) {
            $radios[] = $mform->createElement('radio', 'status', '', $v, $k);
        }
        $mform->addElement('group', 'status_group', get_string('page_status', 'tool_muhome'), $radios, '<div class="w-100" />', false);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('update'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $context = $this->_customdata['context'];

        $errors = parent::validation($data, $files);

        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }

        if ($data['hiddenbefore'] && $data['hiddenafter'] && $data['hiddenbefore'] > $data['hiddenafter']) {
            $errors['hiddenafter'] = get_string('error');
        }

        if (!$data['uservisible']) {
            $args = ['pageid' => 0, 'contextid' => $context->id];
            foreach ($data['cohortvisible'] as $cohortid) {
                $error = page_cohortvisible::validate_value($cohortid, $args, $context);
                if ($error !== null) {
                    $errors['cohortvisible'] = $error;
                    break;
                }
            }
        }

        return $errors;
    }
}
