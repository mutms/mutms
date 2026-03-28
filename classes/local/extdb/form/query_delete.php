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

namespace tool_mulib\local\extdb\form;

use tool_mulib\external\form_autocomplete\extdb_query_contextid;

/**
 * Delete query form.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class query_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $query = $this->_customdata['query'];

        $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);
        $classname = $qman->get_class($query->component, $query->type);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('static', 'querycomponent', get_string('plugin'), get_string('pluginname', $query->component));

        if ($classname) {
            $typename = $classname::get_name();
        } else {
            $typename = get_string('error');
        }
        $mform->addElement('static', 'querytype', get_string('extdb_query_type', 'tool_mulib'), $typename);

        $mform->addElement('static', 'staticname', get_string('name'), s($query->name));

        $this->add_action_buttons(true, get_string('extdb_query_delete', 'tool_mulib'));
        $this->set_data($query);
    }
}
