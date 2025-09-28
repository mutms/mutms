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
 * Multi-tenancy capabilities.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // View tenant details.
    'tool/mutenancy:view' => [
        'captype' => 'read',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Add, update and delete tenants; add and remove tenant managers.
    'tool/mutenancy:admin' => [
        'captype' => 'write',
        'riskbitmask' => RISK_PERSONAL || RISK_DATALOSS || RISK_SPAM,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW, // Excluded from tenantmanager archetype.
        ],
    ],
    // Switch to tenant site - not applicable to tenant members, view cap is also needed in tenant.
    'tool/mutenancy:switch' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Allocate global users as tenants members and deallocate members.
    'tool/mutenancy:allocate' => [
        'captype' => 'write',
        'riskbitmask' => RISK_DATALOSS,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Create new user account for tenant member.
    'tool/mutenancy:membercreate' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Update user account of tenant member.
    'tool/mutenancy:memberupdate' => [
        'captype' => 'write',
        'riskbitmask' => RISK_DATALOSS | RISK_PERSONAL,
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Delete user account of tenant member.
    'tool/mutenancy:memberdelete' => [
        'captype' => 'write',
        'riskbitmask' => RISK_DATALOSS,
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Change tenant authentication settings.
    'tool/mutenancy:configauth' => [
        'captype' => 'write',
        'riskbitmask' => RISK_DATALOSS,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    // Change theme and tenant branding.
    'tool/mutenancy:configappearance' => [
        'captype' => 'write',
        'riskbitmask' => RISK_SPAM,
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
