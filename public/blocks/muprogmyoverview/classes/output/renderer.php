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

namespace block_muprogmyoverview\output;

use plugin_renderer_base;

/**
 * My programs overview block renderer.
 *
 * @package    block_muprogmyoverview
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @copyright  2025 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Return the main content for the block overview.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main(main $main) {
        if (!\block_muprogmyoverview\local\util::count_active_programs()) {
            return $this->render_from_template(
                'block_muprogmyoverview/zero-state',
                $main->export_for_zero_state_template($this)
            );
        }
        return $this->render_from_template('block_muprogmyoverview/main', $main->export_for_template($this));
    }
}
