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

use tool_murelation\external\form_autocomplete\position_edit_supuserid;

/**
 * Delete supervisor for given subordinate user.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class supervisor_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $subuser = $this->_customdata['subuser'];
        $supuser = $this->_customdata['supuser'];
        $framework = $this->_customdata['framework'];

        $supervisortitle = format_string($framework->supervisortitle);
        $subordinatetitle = format_string($framework->subordinatetitle);

        $mform->addElement('hidden', 'subuserid');
        $mform->setType('subuserid', PARAM_INT);
        $mform->setDefault('subuserid', $subuser->id);

        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->setDefault('frameworkid', $framework->id);

        $mform->addElement('static', 'fwname', get_string('framework_name', 'tool_murelation'), format_string($framework->name));
        if ($framework->idnumber !== null) {
            $mform->addElement('static', 'fwidnumber', get_string('framework_idnumber', 'tool_murelation'), s($framework->idnumber));
        }

        $mform->addElement('static', 'subuser', $subordinatetitle, fullname($subuser));

        if ($supuser) {
            $mform->addElement('static', 'supuser', $supervisortitle, fullname($supuser));
        }

        $this->add_action_buttons(true, get_string('supervisor_delete_a', 'tool_murelation', $supervisortitle));
    }
}
