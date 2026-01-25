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
 * Add courses to program.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_create_course extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $currentdata = $this->_customdata['currentdata'];
        $types = $this->_customdata['types'];
        /** @var set $parent */
        $parent = $this->_customdata['parent'];
        /** @var \context $context */
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'statictype', get_string('item_type', 'tool_muprog'), $types[$currentdata['type']]);

        $args = ['programid' => $parent->get_programid()];
        item_create_course_courseids::add_element(
            $mform,
            $args,
            'courseids',
            get_string('courses'),
            $context
        );
        $mform->addRule('courseids', get_string('required'), 'required', null, 'client');

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

        $this->add_action_buttons(true, get_string('item_create_course', 'tool_muprog'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /** @var set $parent */
        $parent = $this->_customdata['parent'];
        /** @var \context $context */
        $context = $this->_customdata['context'];

        if ($data['courseids']) {
            $args = ['programid' => $parent->get_programid()];
            foreach ($data['courseids'] as $courseid) {
                $error = item_create_course_courseids::validate_value($courseid, $args, $context);
                if ($error !== null) {
                    $errors['courseids'] = $error;
                    break;
                }
            }
        } else {
            $errors['courseids'] = get_string('required');
        }

        if ($data['points'] < 0) {
            $errors['points'] = get_string('error');
        }

        return $errors;
    }
}
