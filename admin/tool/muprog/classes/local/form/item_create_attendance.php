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

use tool_muprog\local\content\set;
use tool_muprog\external\form_autocomplete\item_create_course_courseids;

/**
 * Add offline attendance to program.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_create_attendance extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $currentdata = $this->_customdata['currentdata'];
        $types = $this->_customdata['types'];

        $mform->addElement('static', 'statictype', get_string('item_type', 'tool_muprog'), $types[$currentdata['type']]);

        $mform->addElement('text', 'fullname', get_string('fullname'), 'maxlength="254" size="50"');
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        $mform->addElement(
            'duration',
            'completiondelay',
            get_string('completiondelay', 'tool_muprog'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );

        $mform->addElement('text', 'points', get_string('itempoints', 'tool_muprog'));
        $mform->setType('points', PARAM_INT);

        $mform->addElement('hidden', 'parentid');
        $mform->setType('parentid', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUM);

        $this->add_action_buttons(true, get_string('item_create_attendance', 'tool_muprog'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim($data['fullname']) === '') {
            $errors['fullname'] = get_string('required');
        }

        if ($data['points'] < 0) {
            $errors['points'] = get_string('error');
        }

        return $errors;
    }
}
