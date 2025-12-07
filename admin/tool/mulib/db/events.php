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
 * Additional tool library event observers.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => \tool_mutenancy\event\tenant_created::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_tenant_created',
    ],
    [
        'eventname' => \tool_mutenancy\event\tenant_deleted::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_tenant_deleted',
    ],
    [
        'eventname' => \core\event\user_created::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_user_created',
    ],
    [
        'eventname' => \tool_mutenancy\event\user_allocated::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_user_allocated',
    ],
    [
        'eventname' => \core\event\user_deleted::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_user_deleted',
    ],
    [
        'eventname' => \core\event\course_category_created::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_course_category_created',
    ],
    [
        'eventname' => \core\event\course_category_updated::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_course_category_updated',
    ],
    [
        'eventname' => \core\event\course_category_deleted::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_course_category_deleted',
    ],
    [
        'eventname' => \core\event\course_created::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_course_created',
    ],
    [
        'eventname' => \core\event\course_updated::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_course_updated',
    ],
    [
        'eventname' => \core\event\course_deleted::class,
        'callback' => \tool_mulib\local\context_map_builder::class . '::callback_course_deleted',
    ],
];
