<?php
// This file is part of Multi-tenancy patch for Moodle™.
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

use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\config;

/**
 * Helper functions for multi-tenancy patch.
 *
 * @package     core
 * @copyright   2025 Petr Skoda
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Is multi-tenancy available and active?
 *
 * NOTE: for code that is not part of standard Moodle™ distribution use following instead:
 *
 *       if (component_class_callback(\tool_mutenancy\local\tenancy::class, 'is_active', [])) {
 *
 * @return bool
 */
function mutenancy_is_active(): bool {
    if (!class_exists(tenancy::class)) {
        return false;
    }

    return tenancy::is_active();
}

/**
 * Replacement of get_config() and direct use of $CFG->xyz
 * for settings with tenant overrides.
 *
 * @param string $plugin
 * @param string|null $name null means return all configs as object properties
 * @return mixed the same format as get_config()
 */
function mutenancy_get_config(string $plugin, ?string $name = null) {
    global $CFG;

    if (!mutenancy_is_active() || !tenancy::get_current_tenantid()) {
        if ($plugin === 'moodle' || $plugin === 'core' || empty($plugin)) {
            $plugin = 'core';
        }
        if ($plugin === 'core' && !empty($name)) {
            if (!isset($CFG->config_php_settings[$name]) && property_exists($CFG, $name)) {
                // It is common bad practice that devs just override CFG directly in tests, oh well...
                return $CFG->$name;
            }
        }

        return get_config($plugin, $name);
    }

    return config::get(-1, $plugin, $name);
}

/**
 * Members of archived tenant are to be treated as suspended users.
 *
 * @param int|stdClass $userorid
 * @return bool
 */
function mutenancy_is_user_archived($userorid): bool {
    if (!mutenancy_is_active()) {
        return false;
    }

    if (is_object($userorid)) {
        if (property_exists($userorid, 'tenantid')) {
            $tenantid = $userorid->tenantid;
        } else {
            $tenantid = \tool_mutenancy\local\tenancy::get_user_tenantid($userorid->id);
        }
    } else {
        $tenantid = \tool_mutenancy\local\tenancy::get_user_tenantid($userorid);
    }

    if ($tenantid) {
        $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
        if ($tenant && $tenant->archived) {
            return true;
        }
    }

    return false;
}
