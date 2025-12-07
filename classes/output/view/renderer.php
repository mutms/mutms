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

namespace mod_mubook\output\view;

use mod_mubook\local\toc;
use mod_mubook\local\chapter;

/**
 * Renderer for view.php file in edit mode.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render page with editable TOC.
     *
     * @param toc $toc
     * @return string
     */
    public function render_page(toc $toc): string {
        if (!$toc->get_chapters()) {
            return $this->render_page_empty($toc);
        }

        $chapters = $toc->get_chapters();
        $editing = $this->page->user_is_editing();

        $topchapters = [];
        $subchapters = [];
        foreach ($chapters as $chapter) {
            if ($chapter->parentid) {
                $subchapters[$chapter->parentid][$chapter->id] = $chapter;
            } else {
                $topchapters[$chapter->id] = $chapter;
            }
        }
        $firstchapter = $toc->get_first_chapter();
        $firstchapterurl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $firstchapter->id]);

        $data = [
            'editing' => $editing,
            'chapters' => [],
            'orphaned' => [],
            'next' => [
                'title' => get_string('chapter_first_a', 'mod_mubook', $firstchapter->format_title()),
                'url' => $firstchapterurl,
            ],
        ];

        foreach ($topchapters as $chapter) {
            $actions = new \mod_mubook\hook\chapter_actions($chapter, $toc, $this->page->url, $editing);
            $ch = [
                'usecolumns' => false,
                'chapterid' => $chapter->id,
                'numbers' => $toc->format_chapter_numbers($chapter->id),
                'title' => $chapter->format_title(),
                'subchapters' => [],
                'url' => new \core\url('/mod/mubook/viewchapter.php', ['id' => $chapter->id]),
                'actions' => $actions->dropdown->has_items() ? $this->render($actions->dropdown) : null,
            ];
            if (isset($subchapters[$chapter->id])) {
                if (!$editing && count($subchapters[$chapter->id]) > 2) {
                    $ch['usecolumns'] = true;
                }
                foreach ($subchapters[$chapter->id] as $subchapter) {
                    $actions = new \mod_mubook\hook\chapter_actions($subchapter, $toc, $this->page->url, $editing);
                    $ch['subchapters'][] = [
                        'chapterid' => $subchapter->id,
                        'numbers' => $toc->format_chapter_numbers($subchapter->id),
                        'title' => $subchapter->format_title(),
                        'url' => new \core\url('/mod/mubook/viewchapter.php', ['id' => $subchapter->id]),
                        'actions' => $actions->dropdown->has_items() ? $this->render($actions->dropdown) : null,
                    ];
                }
            }
            $ch['has_subchapters'] = !empty($ch['subchapters']);
            $data['chapters'][] = $ch;
        }

        // There should not be any orphaned chapters, it is the last resort to prevent data loss.
        if ($editing) {
            foreach ($toc->get_orphaned_chapters() as $subchapter) {
                if (!$subchapter->can_update()) {
                    continue;
                }
                $actions = new \mod_mubook\hook\chapter_actions($subchapter, $toc, $this->page->url, $editing);
                $data['orphaned']['subchapters'] = [
                    'chapterid' => $subchapter->id,
                    'numbers' => null,
                    'title' => $subchapter->format_title(),
                    'url' => new \core\url('/mod/mubook/viewchapter.php', ['id' => $subchapter->id]),
                    'actions' => $actions->dropdown->has_items() ? $this->render($actions->dropdown) : null,
                ];
            }
        }

        return $this->render_from_template('mod_mubook/page_view', $data);
    }

    /**
     * Render page with just buttons.
     *
     * @param toc $toc
     * @return string
     */
    public function render_page_empty(toc $toc): string {
        $mubook = $toc->get_mubook();
        $context = $toc->get_context();

        $result = '';

        if (!$this->page->user_is_editing() && chapter::can_create($mubook, $context)) {
            $result .= $this->notification(get_string('nocontent_edit', 'mod_mubook'), 'warning', false);
        } else {
            $result .= $this->notification(get_string('nocontent', 'mod_mubook'), 'info', false);
        }

        return $result;
    }
}
