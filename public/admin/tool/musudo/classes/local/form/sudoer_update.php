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

namespace tool_musudo\local\form;

use tool_musudo\local\sudoer;
use tool_musudo\local\mfa;

/**
 * Update sudo user.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $sudoer = $this->_customdata['sudoer'];
        $user = $this->_customdata['user'];

        if ($user) {
            $username = fullname($user);
        } else {
            $username = get_string('error');
        }
        $mform->addElement('static', 'username', get_string('user'), $username);

        $currentdata = [
            'id' => $sudoer->id,
            'note' => $sudoer->note,
            'mfarequired' => $sudoer->mfarequired,
            'contextid' => [],
            'roleid' => [],
        ];
        $privileges = json_decode($sudoer->privilegesjson);
        $privileges = array_values($privileges);
        foreach ($privileges as $i => $privilege) {
            $currentdata['contextid'][] = $privilege->contextid;
            $currentdata['roleid'][] = $privilege->roleid;
        }

        $mform->addElement('textarea', 'note', get_string('sudoer_note', 'tool_musudo'), ['rows' => 3]);
        $mform->setType('note', PARAM_TEXT);

        if (mfa::is_mfa_enabled()) {
            $mform->addElement('advcheckbox', 'mfarequired', get_string('mfarequired', 'tool_musudo'));
        }

        // Dealing with deleted roles would be a hassle here...
        $roles = ['' => get_string('choosedots')] + sudoer::get_role_options();

        $repeat = [];
        $repeatopts = [];

        $repeat[] = $mform->createElement('header', 'roleheader', get_string('privilege_heading', 'tool_musudo'));

        $repeat[] = $mform->createElement('select', 'roleid', get_string('role'), $roles);

        $repeat[] = $mform->createElement('text', 'contextid', get_string('contextid', 'tool_musudo'), ['size' => 5]);
        $repeatopts['contextid']['type'] = PARAM_INT;

        $repeat[] = $mform->createElement('submit', 'privilege_delete', get_string('privilege_delete', 'tool_musudo'), [], false);

        $this->repeat_elements(
            $repeat,
            count($privileges),
            $repeatopts,
            'privilege_repeat',
            'privilege_more',
            1,
            get_string('privilege_more', 'tool_musudo'),
            false,
            'privilege_delete'
        );

        // NOTE: repeat options are not working much when stuff gets deleted, just hack around it for now.
        $repeatcount = $this->optional_param('privilege_repeat', count($privileges), PARAM_INT);
        for ($i = 0; $i < $repeatcount + 1; $i++) {
            if ($mform->elementExists("contextid[$i]")) {
                $mform->addRule("contextid[$i]", get_string('required'), 'required', null, 'server');
                $mform->addRule("roleid[$i]", get_string('required'), 'required', null, 'server');
            }
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('sudoer_update', 'tool_musudo'));

        $this->set_data($currentdata);
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $contextids = [];
        if (empty($data['contextid'])) {
            $errors['privilege_more'] = get_string('required');
        } else {
            foreach ($data['contextid'] as $i => $contextid) {
                if ($contextid) {
                    $context = \context::instance_by_id($contextid, IGNORE_MISSING);
                    if ($context) {
                        if (isset($contextids[$context->id])) {
                            $errors["contextid[$i]"] = get_string('error');
                        } else {
                            $contextids[$context->id] = true;
                        }
                    } else {
                        $errors["contextid[$i]"] = get_string('error');
                    }
                } else {
                    $errors["contextid[$i]"] = get_string('required');
                }

                if (empty($data['roleid'][$i])) {
                    $errors["roleid[$i]"] = get_string('required');
                } else {
                    $role = $DB->get_record('role', ['id' => $data['roleid'][$i]]);
                    if (!$role) {
                        $errors["roleid[$i]"] = get_string('error');
                    }
                }
            }
        }

        return $errors;
    }
}
