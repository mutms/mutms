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
 * Custom home pages hook callbacks.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \tool_mutenancy\hook\tenant_management_menu::class,
        'callback' => [\tool_muhome\callback\tool_mutenancy::class, 'hook_tenant_management_menu'],
        'priority' => 1000, // Move to the top after tenant category.
    ],
    [
        'hook' => \tool_mutenancy\hook\pre_tenant_delete::class,
        'callback' => [\tool_muhome\callback\tool_mutenancy::class, 'hook_pre_tenant_delete'],
    ],
    [
        'hook' => \core\hook\after_config::class,
        'callback' => [\tool_muhome\callback\core::class, 'hook_after_config'],
        'priority' => -99999999, // As low as possible to allow redirect when user not-logged-in.
    ],
    [
        'hook' => \core\hook\navigation\primary_extend::class,
        'callback' => [\tool_muhome\callback\core::class, 'hook_primary_extend'],
        'priority' => -99999999, // As low as possible to override all other observer changes.
    ],
];
