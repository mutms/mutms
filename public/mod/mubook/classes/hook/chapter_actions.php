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

namespace mod_mubook\hook;

use mod_mubook\local\toc;
use mod_mubook\local\chapter;
use core\url;
use tool_mulib\output\ajax_form\button;

/**
 * Hook for adding of interactive book chapter actions.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Interactive book chapter actions dropdown')]
#[\core\attribute\tags('mod_mubook')]
final class chapter_actions {
    /** @var chapter */
    public $chapter;
    /** @var toc */
    public $toc;
    /** @var url */
    public $pageurl;
    /** @var bool */
    public $editing;
    /** @var button */
    protected $button;

    /** @var \tool_mulib\output\dropdown */
    public $dropdown;

    /**
     * Chapter actions dropdown constructor.
     *
     * @param chapter $chapter
     * @param toc $toc
     * @param url $pageurl
     * @param bool $editing
     */
    public function __construct(chapter $chapter, toc $toc, url $pageurl, bool $editing) {
        $mubook = $toc->get_mubook();
        $context = $toc->get_context();
        $orphaned = $toc->is_orphaned_chapter($chapter->id);

        $viewallurl = new url('/mod/mubook/viewall.php', ['id' => $context->instanceid]);
        $viewurl = new url('/mod/mubook/view.php', ['id' => $context->instanceid]);
        $viewchapterurl = new url('/mod/mubook/viewchapter.php', ['id' => $chapter->id]);

        $isviewurl = $pageurl->compare($viewurl, URL_MATCH_BASE);
        $isviewchapterurl = $pageurl->compare($viewchapterurl, URL_MATCH_BASE);

        $chaptertitle = $chapter->format_title();

        if ($isviewurl) {
            if ($chapter->parentid) {
                $label = get_string('subchapter_actions_a', 'mod_mubook', $chaptertitle);
            } else {
                $label = get_string('chapter_actions_a', 'mod_mubook', $chaptertitle);
            }
        } else {
            if ($chapter->parentid) {
                if ($isviewchapterurl && $pageurl->param('id') != $chapter->id) {
                    $label = get_string('subchapter_actions_a', 'mod_mubook', $chaptertitle);
                } else {
                    $label = get_string('subchapter_actions', 'mod_mubook');
                }
            } else {
                $label = get_string('chapter_actions', 'mod_mubook');
            }
        }

        $this->dropdown = new \tool_mulib\output\dropdown($label);

        $this->chapter = $chapter;
        $this->toc = $toc;
        $this->pageurl = $pageurl;
        $this->editing = $editing;

        if (!$editing) {
            // No actions in normal mode,
            // use book_actions if there are any extras in normal mode.
            return;
        }
        if ($pageurl->compare($viewallurl, URL_MATCH_BASE)) {
            // Do not clutter viewall page with any actions,
            // use book_actions if there are any extras needed.
            return;
        }

        $cman = \core\di::get(\mod_mubook\local\content_manager::class);

        $chapters = $toc->get_chapters();
        $subchapters = [];
        foreach ($chapters as $ch) {
            if ($ch->parentid) {
                $subchapters[$ch->parentid][$ch->id] = true;
            }
        }

        if ($chapter->can_update()) {
            $link = $chapter->get_update_link();
            $this->dropdown->add_ajax_form($link);
        }

        if ($chapter->can_move()) {
            if ($orphaned || count($chapters) > 1) {
                $link = $chapter->get_move_link();
                if ($isviewurl) {
                    $link->set_submitted_action($link::SUBMITTED_ACTION_RELOAD);
                }
                $this->dropdown->add_ajax_form($link);
            }
        }

        if ($chapter->can_delete()) {
            $link = $chapter->get_delete_link();
            if ($isviewurl) {
                $link->set_submitted_action($link::SUBMITTED_ACTION_RELOAD);
            } else if ($isviewchapterurl && $pageurl->param('id') != $chapter->id) {
                $link->set_submitted_action($link::SUBMITTED_ACTION_RELOAD);
            }
            $this->dropdown->add_ajax_form($link);
        }

        if (!$orphaned && chapter::can_create($mubook, $context)) {
            $links = [];

            if ($isviewurl) {
                if ($chapter->parentid) {
                    $links[] = chapter::get_create_link($mubook, $chapter->id, true, 0);
                } else {
                    $links[] = chapter::get_create_link($mubook, $chapter->id, false, 0);
                    if (isset($subchapters[$chapter->id])) {
                        $lastsubchapterid = array_key_last($subchapters[$chapter->id]);
                        $links[] = chapter::get_create_link($mubook, $lastsubchapterid, true, 0);
                    } else {
                        $links[] = chapter::get_create_link($mubook, $chapter->id, true, 0);
                    }
                }
            } else if ($isviewchapterurl) {
                if (!$chapter->parentid) {
                    $fromcreatechapterid = $pageurl->param('id') ?? -1;
                    $lastsubchapter = $toc->get_last_subchapter($chapter->id);
                    $link = chapter::get_create_link($mubook, $lastsubchapter->id ?? $chapter->id, true, $fromcreatechapterid);
                    $this->button = $link->create_button(true, false, true);
                }
            }

            if ($links) {
                if ($this->dropdown->has_items()) {
                    $this->dropdown->add_divider();
                }
                foreach ($links as $link) {
                    $this->dropdown->add_ajax_form($link);
                }
            }
        }

        if ($isviewchapterurl && $cman->can_create_content($chapter, $mubook, $context)) {
            if ($chapter->parentid) {
                $link = $cman->get_create_content_link($chapter, 0);
                $this->button = $link->create_button(true, false, true);
            } else {
                if ($cman->can_create_content($chapter, $mubook, $context)) {
                    if ($this->dropdown->has_items()) {
                        $this->dropdown->add_divider();
                    }
                    $link = $cman->get_create_content_link($chapter, 0);
                    $this->dropdown->add_ajax_form($link);
                }
            }
        }

        \core\di::get(\core\hook\manager::class)->dispatch($this);
    }

    /**
     * Returns additional header action button.
     *
     * @return button|null
     */
    public function get_extra_button(): ?button {
        return $this->button;
    }
}
