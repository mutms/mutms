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

namespace tool_muhome\local;

use core\url;
use tool_mulib\output\header_actions;
use tool_mulib\output\ajax_form\button;
use tool_mulib\local\sql;
use stdClass;

/**
 * Custom home pages management UI helper.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Set up $PAGE and navigation.
     *
     * @param url $pageurl
     * @param \context $context
     */
    public static function setup_index_page(url $pageurl, \context $context): void {
        global $PAGE, $OUTPUT;

        $syscontext = \context_system::instance();

        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_pagelayout('admin');
        $PAGE->set_title(get_string('management', 'tool_muhome'));
        $PAGE->set_heading(get_string('management', 'tool_muhome'));
        $PAGE->set_secondary_navigation(false);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('pages', 'tool_muhome');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/muhome:view', $parentcontext)) {
                $url = new url('/admin/tool/muhome/management/index.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $actions = new header_actions(get_string('management_actions', 'tool_muhome'));
        if (has_capability('tool/muhome:manage', $context)) {
            $url = new url('/admin/tool/muhome/management/page_create.php', ['contextid' => $context->id]);
            $button = new button($url, get_string('page_create', 'tool_muhome'));
            $actions->add_button($button);
        }
        if (has_capability('moodle/site:config', $syscontext)) {
            $url = new url('/admin/settings.php', ['section' => 'tool_muhome_settings']);
            $actions->get_dropdown()->add_item(get_string('settings', 'tool_muhome'), $url, new \core\output\pix_icon('i/settings', ''));
        }

        if ($actions->has_items()) {
            $PAGE->add_header_action($OUTPUT->render($actions));
        }
    }

    /**
     * Hint for custom home page managers.
     *
     * @param stdClass $page
     * @param \context $context
     * @return string|null
     */
    public static function get_page_hint(stdClass $page, \context $context): ?string {
        global $PAGE, $DB;

        if ($PAGE->user_is_editing()) {
            return null;
        }
        if (!has_capability('tool/muhome:manage', $context)) {
            return null;
        }
        if ($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page->id])) {
            return null;
        }

        $hint = markdown_to_html(get_string('edit_mode_hint', 'tool_muhome'));
        return $hint;
    }
}
