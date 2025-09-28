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

use tool_muprog\external\form_autocomplete\export_programids;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');

/**
 * Export programs.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class export extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $contextid = $this->_customdata['contextid'];
        $context = $this->_customdata['context'];
        $archived = $this->_customdata['archived'];

        if ($program) {
            // Export was started from a program details page, let them add other programs.
            export_programids::add_element(
                $mform,
                ['query' => ''],
                'programids',
                get_string('programs', 'tool_muprog'),
                $context
            );
            $mform->addRule('programids', null, 'required', null, 'client');
            $mform->setDefault('programids', [$program->id]);

            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
            $mform->setDefault('id', $program->id);
        } else {
            // Export started from category, let them select other categories or all programs.
            $mform->addElement('select', 'contextid', get_string('context'), self::get_contextid_options());
            $mform->setDefault('contextid', $contextid);

            $mform->addElement('advcheckbox', 'archived', get_string('archived', 'tool_muprog'), '&nbsp;');
            $mform->setDefault('archived', $archived);
        }

        $choices = [
            'json' => get_string('exportformat_json', 'tool_muprog'),
            'csv' => get_string('exportformat_csv', 'tool_muprog'),
        ];
        $mform->addElement('select', 'format', get_string('exportformat', 'tool_muprog'), $choices);

        $choices = \csv_import_reader::get_delimiter_list();
        unset($choices['colon']); // This collides with formatted dates, better not use it at all.
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') === ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }
        $mform->hideIf('delimiter_name', 'format', 'noteq', 'csv');

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
        $mform->hideIf('encoding', 'format', 'noteq', 'csv');

        // We cannot redirect after file is downloaded, so let them click "Back" button instead.
        $buttonarray = [
            $mform->createElement('submit', 'exportbutton', get_string('export', 'tool_muprog')),
            $mform->createElement('cancel', 'cancel', get_string('back')),
        ];
        $grp = $mform->addGroup($buttonarray, 'buttonar', get_string('formactions', 'core_form'), [' '], false);
        $grp->setHiddenLabel(true);
        $mform->closeHeaderBefore('buttonar');
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];
        $program = $this->_customdata['program'];

        if ($program) {
            if (!$data['programids']) {
                $errors['programids'] = get_string('required');
            } else {
                foreach ($data['programids'] as $programid) {
                    $error = export_programids::validate_value($programid, [], $context);
                    if ($error !== null) {
                        $errors['programids'] = $error;
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Returns contexts of categories that user can export.
     *
     * @return array
     */
    public function get_contextid_options(): array {
        $options = [];
        $syscontext = \context_system::instance();
        if (has_capability('tool/muprog:export', $syscontext)) {
            $options[0] = get_string('allprograms', 'tool_muprog');
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        $categories = \core_course_category::make_categories_list('tool/muprog:export');
        foreach ($categories as $catid => $categoryname) {
            $catcontext = \context_coursecat::instance($catid);
            $options[$catcontext->id] = $categoryname;
        }
        return $options;
    }
}
