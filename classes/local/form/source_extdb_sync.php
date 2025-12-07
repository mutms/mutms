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

/**
 * Edit external database manual sync form
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_extdb_sync extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $source = $this->_customdata['source'];
        $program = $this->_customdata['program'];
        $query = $this->_customdata['query'];

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        $mform->addElement('static', 'program', get_string('program', 'tool_muprog'), format_string($program->fullname));

        if (!$query) {
            $qname = get_string('error');
        } else {
            $qname = s($query->name);
        }
        $mform->addElement('static', 'query', get_string('extdb_query', 'tool_mulib'), $qname);

        if ($source->auxint4 || $source->auxint3) {
            $mform->addElement('static', 'pendingsync', get_string('source_extdb_pendingsync', 'tool_muprog'), get_string('yes'));
        }

        if ($source->auxint5) {
            $last = userdate($source->auxint5);
        } else {
            $last = get_string('none');
        }
        $mform->addElement('static', 'lastsync', get_string('source_extdb_lastsync', 'tool_muprog'), $last);

        $this->add_action_buttons(true, get_string('source_extdb_sync', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $program = $this->_customdata['program'];
        $source = $this->_customdata['source'];
        $query = $this->_customdata['query'];

        if ($program->archived) {
            $errors['program'] = get_string('error');
        } else if (!$query) {
            $errors['query'] = get_string('error');
        }

        if ($source->auxint4 && $source->auxint4 + HOURSECS < time()) {
            // Task is still running right now, wait at least an hour before adding a new task.
            $errors['pendingsync'] = get_string('error');
        }

        return $errors;
    }
}
