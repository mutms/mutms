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

/**
 * My overview block class.
 *
 * @package    block_muprogmyoverview
 * @copyright  Mark Nelson <markn@moodle.com>
 * @copyright  2025 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_muprogmyoverview extends block_base {
    /**
     * Init.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_muprogmyoverview');
    }

    /**
     * Returns the contents.
     *
     * @return stdClass contents of block
     */
    public function get_content(): stdClass {
        if (isset($this->content)) {
            return $this->content;
        }

        if (!\tool_mulib\local\mulib::is_muprog_available()) {
            $this->content = new stdClass();
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        $group = get_user_preferences('block_muprogmyoverview_user_grouping_preference');
        $sort = get_user_preferences('block_muprogmyoverview_user_sort_preference');
        $view = get_user_preferences('block_muprogmyoverview_user_view_preference');
        $paging = get_user_preferences('block_muprogmyoverview_user_paging_preference');

        $renderable = new \block_muprogmyoverview\output\main($group, $sort, $view, $paging);
        $renderer = $this->page->get_renderer('block_muprogmyoverview');

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;
    }

    #[\Override]
    public function applicable_formats(): array {
        return [
            'course-view' => false,
            'site' => false,
            'mod' => false,
            'my' => false,
            'block-muprogmyoverview' => true,
        ];
    }

    #[\Override]
    public function has_config(): bool {
        return true;
    }

    #[\Override]
    public function get_config_for_external(): stdClass {
        // Return all settings for all users since it is safe (no private keys, etc..).
        return (object) [
            'instance' => new stdClass(),
            'plugin' => get_config('block_muprogmyoverview'),
        ];
    }

    #[\Override]
    public function instance_can_be_edited(): bool {
        return false;
    }

    #[\Override]
    public function instance_can_be_hidden(): bool {
        return false;
    }

    #[\Override]
    public function instance_can_be_collapsed(): bool {
        return false;
    }

    #[\Override]
    public function hide_header(): bool {
        if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
            return false;
        }
        return true;
    }

    #[\Override]
    public function can_block_be_added(moodle_page $page): bool {
        return \tool_mulib\local\mulib::is_muprog_available();
    }
}
