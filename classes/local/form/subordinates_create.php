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

namespace tool_murelation\local\form;

use tool_murelation\external\form_autocomplete\subordinates_create_subuserids;

/**
 * Create team.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class subordinates_create extends \tool_mulib\local\ajax_form {
    /** @var array */
    protected $wsarguments;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $framework = $this->_customdata['framework'];
        $tenantid = $this->_customdata['tenantid'];
        $context = $this->_customdata['context'];
        $supuser = $this->_customdata['supuser'];
        $this->wsarguments = ['frameworkid' => $framework->id, 'tenantid' => $tenantid, 'supuserid' => $supuser->id];

        $supervisortitle = format_string($framework->supervisortitle);
        $subordinatestitle = format_string($framework->subordinatestitle);

        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->setDefault('frameworkid', $framework->id);

        $mform->addElement('hidden', 'supuserid');
        $mform->setType('supuserid', PARAM_INT);
        $mform->setDefault('supuserid', $supuser->id);

        $mform->addElement('static', 'fwname', get_string('framework_name', 'tool_murelation'), format_string($framework->name));
        if ($framework->idnumber !== null) {
            $mform->addElement('static', 'fwidnumber', get_string('framework_idnumber', 'tool_murelation'), s($framework->idnumber));
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active() && $tenantid) {
            $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
            $mform->addElement('static', 'tenant', get_string('tenant', 'tool_mutenancy'), format_string($tenant->name));
        }

        $mform->addElement('static', 'supuser', $supervisortitle, fullname($supuser));

        subordinates_create_subuserids::add_element($mform, $this->wsarguments, 'subuserids', $subordinatestitle, $context);
        $mform->addRule('subuserids', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('subordinates_create_a', 'tool_murelation', $subordinatestitle));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        if ($data['subuserids']) {
            foreach ($data['subuserids'] as $userid) {
                $error = subordinates_create_subuserids::validate_value($userid, $this->wsarguments, $context);
                if ($error !== null) {
                    $errors['subuserids'] = $error;
                    break;
                }
            }
        } else {
            $errors['subuserids'] = get_string('required');
        }

        return $errors;
    }
}
