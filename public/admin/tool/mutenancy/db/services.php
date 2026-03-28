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
    'tool_mutenancy_get_tenants' => [
        'classname' => tool_mutenancy\external\get_tenants::class,
        'description' => 'Returns list of tenants',
        'type' => 'read',
        'ajax' => false,
        'capabilities'  => 'tool/mutenancy:view',
    ],

    'tool_mutenancy_create_tenant' => [
        'classname' => tool_mutenancy\external\create_tenant::class,
        'description' => 'Create a new tenant',
        'type' => 'write',
        'ajax' => false,
        'capabilities'  => 'tool/mutenancy:admin',
    ],

    'tool_mutenancy_update_tenant' => [
        'classname' => tool_mutenancy\external\update_tenant::class,
        'description' => 'Update existing tenant',
        'type' => 'write',
        'ajax' => false,
        'capabilities'  => 'tool/mutenancy:admin',
    ],

    'tool_mutenancy_get_managers' => [
        'classname' => tool_mutenancy\external\get_managers::class,
        'description' => 'Returns list of tenant managers',
        'type' => 'read',
        'ajax' => false,
        'capabilities'  => 'tool/mutenancy:view',
    ],

    'tool_mutenancy_add_manager' => [
        'classname' => tool_mutenancy\external\add_manager::class,
        'description' => 'Add user to tenant manager position',
        'type' => 'write',
        'ajax' => false,
        'capabilities'  => 'tool/mutenancy:admin',
    ],

    'tool_mutenancy_remove_manager' => [
        'classname' => tool_mutenancy\external\remove_manager::class,
        'description' => 'Remove user from tenant manager position',
        'type' => 'write',
        'ajax' => false,
        'capabilities'  => 'tool/mutenancy:admin',
    ],

    'tool_mutenancy_allocate_user' => [
        'classname' => tool_mutenancy\external\allocate_user::class,
        'description' => 'Allocate user as tenant member or global user',
        'type' => 'write',
        'ajax' => false,
        'capabilities'  => 'tool/mutenancy:allocate',
    ],

    // Form autocomplete ajax stuff.

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
