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
 * Allocate users via file upload.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_upload_file extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $source = $this->_customdata['source'];
        $context = $this->_customdata['context'];

        $mform->addElement('filepicker', 'csvfile', get_string('source_manual_csvfile', 'tool_muprog'));
        $mform->addRule('csvfile', null, 'required');

        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') === ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        // File validation is bad in mforms, so work around it here.
        if (empty($data['csvfile'])) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }
        $draftid = $data['csvfile'];
        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false);
        if (!$files) {
            $errors['csvfile'] = get_string('required');
            return $errors;
        }
        $file = reset($files);
        $content = $file->get_content();
        $content = trim($content);
        if (!$content) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }

        $iid = \csv_import_reader::get_new_iid('programuploadusers');
        $cir = new \csv_import_reader($iid, 'programuploadusers');

        $readcount = $cir->load_csv_content($content, $data['encoding'], $data['delimiter_name']);
        $columns = $cir->get_columns();
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            $errors['csvfile'] = $csvloaderror;
            return $errors;
        } else if (!$readcount || !$columns) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }

        if ($errors) {
            return $errors;
        }

        $cir = new \csv_import_reader($iid, 'programuploadusers');
        $cir->init();
        $filedata = [];
        $filedata[] = array_map('trim', $columns);
        while ($line = $cir->next()) {
            $filedata[] = array_map('trim', $line);
        }
        $cir->close();

        if (!$filedata) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }

        $cir->cleanup(true);

        \tool_muprog\local\util::store_uploaded_data($data['csvfile'], $filedata);

        return $errors;
    }
}
