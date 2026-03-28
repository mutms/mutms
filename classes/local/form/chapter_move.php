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
 * Move a chapter.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_move extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var \mod_mubook\local\toc $toc */
        $toc = $this->_customdata['toc'];
        $chapter = $this->_customdata['chapter'];

        $firstchapter = $toc->get_first_chapter();
        $topchapters = [];
        $subchapters = [];
        foreach ($toc->get_chapters() as $ch) {
            if ($ch->parentid) {
                $subchapters[$ch->parentid][$ch->id] = $ch;
            } else {
                $topchapters[$ch->id] = $ch;
            }
        }

        $mform->addElement('static', 'statictitle', get_string('chapter_title', 'mod_mubook'), $toc->get_numbered_chapter_title($chapter->id));

        if ($chapter->parentid || !isset($subchapters[$chapter->id])) {
            $mform->addElement('advcheckbox', 'subchapter', get_string('subchapter', 'mod_mubook'));
            $mform->setDefault('subchapter', (int)!empty($chapter->parentid));
            $showsubchapter = true;
        } else {
            $mform->addElement('hidden', 'subchapter');
            $mform->setType('subchapter', PARAM_INT);
            $mform->setDefault('subchapter', 0);
            $mform->setConstant('subchapter', 0);
            $showsubchapter = false;
        }

        $options = [];
        if (!$firstchapter || $firstchapter->id != $chapter->id) {
            $options[0] = get_string('chapter_position_first', 'mod_mubook');
        }
        foreach ($topchapters as $ch) {
            $title = $toc->get_numbered_chapter_title($ch->id);
            if ($ch->id == $chapter->id) {
                $options[$ch->id] = get_string('choosedots');
            } else {
                $options[$ch->id] = get_string('chapter_position_after', 'mod_mubook', $title);
            }
        }
        $mform->addElement('select', 'positionchapter', get_string('chapter_position', 'mod_mubook'), $options);
        if ($toc->is_orphaned_chapter($chapter->id)) {
            if ($topchapters) {
                $lasttopchapterid = array_key_last($topchapters);
                if (isset($options[$lasttopchapterid])) {
                    $mform->setDefault('positionchapter', $lasttopchapterid);
                }
            }
        } else if ($chapter->parentid) {
            if (isset($options[$chapter->parentid])) {
                $mform->setDefault('positionchapter', $chapter->parentid);
            }
        } else {
            if (isset($options[$chapter->id])) {
                $mform->setDefault('positionchapter', $chapter->id);
            }
        }

        if ($showsubchapter) {
            $mform->hideIf('positionchapter', 'subchapter', 'eq', 1);

            $options = [];
            $positions = [];
            foreach ($topchapters as $ch) {
                $positions[] = $ch->id;
                if ($ch->id == $chapter->id) {
                    $options[''][$ch->id] = get_string('choosedots');
                    continue;
                }
                $optgroup = $toc->get_numbered_chapter_title($ch->id);
                $options[$optgroup][$ch->id] = get_string('subchapter_position_first', 'mod_mubook', $ch->format_title());

                if (isset($subchapters[$ch->id])) {
                    foreach ($subchapters[$ch->id] as $subch) {
                        $positions[] = $subch->id;
                        if ($subch->id == $chapter->id) {
                            $options[$optgroup][$subch->id] = get_string('choosedots');
                            continue;
                        }
                        $title = $toc->get_numbered_chapter_title($subch->id);
                        $options[$optgroup][$subch->id] = get_string('subchapter_position_after', 'mod_mubook', $title);
                    }
                }
            }
            $mform->addElement('selectgroups', 'positionsubchapter', get_string('subchapter_position', 'mod_mubook'), $options);
            if (in_array($chapter->id, $positions)) {
                $mform->setDefault('positionsubchapter', $chapter->id);
            } else {
                $mform->setDefault('positionsubchapter', end($positions));
            }
            $mform->hideIf('positionsubchapter', 'subchapter', 'eq', 0);
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $chapter->id);

        if ($chapter->parentid) {
            $this->add_action_buttons(true, get_string('subchapter_move', 'mod_mubook'));
        } else {
            $this->add_action_buttons(true, get_string('chapter_move', 'mod_mubook'));
        }
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $chapter = $this->_customdata['chapter'];

        if ($data['subchapter']) {
            if ($data['positionsubchapter'] == $chapter->id) {
                $errors['positionsubchapter'] = get_string('required');
            }
        } else {
            if ($data['positionchapter'] == $chapter->id) {
                $errors['positionchapter'] = get_string('required');
            }
        }

        return $errors;
    }
}
