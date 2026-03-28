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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Upload programs files.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upload_files extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $contextid = $this->_customdata['contextid'];

        $options = [
            'maxfiles' => -1,
            'subdirs' => 0,
            'accepted_types' => ['.json', '.zip', '.txt', '.csv'],
            'return_types' => FILE_INTERNAL,
        ];
        $mform->addElement('filemanager', 'files', get_string('upload_files', 'tool_muprog'), null, $options);
        $mform->addRule('files', null, 'required');

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);
        $mform->setDefault('contextid', $contextid);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // File validation is bad in mforms, so work around it here.
        if (empty($data['files'])) {
            $errors['files'] = get_string('error');
            return $errors;
        }

        $error = \tool_muprog\local\upload::store_filedata($data['files'], $data['encoding']);
        if ($error !== null) {
            $errors['files'] = $error;
        }

        return $errors;
    }
}
