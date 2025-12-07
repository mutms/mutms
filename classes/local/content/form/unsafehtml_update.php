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

namespace mod_mubook\local\content\form;

use stdClass;
use mod_mubook\local\toc;
use mod_mubook\local\chapter;
use mod_mubook\local\content;

/**
 * Update unsafe raw HTML content.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class unsafehtml_update extends \mod_mubook\local\form\content_update_base {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var content $content */
        $content = $this->_customdata['content'];
        /** @var chapter $chapter */
        $chapter = $this->_customdata['chapter'];
        /** @var toc $toc */
        $toc = $this->_customdata['toc'];
        $mubook = $toc->get_mubook();
        $context = $toc->get_context();

        $mform->addElement('textarea', 'text', get_string('content_type_unsafehtml', 'mod_mubook'), ['cols' => 50, 'rows' => 20]);
        $mform->setDefault('text', $content->data1);

        $mform->addElement('filemanager', 'files', get_string('content_files', 'mod_mubook'), null, self::get_content_files_options());
        $draftitemid = file_get_submitted_draft_itemid('files');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_mubook', 'content', $content->id, self::get_content_files_options());
        $mform->setDefault('files', $draftitemid);

        $mform->addElement(
            'advcheckbox',
            'unsafetrusted',
            get_string('content_unsafetrusted', 'mod_mubook'),
            get_string('content_unsafetrusted_confirmation', 'mod_mubook')
        );
        $mform->setDefault('trusted', $content->unsafetrusted);

        $this->add_shared_content_elements();

        $this->add_action_buttons(true, get_string('content_update', 'mod_mubook'));
    }

    /**
     * Returns file manager options.
     *
     * @return array
     */
    public static function get_content_files_options(): array {
        global $CFG;

        return [
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => 100,
            'subdirs' => 1,
            'accepted_types' => ['*'],
        ];
    }

    #[\Override]
    public static function before_db_update(stdClass $record, stdClass $data, chapter $chapter, stdClass $mubook, \context_module $context): void {
        if (property_exists($data, 'text')) {
            $record->data1 = $data->text;
        }
        if (property_exists($data, 'unsafetrusted')) {
            $record->unsafetrusted = (int)(bool)$data->unsafetrusted;
        }
    }

    #[\Override]
    public static function after_db_update(stdClass $record, stdClass $data, chapter $chapter, stdClass $mubook, \context_module $context): void {
        if (isset($data->files)) {
            if (is_number($data->files) && $data->files) {
                file_save_draft_area_files($data->files, $context->id, 'mod_mubook', 'content', $record->id, self::get_content_files_options());
            }
        }
    }
}
