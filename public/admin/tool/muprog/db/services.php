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
 * Program external functions.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // Form element autocompletion WS.
    'tool_muprog_form_autocomplete_source_manual_allocate_users' => [
        'classname' => tool_muprog\external\form_autocomplete\source_manual_allocate_users::class,
        'description' => 'Return list of user candidates for program allocation.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_program_visibility_edit_cohortids' => [
        'classname' => tool_muprog\external\form_autocomplete\program_visibility_edit_cohortids::class,
        'description' => 'Return list of cohorts for program visibility.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_source_cohort_edit_cohortids' => [
        'classname' => tool_muprog\external\form_autocomplete\source_cohort_edit_cohortids::class,
        'description' => 'Return list of cohorts for cohort allocation.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_program_content_import_fromprogram' => [
        'classname' => tool_muprog\external\form_autocomplete\program_content_import_fromprogram::class,
        'description' => 'Return list of programs that can be used as source for importing of content.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_notification_import_frominstance' => [
        'classname' => tool_muprog\external\form_autocomplete\notification_import_frominstance::class,
         'description' => 'Return list of programs that can be used as source for importing of notifications.',
         'type' => 'read',
         'ajax' => true,
         'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_program_allocation_import_fromprogram' => [
        'classname' => tool_muprog\external\form_autocomplete\program_allocation_import_fromprogram::class,
        'description' => 'Return list of programs that can be used as source for importing program allocation.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_item_append_trainingid' => [
        'classname' => \tool_muprog\external\form_autocomplete\item_append_trainingid::class,
        'description' => 'Return list of framework candidates for adding of items.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_export_programids' => [
        'classname' => \tool_muprog\external\form_autocomplete\export_programids::class,
        'description' => 'Return list of candidate programs for export.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muprog_form_autocomplete_source_program_edit_programid' => [
        'classname' => \tool_muprog\external\form_autocomplete\source_program_edit_programid::class,
        'description' => 'Return list of candidate programs for allocation based on other program completion.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    // Real web services follow.
    'tool_muprog_get_programs' => [
        'classname' => tool_muprog\external\get_programs::class,
        'description' => 'Return list of programs that match the search parameters.',
        'type' => 'read',
    ],
    'tool_muprog_get_program_allocations' => [
        'classname' => tool_muprog\external\get_program_allocations::class,
        'description' => 'Return list of program allocations for given programid and optional userids.',
        'type' => 'read',
    ],
    'tool_muprog_source_manual_allocate_users' => [
        'classname' => tool_muprog\external\source_manual_allocate_users::class,
        'description' => 'Allocates users or cohorts to the program.',
        'type' => 'write',
    ],
    'tool_muprog_delete_program_allocations' => [
        'classname' => tool_muprog\external\delete_program_allocations::class,
        'description' => 'Deallocates users from the program.',
        'type' => 'write',
    ],
    'tool_muprog_update_program_allocation' => [
        'classname' => tool_muprog\external\update_program_allocation::class,
        'description' => 'Updates the allocation for the user and the program.',
        'type' => 'write',
    ],
    'tool_muprog_source_cohort_get_cohorts' => [
        'classname' => tool_muprog\external\source_cohort_get_cohorts::class,
        'description' => 'Gets list of cohort that are synced with the program cohort allocation.',
        'type' => 'read',
    ],
    'tool_muprog_source_cohort_add_cohort' => [
        'classname' => tool_muprog\external\source_cohort_add_cohort::class,
        'description' => 'Add cohort to the list of synchronised cohorts of one program.',
        'type' => 'write',
    ],
    'tool_muprog_source_cohort_delete_cohort' => [
        'classname' => tool_muprog\external\source_cohort_delete_cohort::class,
        'description' => 'Removes a cohort from the list of synchronised cohorts of one program.',
        'type' => 'write',
    ],
];
