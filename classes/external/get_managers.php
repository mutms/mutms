<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This tenant is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This tenant is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this tenant.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mutenancy\external;

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_api;
use core_external\external_single_structure;
use core\exception\invalid_parameter_exception;
use core_external\external_multiple_structure;

/**
 * List tenant managers.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_managers extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tenantid' => new external_value(PARAM_INT, 'tenant id'),
        ]);
    }

    /**
     * Get tenant managers.
     *
     * @param int $tenantid
     * @return array
     */
    public static function execute(int $tenantid): array {
        global $DB;

        ['tenantid' => $tenantid] = self::validate_parameters(
            self::execute_parameters(),
            ['tenantid' => $tenantid]
        );

        if (!\tool_mutenancy\local\tenancy::is_active()) {
            throw new invalid_parameter_exception('multitenancy is not active');
        }

        $context = \context_tenant::instance($tenantid);
        self::validate_context($context);
        require_capability('tool/mutenancy:view', $context);

        $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.tenantid
                  FROM {user} u
                  JOIN {tool_mutenancy_manager} tm ON tm.userid = u.id
                 WHERE u.deleted = 0 AND tm.tenantid = :tenantid
              ORDER BY u.id ASC";
        $params = ['tenantid' => $tenantid];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(\core\user::get_property_type('id'), 'user ID of the manager'),
                'username' => new external_value(\core\user::get_property_type('username'), 'username'),
                'firstname' => new external_value(\core\user::get_property_type('firstname'), 'first name'),
                'lastname' => new external_value(\core\user::get_property_type('lastname'), 'family name'),
                'email' => new external_value(\core\user::get_property_type('email'), 'email address'),
                'tenantid' => new external_value(PARAM_INT, 'tenantid if manager is a member of the tenant'),
            ], 'List of tenant managers')
        );
    }
}
