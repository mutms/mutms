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
 * Multi-tenancy services.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_mutenancy_form_autocomplete_tenant_assoccohortid' => [
        'classname' => tool_mutenancy\external\form_autocomplete\tenant_assoccohortid::class,
        'description' => 'Return list of cohorts for tenant associated users.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'tool_mutenancy_form_autocomplete_tenant_managers_userids' => [
        'classname' => tool_mutenancy\external\form_autocomplete\tenant_managers_userids::class,
        'description' => 'Return list of candidate users for tenant managers.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'tool_mutenancy_form_autocomplete_associate_add_userids' => [
        'classname' => tool_mutenancy\external\form_autocomplete\associate_add_userids::class,
        'description' => 'Return list of candidate users for tenant association.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'tool_mutenancy_form_autocomplete_user_allocate_tenantid' => [
        'classname' => tool_mutenancy\external\form_autocomplete\user_allocate_tenantid::class,
        'description' => 'Return list of tenant candidates for tenant managers.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
