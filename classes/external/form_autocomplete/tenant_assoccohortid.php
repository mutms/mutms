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

namespace tool_mutenancy\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Associated users cohort autocompletion.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_assoccohortid extends \tool_mulib\external\form_autocomplete\cohort {
    /** @var string|null cohort table */
    protected const ITEM_TABLE = 'cohort';
    /** @var string|null field used for item name */
    protected const ITEM_FIELD = 'name';

    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'tenantid' => new external_value(PARAM_INT, 'Tenant id, 0 if creating new tenant', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available cohorts.
     *
     * @param string $query The search request.
     * @param int $tenantid
     * @return array
     */
    public static function execute(string $query, int $tenantid): array {
        global $DB;

        [
            'query' => $query,
            'tenantid' => $tenantid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'query' => $query,
                'tenantid' => $tenantid,
            ]
        );

        if ($tenantid) {
            $context = \context_tenant::instance($tenantid);
        } else {
            $context = \context_system::instance();
        }
        self::validate_context($context);
        require_capability('tool/mutenancy:admin', $context);

        [$searchsql, $params] = self::get_cohort_search_query($query, 'ch');
        if ($tenantid) {
            $params['tenantid'] = $tenantid;
            $ortenantid = "OR c.tenantid = :tenantid";
        } else {
            $ortenantid = "";
        }

        $sql = "SELECT ch.id, ch.name, ch.contextid, ch.visible
                  FROM {cohort} ch
                  JOIN {context} c ON c.id = ch.contextid AND (c.tenantid IS NULL $ortenantid)
             LEFT JOIN {tool_mutenancy_tenant} t ON t.cohortid = ch.id
                 WHERE t.id IS NULL AND $searchsql
              ORDER BY ch.name ASC";

        $cohorts = [];
        $rs = $DB->get_recordset_sql($sql, $params);
        $i = 0;
        foreach ($rs as $cohort) {
            if (!self::is_cohort_visible($cohort)) {
                continue;
            }
            $i++;
            if ($i > self::MAX_RESULTS) {
                $rs->close();
                return self::get_overflow_result();
            }
            $cohorts[$cohort->id] = $cohort;
        }
        $rs->close();

        return self::get_list_result($cohorts, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;
        $cohort = $DB->get_record('cohort', ['id' => $value]);
        if (!$cohort) {
            return get_string('error');
        }
        $context = \context::instance_by_id($cohort->contextid, IGNORE_MISSING);
        if (!$context) {
            return get_string('error');
        }

        $tenantid = $args['tenantid'];

        if ($tenantid) {
            $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid]);
            if (!$tenant) {
                return get_string('error');
            }
            if ($tenant->assoccohortid == $cohort->id) {
                // Allow whatever existing cohort is there.
                return null;
            }
            if ($context->tenantid && $context->tenantid != $tenantid) {
                // Do not allow cohorts from other tenants.
                return get_string('error');
            }
        } else {
            if ($context->tenantid) {
                // Do not allow cohorts from other tenants.
                return get_string('error');
            }
        }

        if ($DB->record_exists('tool_mutenancy_tenant', ['cohortid' => $cohort->id])) {
            // Do not allow tenant member cohorts.
            return get_string('error');
        }

        if (!self::is_cohort_visible($cohort)) {
            return get_string('error');
        }

        return null;
    }
}
