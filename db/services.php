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
 * User relations and teams services.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_murelation_form_autocomplete_framework_cohortid' => [
        'classname' => tool_murelation\external\form_autocomplete\framework_cohortid::class,
        'description' => 'Returns list of cohorts for framework settings.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_framework_tenantids' => [
        'classname' => tool_murelation\external\form_autocomplete\framework_tenantids::class,
        'description' => 'Returns list of tenants for allowing them in frameworks.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_team_create_userid' => [
        'classname' => tool_murelation\external\form_autocomplete\team_create_userid::class,
        'description' => 'Returns list of candidates for supervisor position of new team.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_team_create_subuserids' => [
        'classname' => tool_murelation\external\form_autocomplete\team_create_subuserids::class,
        'description' => 'Returns list of subordinate candidates for new team.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_members_add_cohort_cohortid' => [
        'classname' => tool_murelation\external\form_autocomplete\members_add_cohort_cohortid::class,
        'description' => 'Returns list of cohort ids with subordinate candidates for existing team.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_members_create_subuserids' => [
        'classname' => tool_murelation\external\form_autocomplete\members_create_subuserids::class,
        'description' => 'Returns list of subordinate candidates for existing team.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_team_update_userid' => [
        'classname' => tool_murelation\external\form_autocomplete\team_update_userid::class,
        'description' => 'Returns list of candidates for team supervisor position update.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_supervisor_edit_userid' => [
        'classname' => tool_murelation\external\form_autocomplete\supervisor_edit_userid::class,
        'description' => 'Returns list of candidate supervisors for new subordinate positions.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_subordinates_create_select_supuserid' => [
        'classname' => tool_murelation\external\form_autocomplete\subordinates_create_select_supuserid::class,
        'description' => 'Returns list of supervisor candidates for bulk subordinate creation.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_murelation_form_autocomplete_subordinates_create_subuserids' => [
        'classname' => tool_murelation\external\form_autocomplete\subordinates_create_subuserids::class,
        'description' => 'Returns list of subordinate candidates for bulk creation.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
