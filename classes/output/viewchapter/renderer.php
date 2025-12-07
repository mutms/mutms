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

namespace mod_mubook\output\viewchapter;

use mod_mubook\local\toc;
use mod_mubook\local\chapter;

/**
 * Renderer for viewchapter.php file.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render chapter view.
     *
     * @param chapter $chapter
     * @param toc $toc
     * @return string
     */
    public function render_page(chapter $chapter, toc $toc): string {
        $editing = $this->page->user_is_editing();

        $tags = \core_tag_tag::get_item_tags('mod_mubook', 'mubook_chapter', $chapter->id);
        if ($tags) {
            $tags = $this->output->tag_list($tags, '', 'chapter-tags');
        } else {
            $tags = null;
        }

        $actions = new \mod_mubook\hook\chapter_actions($chapter, $toc, $this->page->url, $editing);
        $actionshtml = '';
        if ($actions->get_extra_button()) {
            $actionshtml .= $this->render($actions->get_extra_button());
        }
        if ($actions->dropdown->has_items()) {
            $actionshtml .= $this->render($actions->dropdown);
        }
        if ($actionshtml === '') {
            $actionshtml = null;
        }
        $data = [
            'chapterid' => $chapter->id,
            'numbers' => $toc->format_chapter_numbers($chapter->id),
            'title' => $chapter->format_title(),
            'tags' => $tags,
            'actions' => $actionshtml,
            'contents' => $this->render_contents($chapter, $toc),
            'subchapters' => $this->get_subchapters_data($chapter, $toc, $editing),
        ];
        $data['has_subchapters'] = !empty($data['subchapters']);

        $previous = $toc->get_previous_chapter($chapter->id);
        if ($previous) {
            $url = new \core\url('/mod/mubook/viewchapter.php', ['id' => $previous->id]);
            $data['previous'] = [
                'title' => get_string('chapter_previous_a', 'mod_mubook', $previous->format_title()),
                'url' => $url,
            ];
        } else {
            $data['previous'] = [
                'title' => get_string('book_toc', 'mod_mubook'),
                'url' => new \core\url('/mod/mubook/view.php', ['id' => $toc->get_context()->instanceid]),
            ];
        }

        $next = $toc->get_next_chapter($chapter->id);
        if ($next) {
            $url = new \core\url('/mod/mubook/viewchapter.php', ['id' => $next->id]);
            $data['next'] = [
                'title' => get_string('chapter_next_a', 'mod_mubook', $next->format_title()),
                'url' => $url,
            ];
        } else {
            $data['next'] = [
                'title' => get_string('book_toc', 'mod_mubook'),
                'url' => new \core\url('/mod/mubook/view.php', ['id' => $toc->get_context()->instanceid]),
            ];
        }

        return $this->render_from_template('mod_mubook/page_viewchapter', $data);
    }

    /**
     * Get chapter TOC rendering data.
     *
     * @param chapter $chapter
     * @param toc $toc
     * @param bool $editing
     * @return array
     */
    public function get_subchapters_data(chapter $chapter, toc $toc, bool $editing): array {
        if ($chapter->parentid || $toc->is_orphaned_chapter($chapter->id)) {
            return [];
        }
        $data = [];
        foreach ($toc->get_chapters() as $subchapter) {
            if (!$subchapter->parentid || $subchapter->parentid != $chapter->id) {
                continue;
            }
            $actions = new \mod_mubook\hook\chapter_actions($subchapter, $toc, $this->page->url, $editing);
            $data[] = [
                'chapterid' => $subchapter->id,
                'numbers' => $toc->format_chapter_numbers($subchapter->id),
                'title' => $subchapter->format_title(),
                'url' => new \core\url('/mod/mubook/viewchapter.php', ['id' => $subchapter->id]),
                'actions' => $actions->dropdown->has_items() ? $this->render($actions->dropdown) : null,
            ];
        }
        return $data;
    }

    /**
     * Render chapter contents.
     *
     * @param chapter $chapter
     * @param toc $toc
     * @return string
     */
    public function render_contents(chapter $chapter, toc $toc): string {
        $editing = $this->page->user_is_editing();
        $contents = $chapter->get_contents();

        $html = '';
        foreach ($contents as $content) {
            if (!$content->can_view()) {
                if (!$editing) {
                    continue;
                }
                $data = [
                    'chapterid' => $chapter->id,
                    'contentid' => $content->id,
                ];
                $html .= $this->render_from_template('mod_mubook/content_unavailable', $data);
                continue;
            }
            $actions = new \mod_mubook\hook\content_actions($content, $chapter, $toc, $this->page->url, $editing);

            // We want smaller headings to match viewall page.
            $contenthtml = $content->render($this, $toc, $editing, 3, 1);
            $post = new \mod_mubook\hook\content_post_render($contenthtml, $content, $chapter, $this, $toc, $editing, 3, 1);
            $contenthtml = $post->html;

            $data = [
                'chapterid' => $chapter->id,
                'contentid' => $content->id,
                'type' => $content::get_type(),
                'editing' => $editing,
                'hidden' => (bool)$content->hidden,
                'actions' => $actions->dropdown->has_items() ? $this->render($actions->dropdown) : null,
                'html' => $contenthtml,
            ];
            $html .= $this->render_from_template('mod_mubook/content', $data);
        }
        return $html;
    }
}
