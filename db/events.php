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
 * Program enrolment plugin events.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => \core\event\course_updated::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_updated',
    ],
    [
        'eventname'   => \core\event\course_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_deleted',
    ],
    [
        'eventname'   => \core\event\course_category_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_category_deleted',
    ],
    [
        'eventname'   => \core\event\user_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::user_deleted',
    ],
    [
        'eventname'   => \core\event\cohort_member_added::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::cohort_member_added',
    ],
    [
        'eventname'   => \core\event\cohort_member_removed::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::cohort_member_removed',
    ],
    [
        'eventname'   => \core\event\course_completed::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_completed',
    ],
    [
        'eventname'   => \core\event\group_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::group_deleted',
    ],
    [
        'eventname' => \tool_certificate\event\template_deleted::class,
        'callback' => \tool_muprog\local\certificate::class . '::template_deleted',
    ],
    [
        'eventname' => \tool_muprog\event\allocation_completed::class,
        'callback' => \tool_muprog\local\source\program::class . '::observe_allocation_completed',
    ],
];
