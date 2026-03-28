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
 * Delete a chapter.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var \mod_mubook\local\chapter $chapter */
        $chapter = $this->_customdata['chapter'];
        /** @var \mod_mubook\local\toc $toc */
        $toc = $this->_customdata['toc'];

        if ($chapter->parentid) {
            $mform->addElement('static', 'statictitle', get_string('subchapter_title', 'mod_mubook'), $toc->get_numbered_chapter_title($chapter->id));
        } else {
            $mform->addElement('static', 'statictitle', get_string('chapter_title', 'mod_mubook'), $toc->get_numbered_chapter_title($chapter->id));
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $chapter->id);

        $count = self::count_subchapters($chapter);
        if ($count) {
            $subchapters = [];
            foreach ($toc->get_chapters() as $ch) {
                if ($ch->parentid == $chapter->id) {
                    $subchapters[] = $toc->get_numbered_chapter_title($ch->id);
                }
            }
            $mform->addElement('static', 'subchapters', get_string('subchapters', 'mod_mubook'), implode('<br/>', $subchapters));
            $mform->addElement('advcheckbox', 'deletesubchapters', get_string('subchapters_delete_a', 'mod_mubook', $count));
        }

        if ($chapter->parentid) {
            $this->add_action_buttons(true, get_string('subchapter_delete', 'mod_mubook'));
        } else {
            $this->add_action_buttons(true, get_string('chapter_delete', 'mod_mubook'));
        }
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /** @var \mod_mubook\local\chapter $chapter */
        $chapter = $this->_customdata['chapter'];

        if (self::count_subchapters($chapter)) {
            if (!$data['deletesubchapters']) {
                $errors['deletesubchapters'] = get_string('required');
            }
        }

        return $errors;
    }

    /**
     * Does the chapter have any subchapters?
     *
     * @param \mod_mubook\local\chapter $chapter
     * @return int
     */
    protected static function count_subchapters(\mod_mubook\local\chapter $chapter): int {
        global $DB;
        if ($chapter->parentid) {
            return 0;
        }
        return $DB->count_records('mubook_chapter', ['parentid' => $chapter->id]);
    }
}
