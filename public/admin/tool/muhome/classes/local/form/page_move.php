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

use tool_muhome\external\form_autocomplete\page_contextid;

/**
 * Move page to a different context.
 *
 * @package    tool_muhome
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_move extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $currentdata = $this->_customdata['currentdata'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'staticname', get_string('page_name', 'tool_muhome'), format_string($currentdata->name));

        $mform->addElement('static', 'statictitle', get_string('page_title', 'tool_muhome'), format_string($currentdata->title));

        page_contextid::add_element($mform, [], 'contextid', get_string('page_category', 'tool_muhome'), $context);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('page_move', 'tool_muhome'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $context = $this->_customdata['context'];

        $errors = parent::validation($data, $files);

        $error = page_contextid::validate_value($data['contextid'], [], $context);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        return $errors;
    }
}
