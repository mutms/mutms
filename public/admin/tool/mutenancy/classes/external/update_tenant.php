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

/**
 * Update tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_tenant extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tenant' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'id'),
                'name' => new external_value(PARAM_TEXT, 'mame', VALUE_OPTIONAL),
                'idnumber' => new external_value(PARAM_RAW, 'idnumber', VALUE_OPTIONAL),
                'loginshow' => new external_value(PARAM_BOOL, 'show on login page', VALUE_OPTIONAL),
                'memberlimit' => new external_value(PARAM_INT, 'member limit', VALUE_OPTIONAL),
                'sitefullname' => new external_value(PARAM_TEXT, 'long site name name', VALUE_OPTIONAL),
                'siteshortname' => new external_value(PARAM_TEXT, 'short site name', VALUE_OPTIONAL),
                'categoryname' => new external_value(PARAM_TEXT, 'tenant category name', VALUE_OPTIONAL),
                'categoryidnumber' => new external_value(PARAM_RAW, 'tenant category idnumber', VALUE_OPTIONAL),
                'cohortname' => new external_value(PARAM_TEXT, 'tenant cohort name', VALUE_OPTIONAL),
                'cohortidnumber' => new external_value(PARAM_RAW, 'tenant cohort idnumber', VALUE_OPTIONAL),
            ]),
        ]);
    }

    /**
     * Update tenant.
     *
     * @param array $tenant
     * @return array
     */
    public static function execute(array $tenant): array {
        ['tenant' => $tenant] = self::validate_parameters(
            self::execute_parameters(),
            ['tenant' => $tenant]
        );

        if (!\tool_mutenancy\local\tenancy::is_active()) {
            throw new invalid_parameter_exception('multitenancy is not active');
        }

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);
        require_capability('tool/mutenancy:admin', $syscontext);

        foreach ($tenant as $k => $v) {
            if ($v === null) {
                unset($tenant->v);
            }
        }

        return (array)\tool_mutenancy\local\tenant::update((object)$tenant);
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'tenant id'),
                'name' => new external_value(PARAM_RAW, 'mame'),
                'idnumber' => new external_value(PARAM_RAW, 'idnumber'),
                'loginshow' => new external_value(PARAM_BOOL, 'show on login page'),
                'memberlimit' => new external_value(PARAM_INT, 'member limit'),
                'categoryid' => new external_value(PARAM_INT, 'tenant category id'),
                'cohortid' => new external_value(PARAM_INT, 'tenant cohort id'),
                'assoccohortid' => new external_value(PARAM_INT, 'associated users cohort id'),
                'sitefullname' => new external_value(PARAM_RAW, 'long site name name'),
                'siteshortname' => new external_value(PARAM_RAW, 'short site name'),
                'archived' => new external_value(PARAM_BOOL, 'archived flag'),
                'timecreated' => new external_value(PARAM_INT, 'creation date'),
                'timemodified' => new external_value(PARAM_INT, 'modification date'),
            ],
            'Updated tenant'
        );
    }
}
