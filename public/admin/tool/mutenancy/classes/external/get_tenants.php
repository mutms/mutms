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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core\exception\invalid_parameter_exception;

/**
 * Returns tenants.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_tenants extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fieldvalues' => new external_multiple_structure(
                new external_single_structure([
                    'field' => new external_value(
                        PARAM_ALPHANUM,
                        'The name of the field to be searched by list of acceptable fields are: id, name, idnumber, archived'
                    ),
                    'value' => new external_value(
                        PARAM_RAW,
                        'Value of the field to be searched'
                    ),
                ]),
                'tenant search parameters'
            ),
        ]);
    }

    /**
     * Get list of tenants.
     *
     * @param array $fieldvalues Key value pairs.
     * @return array
     */
    public static function execute(array $fieldvalues): array {
        global $DB;
        ['fieldvalues' => $fieldvalues] = self::validate_parameters(
            self::execute_parameters(),
            ['fieldvalues' => $fieldvalues]
        );

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);
        require_capability('tool/mutenancy:view', $syscontext);

        if (!\tool_mutenancy\local\tenancy::is_active()) {
            return [];
        }

        $allowedfieldlist = ['id', 'name', 'idnumber', 'archived'];
        $conditions = [];
        foreach ($fieldvalues as $fieldvalue) {
            ['field' => $field, 'value' => $value] = $fieldvalue;
            if (!in_array($field, $allowedfieldlist, true)) {
                throw new invalid_parameter_exception('Invalid field name: ' . $field);
            }
            $conditions[$field] = $value;
        }
        return $DB->get_records('tool_mutenancy_tenant', $conditions, 'id ASC');
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
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
            ], 'List of tenants')
        );
    }
}
