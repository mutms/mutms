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
use mod_mubook\local\content;
use core\output\pix_icon;
use core\url;

/**
 * Hook for adding of interactive book content actions.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Interactive book content actions dropdown')]
#[\core\attribute\tags('mod_mubook')]
final class content_actions {
    /** @var content */
    public $content;
    /** @var chapter */
    public $chapter;
    /** @var toc */
    public $toc;
    /** @var url */
    public $pageurl;
    /** @var bool */
    public $editing;

    /** @var \tool_mulib\output\dropdown */
    public $dropdown;

    /**
     * Content actions dropdown constructor.
     *
     * @param content $content
     * @param chapter $chapter
     * @param toc $toc
     * @param url $pageurl
     * @param bool $editing
     */
    public function __construct(content $content, chapter $chapter, toc $toc, url $pageurl, bool $editing) {
        $this->dropdown = new \tool_mulib\output\dropdown(get_string('content_actions_a', 'mod_mubook', $content->sortorder));
        $this->content = $content;
        $this->chapter = $chapter;
        $this->toc = $toc;
        $this->pageurl = $pageurl;
        $this->editing = $editing;

        if (!$editing) {
            // No actions in normal mode!
            return;
        }

        if ($content->can_update()) {
            $url = $content->get_update_url();
            $this->dropdown->add_item(get_string('content_update', 'mod_mubook'), $url, new pix_icon('i/edit', ''));
        }

        if ($content->can_delete()) {
            $link = $content->get_delete_link();
            $this->dropdown->add_ajax_form($link);
        }

        \core\di::get(\core\hook\manager::class)->dispatch($this);
    }
}
