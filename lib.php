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
 * Sudoers core api.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_musudo\local\util;
use tool_musudo\local\sudoer;

/**
 * Allow users to obtain privileged access if allowed.
 *
 * @param renderer_base $renderer
 * @return string
 */
function tool_musudo_render_navbar_output(renderer_base $renderer): string {
    global $OUTPUT;
    if (!util::is_musudo_active()) {
        return '';
    }

    if (sudoer::is_sudo_started()) {
        $c = [
            'url' => new moodle_url('/admin/tool/musudo/sudo_end.php', ['sesskey' => sesskey()]),
        ];
        return $OUTPUT->render_from_template('tool_musudo/active', $c);
    }

    return '';
}
