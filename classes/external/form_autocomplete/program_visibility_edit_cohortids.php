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

namespace tool_muprog\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Program visibility cohorts autocompletion.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_visibility_edit_cohortids extends \tool_mulib\external\form_autocomplete\cohort {
    #[\Override]
    public static function get_multiple(): bool {
        return true;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'programid' => new external_value(PARAM_INT, 'Program id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available cohorts.
     *
     * @param string $query The search request.
     * @param int $programid
     * @return array
     */
    public static function execute(string $query, int $programid): array {
        global $DB;

        [
            'query' => $query,
            'programid' => $programid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'query' => $query,
                'programid' => $programid,
            ]
        );

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:edit', $context);

        [$searchsql, $params] = self::get_cohort_search_query($query, 'ch');
        $tenantselect = "";
        if (\tool_muprog\local\util::is_mutenancy_active()) {
            if ($context->tenantid) {
                $tenantselect = "AND (c.tenantid IS NULL OR c.tenantid = :tenantid)";
                $params['tenantid'] = $context->tenantid;
            }
        }

        $sql = "SELECT ch.id, ch.name, ch.contextid, ch.visible
                  FROM {cohort} ch
                  JOIN {context} c ON c.id = ch.contextid
                 WHERE $searchsql $tenantselect
              ORDER BY ch.name ASC";
        $rs = $DB->get_recordset_sql($sql, $params);

        $cohorts = [];
        $i = 0;
        foreach ($rs as $cohort) {
            if (!self::is_cohort_visible($cohort)) {
                continue;
            }
            $cohorts[$cohort->id] = $cohort;
            $i++;
            if ($i > self::MAX_RESULTS) {
                break;
            }
        }
        $rs->close();

        return self::prepare_result($cohorts, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;
        $programid = $args['programid'];
        $cohort = $DB->get_record('cohort', ['id' => $value]);
        if (!$cohort) {
            return get_string('error');
        }
        $context = \context::instance_by_id($cohort->contextid, IGNORE_MISSING);
        if (!$context) {
            return get_string('error');
        }
        if ($DB->record_exists('tool_muprog_cohort', ['cohortid' => $cohort->id, 'programid' => $programid])) {
            // Existing cohorts are always fine.
            return null;
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        if (\tool_muprog\local\util::is_mutenancy_active()) {
            $programcontext = \context::instance_by_id($program->contextid);
            if ($context->tenantid && $programcontext->tenantid && $context->tenantid != $programcontext->tenantid) {
                // Do not allow cohorts from other tenants.
                return get_string('error');
            }
        }
        if (!self::is_cohort_visible($cohort)) {
            return get_string('error');
        }
        return null;
    }
}
