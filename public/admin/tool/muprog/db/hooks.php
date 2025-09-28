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
 * Programs hook callbacks.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \tool_mutrain\hook\framework_usage::class,
        'callback' => [\tool_muprog\callback\tool_mutrain::class, 'framework_usage'],
    ],
    [
        'hook' => \tool_mutrain\hook\completion_updated::class,
        'callback' => [\tool_muprog\callback\tool_mutrain::class, 'completion_updated'],
    ],
    [
        'hook' => \tool_mutenancy\hook\tenant_management_menu::class,
        'callback' => [\tool_muprog\callback\tool_mutenancy::class, 'tenant_management_menu'],
    ],
];
