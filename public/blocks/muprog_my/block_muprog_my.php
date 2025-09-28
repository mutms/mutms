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
 * My programs overview block.
 *
 * @package     block_muprog_my
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_muprog_my extends block_base {
    /**
     * Block init.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_muprog_my');
    }

    #[\Override]
    public function get_content() {
        if (isset($this->content)) {
            return $this->content;
        }

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        if (!\tool_muprog\local\util::is_muprog_active()) {
            return null;
        }

        /** @var \tool_muprog\output\my\renderer $myouput */
        $myouput = $this->page->get_renderer('tool_muprog', 'my');

        $this->content = new stdClass();
        $this->content->text = $myouput->render_block_content();
        $this->content->footer = $myouput->render_block_footer();

        return $this->content;
    }

    #[\Override]
    public function applicable_formats() {
        return ['all' => true];
    }

    #[\Override]
    public function has_config() {
        return false;
    }

    #[\Override]
    public function can_block_be_added(moodle_page $page): bool {
        return \tool_muprog\local\util::is_muprog_active();
    }
}
