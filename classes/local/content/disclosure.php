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

namespace mod_mubook\local\content;

use mod_mubook\local\toc;
use mod_mubook\hook\content_post_render;

/**
 * Solution disclosure buttons affecting the next content block
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class disclosure extends \mod_mubook\local\content {
    #[\Override]
    public function render(\renderer_base $output, toc $toc, bool $editing, int $firstheading, int $headingoffset = 0): string {
        $options = (object)json_decode($this->data1 ?? '[]');

        $next = null;
        foreach ($this->chapter->get_contents() as $c) {
            if ($c->sortorder == $this->sortorder + 1) {
                $next = $c;
                break;
            }
        }

        $labelshow = $options->labelshow ?? '';
        if (trim($labelshow) === '') {
            $labelshow = get_string('content_type_disclosure_show', 'mod_mubook');
        }
        $labelhide = $options->labelhide ?? '';
        if (trim($labelhide) === '') {
            $labelhide = get_string('content_type_disclosure_hide', 'mod_mubook');
        }
        $labelprinted = $options->labelprinted ?? '';
        if (trim($labelprinted) === '') {
            $labelprinted = get_string('content_type_disclosure_printed', 'mod_mubook');
        }

        $data = [
            'editing' => $editing,
            'contentid' => $this->id,
            'labelshow' => $labelshow,
            'labelhide' => $labelhide,
            'labelprinted' => $labelprinted,
            'targetid' => $next->id ?? null,
        ];

        return $output->render_from_template('mod_mubook/content/disclosure', $data);
    }

    /**
     * Hook callback - wraps target with collapsible dic.
     *
     * @param content_post_render $hook
     * @return void
     */
    public static function callback_content_post_render(content_post_render $hook): void {
        $targettedby = null;
        foreach ($hook->chapter->get_contents() as $content) {
            if ($content->type !== 'disclosure') {
                continue;
            }
            if ($content->sortorder + 1 == $hook->content->sortorder) {
                $targettedby = $content;
            }
        }
        if (!$targettedby) {
            return;
        }

        $data = [
            'targettedbyid' => $targettedby->id,
            'editing' => $hook->editing,
            'html' => $hook->html,
        ];
        $hook->html = $hook->output->render_from_template('mod_mubook/content/disclosure_wrapper', $data);
    }
}
