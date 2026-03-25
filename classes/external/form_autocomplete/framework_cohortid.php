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

namespace tool_murelation\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\sql;
use tool_mulib\local\context_map;

/**
 * Cohort selection autocompletion.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework_cohortid extends \tool_mulib\external\form_autocomplete\cohort {
    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available cohorts.
     *
     * @param string $query The search request.
     * @return array
     */
    public static function execute(string $query): array {
        global $DB, $USER;

        [
            'query' => $query,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/murelation:manageframeworks', $context);

        $sql = (
            new sql(
                "SELECT ch.id, ch.name
                   FROM {cohort} ch
                   /* capsubquery */
                  /* capwhere */ /* searchsql */
               ORDER BY ch.name ASC"
            )
        )
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    'moodle/cohort:view',
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [\context_system::LEVEL, \context_coursecat::LEVEL])
                )->wrap("LEFT JOIN (", ")capctx ON capctx.id = ch.contextid")
            )
            ->replace_comment(
                'capwhere',
                "WHERE (ch.visible = 1 OR capctx.id IS NOT NULL)"
            )
            ->replace_comment(
                'searchsql',
                self::get_cohort_search_query($query, 'ch')->wrap('AND ', '')
            );

        $cohorts = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($cohorts, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;
        $cohort = $DB->get_record('cohort', ['id' => $value]);
        if (!$cohort) {
            return get_string('error');
        }
        $cohortcontext = \context::instance_by_id($cohort->contextid, IGNORE_MISSING);
        if (!$cohortcontext) {
            return get_string('error');
        }

        if (isset($args['currentValue']) && $args['currentValue'] == $value) {
            return null;
        }

        if (!self::is_cohort_visible($cohort)) {
            return get_string('error');
        }

        return null;
    }
}
