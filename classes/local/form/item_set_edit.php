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

use tool_muprog\local\content\set;
use tool_muprog\local\content\top;

/**
 * Edit program content item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_set_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var set $set */
        $set = $this->_customdata['set'];

        if ($set instanceof top) {
            $mform->addElement('static', 'fullname', get_string('fullname'), format_string($set->get_fullname()));
        } else {
            $mform->addElement('text', 'fullname', get_string('fullname'), 'maxlength="254" size="50"');
            $mform->setType('fullname', PARAM_TEXT);
            $mform->setDefault('fullname', format_string($set->get_fullname()));
            $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        }

        if (!$set instanceof top) {
            $mform->addElement('text', 'points', get_string('itempoints', 'tool_muprog'));
            $mform->setType('points', PARAM_INT);
            $mform->setDefault('points', $set->get_points());
        }

        $stypes = set::get_sequencetype_types();
        $mform->addElement('select', 'sequencetype', get_string('sequencetype', 'tool_muprog'), $stypes);
        $mform->setDefault('sequencetype', $set->get_sequencetype());

        $mform->addElement('text', 'minprerequisites', $stypes[set::SEQUENCE_TYPE_ATLEAST]);
        $mform->setType('minprerequisites', PARAM_INT);
        $mform->hideIf('minprerequisites', 'sequencetype', 'noteq', set::SEQUENCE_TYPE_ATLEAST);
        if ($set->get_sequencetype() === set::SEQUENCE_TYPE_ATLEAST) {
            $minprerequisites = $set->get_minprerequisites();
        } else {
            $minprerequisites = count($set->get_children());
        }
        $mform->setDefault('minprerequisites', $minprerequisites);

        $mform->addElement('text', 'minpoints', $stypes[set::SEQUENCE_TYPE_MINPOINTS]);
        $mform->setType('minpoints', PARAM_INT);
        $mform->hideIf('minpoints', 'sequencetype', 'noteq', set::SEQUENCE_TYPE_MINPOINTS);
        if ($set->get_sequencetype() === set::SEQUENCE_TYPE_MINPOINTS) {
            $minpoints = $set->get_minpoints();
        } else {
            $minpoints = 0;
            foreach ($set->get_children() as $child) {
                $minpoints += $child->get_points();
            }
        }
        $mform->setDefault('minpoints', $minpoints);

        $mform->addElement(
            'duration',
            'completiondelay',
            get_string('completiondelay', 'tool_muprog'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );
        $mform->setDefault('completiondelay', $set->get_completiondelay());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $set->get_id());

        $this->add_action_buttons(true, get_string('updateset', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /** @var set $set */
        $set = $this->_customdata['set'];

        if (!$set instanceof top) {
            if ($data['points'] < 0) {
                $errors['points'] = get_string('error');
            }
            if (trim($data['fullname']) === '') {
                $errors['fullname'] = get_string('required');
            }
        }

        if ($data['sequencetype'] === set::SEQUENCE_TYPE_ATLEAST) {
            if ($data['minprerequisites'] <= 0) {
                $errors['minprerequisites'] = get_string('required');
            }
        } else if ($data['sequencetype'] === set::SEQUENCE_TYPE_MINPOINTS) {
            if ($data['minpoints'] <= 0) {
                $errors['minpoints'] = get_string('required');
            }
        }

        return $errors;
    }
}
