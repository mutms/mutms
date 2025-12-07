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
// phpcs:disable moodle.Commenting.InlineComment.DocBlock

namespace mod_mubook\local\form;

/**
 * Content type selection for creation.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class content_create_select extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var \mod_mubook\local\chapter $chapter */
        $chapter = $this->_customdata['chapter'];
        $sortorder = $this->_customdata['sortorder'];
        /** @var \mod_mubook\local\toc $toc */
        $toc = $this->_customdata['toc'];
        $mubook = $toc->get_mubook();
        $context = $toc->get_context();

        if ($chapter->parentid) {
            $mform->addElement('static', 'statictitle', get_string('subchapter_title', 'mod_mubook'), $toc->get_numbered_chapter_title($chapter->id));
        } else {
            $mform->addElement('static', 'statictitle', get_string('chapter_title', 'mod_mubook'), $toc->get_numbered_chapter_title($chapter->id));
        }

        $options = [];
        $cman = \core\di::get(\mod_mubook\local\content_manager::class);
        /**
         * @var string $type
         * @var class-string<\mod_mubook\local\content> $classname
         */
        foreach ($cman->get_available_classes() as $type => $classname) {
            if ($classname::can_create(null, $mubook, $context)) {
                $options[$type] = $classname::get_name();
            }
        }
        \core_collator::asort($options);
        $mform->addElement('select', 'type', get_string('content_create', 'mod_mubook'), $options);
        $mform->addRule('type', get_string('required'), 'required', null, 'client');
        if (isset($options[$mubook->contentdefault])) {
            $mform->setDefault('type', $mubook->contentdefault);
        }

        $mform->addElement('hidden', 'chapterid');
        $mform->setType('chapterid', PARAM_INT);
        $mform->setDefault('chapterid', $chapter->id);

        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', $sortorder);

        $this->add_action_buttons(true, get_string('continue'));
    }
}
