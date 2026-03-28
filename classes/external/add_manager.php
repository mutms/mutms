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
 * Add tenant manager.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class add_manager extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tenantid' => new external_value(PARAM_INT, 'tenant id'),
            'userid' => new external_value(PARAM_INT, 'user id'),
        ]);
    }

    /**
     * Add tenant manager.
     *
     * @param int $tenantid
     * @param int $userid
     * @return bool
     */
    public static function execute(int $tenantid, int $userid): bool {
        global $DB;

        ['tenantid' => $tenantid, 'userid' => $userid] = self::validate_parameters(
            self::execute_parameters(),
            ['tenantid' => $tenantid, 'userid' => $userid]
        );

        if (!\tool_mutenancy\local\tenancy::is_active()) {
            throw new invalid_parameter_exception('multitenancy is not active');
        }

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);
        require_capability('tool/mutenancy:admin', $syscontext);

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid, 'archived' => 0], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);
        if ($user->tenantid && $user->tenantid != $tenant->id) {
            throw new invalid_parameter_exception('tenant manager cannot be a member of another tenant');
        }

        if ($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant->id, 'userid' => $user->id])) {
            return false;
        }

        \tool_mutenancy\local\manager::add($tenantid, $user->id);

        return true;
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'True if added, false if already a manager');
    }
}
