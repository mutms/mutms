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

namespace mod_mubook\output\viewall;

use mod_mubook\local\toc;
use mod_mubook\local\chapter;

/**
 * Renderer for viewall.php file.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render page with all chapters.
     *
     * @param toc $toc
     * @return string
     */
    public function render_page(toc $toc): string {
        global $USER;

        $chapters = $toc->get_chapters();

        $data = [
            'chapters' => [],
        ];

        foreach ($chapters as $chapter) {
            $ch = [
                'chapterid' => $chapter->id,
                'subchapter' => !empty($chapter->parentid),
                'numbers' => $toc->format_chapter_numbers($chapter->id),
                'title' => $chapter->format_title(),
                'relurl' => '#mubook-chapter-' . $chapter->id,
                'url' => new \core\url('/mod/mubook/viewchapter.php', ['id' => $chapter->id]),
                'contents' => $this->render_contents($chapter, $toc),
            ];
            $data['chapters'][] = $ch;
        }

        return $this->render_from_template('mod_mubook/page_viewall', $data);
    }

    /**
     * Render chapter contents.
     *
     * @param chapter $chapter
     * @param toc $toc
     * @return string
     */
    public function render_contents(chapter $chapter, toc $toc): string {
        $contents = $chapter->get_contents();

        $html = '';
        foreach ($contents as $content) {
            if ($content->hidden || !$content->can_view()) {
                // No hidden chapters in full view!
                continue;
            }
            if ($chapter->parentid) {
                $firstheading = 4;
            } else {
                $firstheading = 3;
            }
            $contenthtml = $content->render($this, $toc, false, $firstheading, 0);
            $post = new \mod_mubook\hook\content_post_render($contenthtml, $content, $chapter, $this, $toc, false, $firstheading, 0);
            $contenthtml = $post->html;

            $data = [
                'chapterid' => $chapter->id,
                'contentid' => $content->id,
                'type' => $content::get_type(),
                'html' => $contenthtml,
            ];
            $html .= $this->render_from_template('mod_mubook/content', $data);
        }
        return $html;
    }
}
