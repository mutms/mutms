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

/**
 * Select item type to add.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $currentdata = $this->_customdata['currentdata'];
        $types = $this->_customdata['types'];

        $radios = [];
        foreach ($types as $k => $v) {
            $radios[] = $mform->createElement('radio', 'type', '', $v, $k);
        }
        $mform->addElement('group', 'type_group', get_string('item_type', 'tool_muprog'), $radios, '<div class="w-100" />', false);
        $mform->addRule('type_group', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'parentid');
        $mform->setType('parentid', PARAM_INT);

        $this->add_action_buttons(true, get_string('continue'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['type'])) {
            $errors['type_group'] = get_string('required');
        }

        return $errors;
    }
}
