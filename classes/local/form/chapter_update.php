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

namespace mod_mubook\local\form;

/**
 * Update a chapter.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_update extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $chapter = $this->_customdata['chapter'];

        if ($chapter->parentid) {
            $mform->addElement('text', 'title', get_string('subchapter_title', 'mod_mubook'), 'maxlength="1333" size="50"');
        } else {
            $mform->addElement('text', 'title', get_string('chapter_title', 'mod_mubook'), 'maxlength="1333" size="50"');
        }
        $mform->addRule('title', get_string('required'), 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);
        $mform->setDefault('title', $chapter->title);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $chapter->id);

        if (\core_tag_tag::is_enabled('mod_mubook', 'mubook_chapter')) {
            $mform->addElement(
                'tags',
                'tags',
                get_string('tags'),
                ['component' => 'mod_mubook', 'itemtype' => 'mubook_chapter']
            );
            $tags = \core_tag_tag::get_item_tags_array('mod_mubook', 'mubook_chapter', $chapter->id);
            if ($tags) {
                $mform->setDefault('tags', $tags);
            }
        }

        if ($chapter->parentid) {
            $this->add_action_buttons(true, get_string('subchapter_update', 'mod_mubook'));
        } else {
            $this->add_action_buttons(true, get_string('chapter_update', 'mod_mubook'));
        }
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
