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
use core\output\pix_icon;
use core\url;

/**
 * Hook for adding of interactive book actions.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Interactive book actions dropdown')]
#[\core\attribute\tags('mod_mubook')]
final class book_actions {
    /** @var toc */
    public $toc;
    /** @var string */
    public $pageurl;
    /** @var bool */
    public $editing;

    /** @var \tool_mulib\output\dropdown */
    public $dropdown;

    /**
     * Book actions dropdown constructor.
     *
     * @param toc $toc
     * @param url $pageurl
     * @param bool $editing
     */
    public function __construct(toc $toc, url $pageurl, bool $editing) {
        $this->dropdown = new \tool_mulib\output\dropdown(get_string('book_actions', 'mod_mubook'));
        $this->toc = $toc;
        $this->pageurl = $pageurl;
        $this->editing = $editing;

        $context = $toc->get_context();

        if ($toc->get_chapters()) {
            $viewall = new url('/mod/mubook/viewall.php', ['id' => $context->instanceid]);
            if (!$pageurl->compare($viewall, URL_MATCH_BASE)) {
                if (has_capability('mod/mubook:viewall', $context)) {
                    $this->dropdown->add_item(get_string('book_viewall', 'mod_mubook'), $viewall, new pix_icon('viewall', '', 'mod_mubook'));
                }
            }
            $viewurl = new url('/mod/mubook/view.php', ['id' => $context->instanceid]);
            if (!$pageurl->compare($viewurl, URL_MATCH_BASE)) {
                if (has_capability('mod/mubook:view', $context)) {
                    $this->dropdown->add_item(get_string('book_toc', 'mod_mubook'), $viewurl, new pix_icon('toc', '', 'mod_mubook'));
                }
            }
        }

        \core\di::get(\core\hook\manager::class)->dispatch($this);
    }
}
