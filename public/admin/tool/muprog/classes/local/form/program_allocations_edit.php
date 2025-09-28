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
 * Edit program allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_allocations_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $mform->addElement('date_time_selector', 'timeallocationstart', get_string('allocationstart', 'tool_muprog'), ['optional' => true]);
        $mform->addHelpButton('timeallocationstart', 'allocationstart', 'tool_muprog');

        $mform->addElement('date_time_selector', 'timeallocationend', get_string('allocationend', 'tool_muprog'), ['optional' => true]);
        $mform->addHelpButton('timeallocationend', 'allocationend', 'tool_muprog');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $data->id);

        $this->add_action_buttons(true, get_string('program_allocations_edit', 'tool_muprog'));

        $this->set_data($data);
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (
            $data['timeallocationstart'] && $data['timeallocationend']
            && $data['timeallocationstart'] >= $data['timeallocationend']
        ) {
            $errors['timeallocationend'] = get_string('error');
        }

        return $errors;
    }
}
