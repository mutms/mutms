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
 * Supervisors and teams capabilities.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    /* View all user relation frameworks. */
    'tool/murelation:viewframeworks' => [
        'captype' => 'read',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    /* Add, update and delete user relation frameworks. */
    'tool/murelation:manageframeworks' => [
        'captype' => 'write',
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    /* View all supervisors and subordinates of a user in Supervisors mode, team membership in Teams mode. */
    'tool/murelation:viewpositions' => [
        'captype' => 'write',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_USER, // System and tenant for team; user context for supervisors.
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    /*
     * Add, update and delete teams or users supervisors.
     */
    'tool/murelation:managepositions' => [
        'captype' => 'write',
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'contextlevel' => CONTEXT_USER, // System and tenant for team; user context for supervisors.
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
