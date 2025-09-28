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
 * Certification external functions.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_mucertify_form_autocomplete_certification_periods_programid' => [
        'classname' => tool_mucertify\external\form_autocomplete\certification_periods_programid::class,
        'description' => 'Return list of user candidates for program allocation.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucertify_form_autocomplete_source_manual_assign_users' => [
        'classname' => tool_mucertify\external\form_autocomplete\source_manual_assign_users::class,
        'description' => 'Return list of user candidates for certification assignment.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucertify_form_autocomplete_certification_visibility_edit_cohortids' => [
        'classname' => tool_mucertify\external\form_autocomplete\certification_visibility_edit_cohortids::class,
        'description' => 'Return list of cohorts for certification visibility.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mucertify_form_autocomplete_source_cohort_edit_cohortids' => [
        'classname' => tool_mucertify\external\form_autocomplete\source_cohort_edit_cohortids::class,
        'description' => 'Return list of cohorts for cohort allocation.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
