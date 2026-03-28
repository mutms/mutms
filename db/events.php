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
 * User relations and teams event observers.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // NOTE: do not observe cohort deletion here, invalid cohortid means no access or no candidates.
    [
        'eventname' => \core\event\user_deleted::class,
        'callback' => \tool_murelation\local\callbacks::class . '::user_deleted',
    ],
    [
        'eventname' => \tool_mutenancy\event\tenant_deleted::class,
        'callback' => \tool_murelation\local\callbacks::class . '::tenant_deleted',
    ],
    [
        'eventname' => \tool_mutenancy\event\user_allocated::class,
        'callback' => \tool_murelation\local\callbacks::class . '::user_allocated',
    ],
];
