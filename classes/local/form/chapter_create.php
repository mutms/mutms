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
 * Create a new chapter.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_create extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var \mod_mubook\local\toc $toc */
        $toc = $this->_customdata['toc'];
        $subchapter = $this->_customdata['subchapter'];
        $position = $this->_customdata['position'];
        $fromcreatechapterid = $this->_customdata['fromcreatechapterid'];
        $mubook = $toc->get_mubook();
        $context = $toc->get_context();

        $mform->addElement('hidden', 'subchapter');
        $mform->setType('subchapter', PARAM_BOOL);
        $mform->setDefault('subchapter', $subchapter);

        $mform->addElement('hidden', 'fromcreatechapterid');
        $mform->setType('fromcreatechapterid', PARAM_INT);
        $mform->setDefault('fromcreatechapterid', $fromcreatechapterid);

        $topchapters = [];
        $subchapters = [];
        foreach ($toc->get_chapters() as $ch) {
            if ($ch->parentid) {
                $subchapters[$ch->parentid][$ch->id] = $ch;
            } else {
                $topchapters[$ch->id] = $ch;
            }
        }

        if ($topchapters) {
            // Always use chapter numbers here.
            if ($subchapter) {
                $afteroptions = [];
                $positions = [];
                if ($fromcreatechapterid > 0) {
                    $from = $toc->get_chapter($fromcreatechapterid);
                    if ($from && isset($topchapters[$from->id])) {
                        // Restrict to creation in one parent chapter only,
                        // this must be the viewchapter page.
                        $topchapters = [$from->id => $topchapters[$from->id]];
                    }
                }
                foreach ($topchapters as $chapter) {
                    $optgroup = $toc->get_numbered_chapter_title($chapter->id);
                    $option = get_string('subchapter_position_first', 'mod_mubook', $optgroup);
                    $afteroptions[$optgroup][$chapter->id] = $option;
                    $positions[] = $chapter->id;
                    if (isset($subchapters[$chapter->id])) {
                        foreach ($subchapters[$chapter->id] as $subchapter) {
                            $option = $toc->get_numbered_chapter_title($subchapter->id);
                            $option = get_string('subchapter_position_after', 'mod_mubook', $option);
                            $afteroptions[$optgroup][$subchapter->id] = $option;
                            $positions[] = $subchapter->id;
                        }
                    }
                }

                $mform->addElement('selectgroups', 'position', get_string('subchapter_position', 'mod_mubook'), $afteroptions);
                if (in_array($position, $positions)) {
                    $mform->setDefault('position', $position);
                } else if (in_array($toc->get_last_chapter()->id, $positions)) {
                    $mform->setDefault('position', $toc->get_last_chapter()->id);
                } else if ($fromcreatechapterid > 0 && in_array($fromcreatechapterid, $positions)) {
                    $mform->setDefault('position', $fromcreatechapterid);
                }
            } else {
                $afteroptions = [
                    0 => get_string('chapter_position_first', 'mod_mubook'),
                ];
                foreach ($topchapters as $chapter) {
                    $option = $toc->get_numbered_chapter_title($chapter->id);
                    $afteroptions[$chapter->id] = get_string('chapter_position_after', 'mod_mubook', $option);
                }
                $mform->addElement('select', 'position', get_string('chapter_position', 'mod_mubook'), $afteroptions);
                if (isset($afteroptions[$position])) {
                    $mform->setDefault('position', $position);
                } else {
                    $mform->setDefault('position', array_key_last($afteroptions));
                }
            }
        } else {
            $mform->addElement('hidden', 'position');
            $mform->setType('position', PARAM_INT);
            $mform->setDefault('position', 0);
        }

        if ($subchapter) {
            $mform->addElement('text', 'title', get_string('subchapter_title', 'mod_mubook'), 'maxlength="1333" size="50"');
        } else {
            $mform->addElement('text', 'title', get_string('chapter_title', 'mod_mubook'), 'maxlength="1333" size="50"');
        }
        $mform->addRule('title', get_string('required'), 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);

        if (\core_tag_tag::is_enabled('mod_mubook', 'mubook_chapter')) {
            $mform->addElement(
                'tags',
                'tags',
                get_string('tags'),
                ['component' => 'mod_mubook', 'itemtype' => 'mubook_chapter']
            );
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
        $options[''] = get_string('none');
        $mform->addElement('select', 'contentcreate', get_string('content_create', 'mod_mubook'), $options);
        if (isset($options[$mubook->contentdefault])) {
            $mform->setDefault('contentcreate', $mubook->contentdefault);
        }

        $mform->addElement('hidden', 'mubookid');
        $mform->setType('mubookid', PARAM_INT);
        $mform->setDefault('mubookid', $mubook->id);

        if ($subchapter) {
            $this->add_action_buttons(true, get_string('subchapter_create', 'mod_mubook'));
        } else {
            $this->add_action_buttons(true, get_string('chapter_create', 'mod_mubook'));
        }
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
