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

use tool_muprog\local\allocation;
use tool_muprog\local\course_reset;

/**
 * Reset user allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_reset extends \tool_mulib\local\ajax_form {
    /** @var bool editing supported*/
    private $editsupported;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $source = $this->_customdata['source'];
        $allocation = $this->_customdata['allocation'];
        $user = $this->_customdata['user'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $options = [
            course_reset::RESETTYPE_STANDARD => new \lang_string('resettype_standard', 'tool_muprog'),
            course_reset::RESETTYPE_FULL => new \lang_string('resettype_full', 'tool_muprog'),
        ];
        $mform->addElement('select', 'resettype', get_string('resettype', 'tool_muprog'), $options);

        $sourceclass = allocation::get_source_classname($source->type);
        if ($sourceclass && $sourceclass::is_allocation_update_possible($program, $source, $allocation)) {
            $this->editsupported = true;
            $mform->addElement('advcheckbox', 'updateallocation', get_string('allocation_reset_updateallocation', 'tool_muprog'));
            $mform->addElement('date_time_selector', 'timestart', get_string('programstart_date', 'tool_muprog'), ['optional' => false]);
            $mform->disabledIf('timestart', 'updateallocation', 'eq', 0);
            $mform->addElement('date_time_selector', 'timedue', get_string('programdue_date', 'tool_muprog'), ['optional' => true]);
            $mform->disabledIf('timedue', 'updateallocation', 'eq', 0);
            $mform->addElement('date_time_selector', 'timeend', get_string('programend_date', 'tool_muprog'), ['optional' => true]);
            $mform->disabledIf('timeend', 'updateallocation', 'eq', 0);
        } else {
            $this->editsupported = false;
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $allocation->id);

        $this->add_action_buttons(true, get_string('allocation_reset', 'tool_muprog'));

        $this->set_data($allocation);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($this->editsupported && $data['updateallocation']) {
            $errors = array_merge($errors, \tool_muprog\local\allocation::validate_allocation_dates(
                $data['timestart'],
                $data['timedue'],
                $data['timeend']
            ));
        }

        return $errors;
    }
}
