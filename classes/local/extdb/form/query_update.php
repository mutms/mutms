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
 * Update query form.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class query_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        global $DB;
        $mform = $this->_form;
        $query = $this->_customdata['query'];
        $syscontext = \context_system::instance();

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

        extdb_query_contextid::add_element(
            $mform,
            [],
            'contextid',
            get_string('category'),
            $syscontext
        );

        $servers = $DB->get_records_menu('tool_mulib_extdb_server', [], 'name ASC', 'id, name');
        if (!isset($servers[$query->serverid])) {
            $servers[$query->serverid] = get_string('error');
        }
        $mform->addElement('select', 'serverid', get_string('extdb_server', 'tool_mulib'), $servers);

        $mform->addElement('text', 'name', get_string('name'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('textarea', 'sqlquery', get_string('extdb_query_sqlquery', 'tool_mulib'), ['rows' => '5', 'cols' => '50']);
        $mform->setType('sqlquery', PARAM_RAW);
        $mform->addRule('sqlquery', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'showstatus');
        $mform->setType('showstatus', PARAM_INT);

        $mform->addElement('static', 'statusstatic', get_string('extdb_query_status', 'tool_mulib'), '');
        $mform->hideIf('statusstatic', 'showstatus', 'eq', '0');

        $help = $classname::get_query_help();
        $help = format_text($help, FORMAT_MARKDOWN);
        $mform->addElement('static', 'sqlhelp', '', $help);

        $mform->addElement('textarea', 'note', get_string('note', 'core_notes'), ['rows' => '2', 'cols' => '50']);
        $mform->setType('note', PARAM_RAW);

        $buttonarray = [
            $mform->createElement('submit', 'submitbutton_' . $this::$uniqueid, get_string('extdb_query_update', 'tool_mulib')),
            $mform->createElement('submit', 'check', get_string('extdb_query_check', 'tool_mulib'), [], false),
            $mform->createElement('cancel'),
        ];
        $mform->addGroup($buttonarray, 'buttonarray', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');

        $this->set_data($query);
    }

    #[\Override]
    public function definition_after_data() {
        global $DB;
        $mform = $this->_form;
        $query = $this->_customdata['query'];

        $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);
        $classname = $qman->get_class($query->component, $query->type);

        parent::definition_after_data();
        $status = '';
        $data = $this->get_submitted_data();
        if (!$classname) {
            $status = '<div class="alert alert-danger">' . get_string('error') . '</div>';
        } else if (!empty($data->check)) {
            $error = null;
            try {
                $server = $DB->get_record('tool_mulib_extdb_server', ['id' => $data->serverid], '*', MUST_EXIST);
                $pdb = new \tool_mulib\local\extdb\pdb($server);
                $pdb->connect();
                $pdb->query($data->sql, $classname::get_check_parameters());
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
        $syscontext = \context_system::instance();

        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        } else if ($DB->record_exists_select('tool_mulib_extdb_query', "LOWER(name) = LOWER(?) AND id <> ?", [trim($data['name']), $data['id']])) {
            $errors['name'] = get_string('error');
        }

        $error = extdb_query_contextid::validate_value($data['contextid'], [], $syscontext);
        if ($error !== null) {
            $errors['contextid'] = $error;
        }

        if (!$DB->record_exists('tool_mulib_extdb_server', ['id' => $data['serverid']])) {
            $errors['serverid'] = get_string('error');
        }

        return $errors;
    }
}
