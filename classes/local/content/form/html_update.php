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
 * Update HTML content.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class html_update extends \mod_mubook\local\form\content_update_base {
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

        $options = self::get_content_editor_options($context);
        $data = (object)[
            'id' => $content->id,
            'text' => $content->data1,
            'textformat' => FORMAT_HTML,
        ];

        $mform->addElement('editor', 'text_editor', get_string('content_text', 'mod_mubook'), ['rows' => 20], $options);
        file_prepare_standard_editor($data, 'text', $options, $context, 'mod_mubook', 'content', $data->id);
        $mform->setDefault('text_editor', $data->text_editor);

        $this->add_shared_content_elements();

        $this->add_action_buttons(true, get_string('content_update', 'mod_mubook'));
    }

    /**
     * Returns editor options.
     *
     * @param \context_module $context
     * @return array
     */
    public static function get_content_editor_options(\context_module $context): array {
        global $CFG;

        return [
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => 100,
            'subdirs' => 1,
            'accepted_types' => ['*'],
            'context' => $context,
        ];
    }

    #[\Override]
    public static function before_db_update(stdClass $record, stdClass $data, chapter $chapter, stdClass $mubook, \context_module $context): void {
        if (property_exists($data, 'text_editor')) {
            $record->data1 = $data->text_editor['text'];
        } else if (property_exists($data, 'text')) {
            $record->data1 = $data->text;
        }
    }

    #[\Override]
    public static function after_db_update(stdClass $record, stdClass $data, chapter $chapter, stdClass $mubook, \context_module $context): void {
        global $DB;

        if (isset($data->text_editor['itemid'])) {
            $data->text_editor['format'] = FORMAT_HTML;
            $options = self::get_content_editor_options($context);
            $data = file_postupdate_standard_editor(
                $data,
                'text',
                $options,
                $options['context'],
                'mod_mubook',
                'content',
                $record->id
            );
            if ($data->text !== $record->data1) {
                $DB->set_field('mubook_content', 'data1', $data->text, ['id' => $record->id]);
            }
        }
    }
}
