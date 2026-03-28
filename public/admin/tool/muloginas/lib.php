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
 * Log-in-as plugin core API.
 *
 * @package    tool_muloginas
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 */
function tool_muloginas_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course): void {
    global $OUTPUT;

    if (!\tool_muloginas\local\loginas::can_loginas($user)) {
        return;
    }

    $link = $OUTPUT->render_from_template('tool_muloginas/loginas', ['targetuserid' => $user->id, 'targetuser' => fullname($user)]);

    $node = new  core_user\output\myprofile\node('administration', 'muloginas', $link);
    $tree->add_node($node);
}
