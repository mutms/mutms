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

/**
 * Create a new server form.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class server_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'dsn', get_string('extdb_server_dsn', 'tool_mulib'), ['size' => 40, 'maxlength' => 1333]);
        $mform->setType('dsn', PARAM_RAW);
        $mform->addHelpButton('dsn', 'extdb_server_dsn', 'tool_mulib');
        $mform->addRule('dsn', get_string('required'), 'required', null, 'client');

        $extensions = [];
        if (extension_loaded('pdo')) {
            foreach (get_loaded_extensions() as $extension) {
                if (str_starts_with($extension, 'pdo_')) {
                    $extensions[] = $extension;
                }
            }
        }
        if ($extensions) {
            $extensions = implode(', ', $extensions);
        } else {
            $extensions = get_string('none');
        }
        $mform->addElement('static', 'extensions', '', '<em>' . get_string('extdb_server_extensions', 'tool_mulib', $extensions) . '</em>');

        $mform->addElement('text', 'dbuser', get_string('extdb_server_dbuser', 'tool_mulib'), ['size' => 20, 'maxlength' => 100]);
        $mform->setType('dbuser', PARAM_RAW);

        $mform->addElement('text', 'dbpass', get_string('extdb_server_dbpass', 'tool_mulib'), ['size' => 20, 'maxlength' => 100]);
        $mform->setType('dbpass', PARAM_RAW);

        $mform->addElement('textarea', 'dboptions', get_string('extdb_server_dboptions', 'tool_mulib'), ['rows' => '3', 'cols' => '50']);
        $mform->addHelpButton('dboptions', 'extdb_server_dboptions', 'tool_mulib');
        $mform->setType('dboptions', PARAM_RAW);

        $mform->addElement('hidden', 'showstatus');
        $mform->setType('showstatus', PARAM_INT);

        $mform->addElement('static', 'statusstatic', get_string('extdb_server_status', 'tool_mulib'), '');
        $mform->hideIf('statusstatic', 'showstatus', 'eq', '0');

        $mform->addElement('textarea', 'note', get_string('note', 'core_notes'), ['rows' => '2', 'cols' => '50']);
        $mform->setType('note', PARAM_RAW);

        $buttonarray = [
            $mform->createElement('submit', 'submitbutton_' . $this::$uniqueid, get_string('extdb_server_create', 'tool_mulib')),
            $mform->createElement('submit', 'check', get_string('extdb_server_check', 'tool_mulib'), [], false),
            $mform->createElement('cancel'),
        ];
        $mform->addGroup($buttonarray, 'buttonarray', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }

    #[\Override]
    public function definition_after_data() {
        parent::definition_after_data();
        $mform = $this->_form;

        $status = '';
        $data = $this->get_submitted_data();
        if (!empty($data->check)) {
            $error = null;
            try {
                $server = (object)[
                    'dsn' => $data->dsn,
                    'dbuser' => $data->dbuser,
                    'dbpass' => $data->dbpass,
                    'dboptions' => $data->dboptions,
                ];
                $pdb = new \tool_mulib\local\extdb\pdb($server);
                $pdb->connect();
            } catch (\Throwable $ex) {
                $error = $ex->getMessage();
            }
            if ($error === null) {
                $status = '<span class="alert alert-success">' . get_string('ok') . '</span>';
            } else {
                $status = '<div class="alert alert-danger">' . s($error) . '</div>';
            }
        }
        /** @var \MoodleQuickForm_static $element */
        $element = $mform->getElement('statusstatic');
        $element->setText($status);
        /** @var \MoodleQuickForm_hidden $showelement */
        $showelement = $mform->getElement('showstatus');
        if ($status === '') {
            $showelement->setValue(0);
        } else {
            $showelement->setValue(1);
        }
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        } else if ($DB->record_exists_select('tool_mulib_extdb_server', "LOWER(name) = LOWER(?)", [trim($data['name'])])) {
            $errors['name'] = get_string('error');
        }

        if ($data['dboptions']) {
            try {
                $options = json_decode($data['dboptions'], flags:JSON_THROW_ON_ERROR);
                if (!is_array($options) && !is_object($options)) {
                    $errors['dboptions'] = get_string('error');
                }
            } catch (\Throwable $ex) {
                $errors['dboptions'] = get_string('error');
            }
        }

        return $errors;
    }
}
