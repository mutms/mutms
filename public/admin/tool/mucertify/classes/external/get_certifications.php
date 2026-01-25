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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mucertify\external;

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use tool_mulib\local\mulib;
use core\exception\invalid_parameter_exception;

/**
 * Provides list of certifications based on search parameters.
 *
 * @package    tool_mucertify
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_certifications extends external_api {
    /** @var string[] */
    public const SEARCH_FIELDS = ['id', 'contextid', 'fullname', 'idnumber', 'publicaccess', 'archived', 'tenantid'];

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fieldvalues' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'field' => new external_value(PARAM_ALPHANUM, 'The name of the field to be searched by list of'
                            . ' acceptable fields is : id, contextid, fullname, idnumber, publicaccess, archived, tenantid'),
                        'value' => new external_value(PARAM_RAW, 'Value of the field to be searched, NULL allowed only for tenantid'),
                    ]
                ),
                'Certification search parameters'
            ),
        ]);
    }

    /**
     * Returns list of certifications matching the given query.
     *
     * @param array $fieldvalues Key value pairs.
     * @return array
     */
    public static function execute(array $fieldvalues): array {
        global $DB;
        [
            'fieldvalues' => $fieldvalues,
        ] = self::validate_parameters(self::execute_parameters(), [
            'fieldvalues' => $fieldvalues,
        ]);

        $params = [];
        $where = [];
        $tenantjoin = '';
        foreach ($fieldvalues as $fieldvalue) {
            ['field' => $field, 'value' => $value] = $fieldvalue;
            if (!in_array($field, self::SEARCH_FIELDS, true)) {
                throw new invalid_parameter_exception('Invalid field name: ' . $field);
            }
            if (array_key_exists($field, $params)) {
                throw new invalid_parameter_exception('Invalid duplicate field name: ' . $field);
            }
            if ($field === 'tenantid') {
                if (!mulib::is_mutenancy_active()) {
                    throw new invalid_parameter_exception('Invalid field name: ' . $field);
                }
                if (!$value) {
                    $tenantjoin = "JOIN {context} c ON c.id = ct.contextid AND c.tenantid IS NULL";
                } else {
                    $tenantjoin = "JOIN {context} c ON c.id = ct.contextid AND c.tenantid = :tenantid";
                }
            } else {
                if ($value === null) {
                    throw new invalid_parameter_exception('Field value cannot be NULL: ' . $field);
                }
                $where[] = "ct.$field = :$field";
            }
            $params[$field] = $value;
        }
        if ($where) {
            $where = 'WHERE ' . implode(' AND ', $where);
        } else {
            $where = '';
        }
        $sql = "SELECT ct.*
                  FROM {tool_mucertify_certification} ct
           $tenantjoin
                $where
              ORDER BY ct.id ASC";
        $certifications = $DB->get_records_sql($sql, $params);

        $validated = [];

        $results = [];
        foreach ($certifications as $certification) {
            if (!isset($validated[$certification->contextid])) {
                $context = \context::instance_by_id($certification->contextid);
                if (!has_capability('tool/mucertify:view', $context)) {
                    continue;
                }
                self::validate_context($context);
                $validated[$certification->contextid] = true;
            }
            $sources = $DB->get_records_menu(
                'tool_mucertify_source',
                ['certificationid' => $certification->id],
                'type ASC',
                'type'
            );
            $certification->sources = array_keys($sources);
            if ($certification->publicaccess) {
                $certification->cohortids = [];
            } else {
                $cohorts = $DB->get_records_menu(
                    'tool_mucertify_cohort',
                    ['certificationid' => $certification->id],
                    'cohortid ASC',
                    'cohortid'
                );
                $certification->cohortids = array_keys($cohorts);
            }
            $results[] = $certification;
        }

        return $results;
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Certification id'),
                'contextid' => new external_value(PARAM_INT, 'Certification context id'),
                'fullname' => new external_value(PARAM_TEXT, 'Certification full name'),
                'idnumber' => new external_value(PARAM_RAW, 'Certification ID'),
                'description' => new external_value(PARAM_RAW, 'Certification description text (in original text format)'),
                'descriptionformat' => new external_value(PARAM_INT, 'Certification description text format'),
                'publicaccess' => new external_value(PARAM_BOOL, 'Public flag'),
                'archived' => new external_value(PARAM_BOOL, 'Archived flag (archived certifications should not change)'),
                'programid1' => new external_value(PARAM_INT, 'First program id'),
                'programid2' => new external_value(PARAM_INT, 'Re-certification program id'),
                'templateid' => new external_value(PARAM_INT, 'Certificate template id'),
                'recertify' => new external_value(PARAM_INT, 'NULL means no automatic recertification, number is seconds before the end of last period when window opens'),
                'periodsjson' => new external_value(PARAM_RAW, 'Period defaults'),
                'timecreated' => new external_value(PARAM_INT, 'Certification creation date'),
                'sources' => new external_multiple_structure(
                    new external_value(PARAM_ALPHANUMEXT, 'Internal source name'),
                    'Enabled assignment sources'
                ),
                'cohortids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Cohort id'),
                    'Visible cohorts for non-public certifications'
                ),
            ], 'List of certifications')
        );
    }
}
