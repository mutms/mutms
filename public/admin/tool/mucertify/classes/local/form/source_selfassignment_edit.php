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

/**
 * Edit certification self assignment settings.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_selfassignment_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $source = $this->_customdata['source'];
        $certification = $this->_customdata['certification'];

        $mform->addElement('select', 'enable', get_string('active'), ['1' => get_string('yes'), '0' => get_string('no')]);
        $mform->setDefault('enable', $source->enable);
        if ($source->hasassignments) {
            $mform->hardFreeze('enable');
        }

        $mform->addElement(
            'select',
            'selfassignment_allowsignup',
            get_string('source_selfassignment_allowsignup', 'tool_mucertify'),
            ['1' => get_string('yes'), '0' => get_string('no')]
        );
        $mform->setDefault('selfassignment_allowsignup', 1);
        $mform->hideIf('selfassignment_allowsignup', 'enable', 'eq', '0');

        $mform->addElement('passwordunmask', 'selfassignment_key', get_string('source_selfassignment_key', 'tool_mucertify'));
        $mform->setDefault('selfassignment_key', $source->selfassignment_key);
        $mform->hideIf('selfassignment_key', 'enable', 'eq', '0');

        $mform->addElement('text', 'selfassignment_maxusers', get_string('source_selfassignment_maxusers', 'tool_mucertify'), 'size="8"');
        $mform->setType('selfassignment_maxusers', PARAM_RAW);
        $mform->setDefault('selfassignment_maxusers', $source->selfassignment_maxusers);
        $mform->hideIf('selfassignment_maxusers', 'enable', 'eq', '0');

        $mform->addElement('hidden', 'certificationid');
        $mform->setType('certificationid', PARAM_INT);
        $mform->setDefault('certificationid', $certification->id);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUMEXT);
        $mform->setDefault('type', $source->type);

        $this->add_action_buttons(true, get_string('update'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['selfassignment_maxusers'] !== '') {
            if (!is_number($data['selfassignment_maxusers'])) {
                $errors['selfassignment_maxusers'] = get_string('error');
            } else if ($data['selfassignment_maxusers'] < 0) {
                $errors['selfassignment_maxusers'] = get_string('error');
            }
        }

        return $errors;
    }
}
