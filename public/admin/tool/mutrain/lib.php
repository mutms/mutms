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
 * Training plugin api.
 *
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function extends the category navigation with frameworks.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $coursecategorycontext The context of the course category
 */
function tool_mutrain_extend_navigation_category_settings($navigation, $coursecategorycontext): void {
    if (!has_capability('tool/mutrain:viewframeworks', $coursecategorycontext)) {
        return;
    }

    // NOTE: catnav is added to unbreak breadcrums on management pages.
    $settingsnode = navigation_node::create(
        get_string('frameworks', 'tool_mutrain'),
        new moodle_url('/admin/tool/mutrain/management/index.php', ['contextid' => $coursecategorycontext->id, 'catnav' => 1]),
        navigation_node::TYPE_CUSTOM,
        null,
        'tool_mutrain_frameworks'
    );
    $settingsnode->set_force_into_more_menu(true);
    $navigation->add_node($settingsnode);
}
