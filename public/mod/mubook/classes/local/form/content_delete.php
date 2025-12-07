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
 * Delete chapter content instance.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class content_delete extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        /** @var \mod_mubook\local\content $content */
        $content = $this->_customdata['content'];
        /** @var \mod_mubook\local\chapter $chapter */
        $chapter = $this->_customdata['chapter'];
        /** @var \mod_mubook\local\toc $toc */
        $toc = $this->_customdata['toc'];
        $mubook = $toc->get_mubook();
        $context = $toc->get_context();

        if ($chapter->parentid) {
            $mform->addElement('static', 'statictitle', get_string('subchapter_title', 'mod_mubook'), $toc->get_numbered_chapter_title($chapter->id));
        } else {
            $mform->addElement('static', 'statictitle', get_string('chapter_title', 'mod_mubook'), $toc->get_numbered_chapter_title($chapter->id));
        }

        $mform->addElement('static', 'staticidentif', $content->get_identification());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $content->id);

        $this->add_action_buttons(true, get_string('content_delete', 'mod_mubook'));
    }
}
