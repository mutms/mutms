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

namespace tool_murelation\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\sql;
use tool_mulib\local\context_map;
use tool_murelation\local\framework;
use tool_murelation\local\uimode_teams;
use tool_mulib\local\mulib;

/**
 * Cohort selection for adding of members to a teams.
 *
 * @package     tool_murelation
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class members_add_cohort_cohortid extends \tool_mulib\external\form_autocomplete\cohort {
    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'supervisorid' => new external_value(PARAM_INT, 'Team id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available cohorts.
     *
     * @param string $query The search request.
     * @param int $supervisorid team it
     * @return array
     */
    public static function execute(string $query, int $supervisorid): array {
        global $DB, $USER;

        [
            'query' => $query,
            'supervisorid' => $supervisorid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'supervisorid' => $supervisorid,
        ]);

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
        $context = uimode_teams::get_team_context($framework, $supervisor);

        self::validate_context($context);

        if (!uimode_teams::can_manage_members($framework, $supervisor)) {
            throw new \core\exception\invalid_parameter_exception('Cannot manage team members');
        }

        $sql = (new sql(
            "SELECT ch.id, ch.name
               FROM {cohort} ch
               JOIN {context} chcontext ON chcontext.id = ch.contextid
               /* capsubquery */
              /* capwhere */ /* searchsql */ /* tenantwhere */
           ORDER BY ch.name ASC"
        ))
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

        if (mulib::is_mutenancy_active()) {
            if ($supervisor->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantwhere',
                    "AND (chcontext.tenantid IS NULL OR chcontext.tenantid = ?)",
                    [$supervisor->tenantid]
                );
            } else {
                $sql = $sql->replace_comment('tenantwhere', "AND chcontext.tenantid IS NULL");
            }
        } else {
            $sql = $sql->replace_comment('tenantwhere', "");
        }

        $sql->ensure_no_comments();

        $cohorts = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($cohorts, $context);
    }

    /**
     * Returns candidates for new team members.
     *
     * @param int $supervisorid
     * @param int $cohortid
     * @return array user ids
     */
    public static function get_candidates(int $supervisorid, int $cohortid): array {
        global $DB;

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid, 'uimode' => framework::UIMODE_TEAMS], '*', MUST_EXIST);
        $context = uimode_teams::get_team_context($framework, $supervisor);

        $sql = (new sql(
            "SELECT u.id
               FROM {user} u
               JOIN {cohort_members} cm ON cm.userid = u.id AND cm.cohortid = :cohortid
               /* cohortjoin */
          LEFT JOIN {tool_murelation_subordinate} sub ON sub.userid = u.id AND sub.supervisorid = :supervisorid
              WHERE u.deleted = 0 AND u.confirmed = 1 AND sub.id IS NULL
                    /* tenantwhere */
           ORDER BY u.id ASC",
            ['supervisorid' => $supervisor->id, 'cohortid' => $cohortid]
        ));

        if (mulib::is_mutenancy_active()) {
            $tenantwhere = \tool_mutenancy\local\tenancy::get_related_users_exists('u.id', $context, 'AND');
            $sql = $sql->replace_comment('tenantwhere', $tenantwhere);
        } else {
            $sql = $sql->replace_comment('tenantwhere', "");
        }

        if ($framework->subordinatecohortid) {
            $sql = $sql->replace_comment(
                'cohortjoin',
                new sql("JOIN {cohort_members} scm ON scm.userid = u.id AND scm.cohortid = ?", [$framework->subordinatecohortid])
            );
        } else {
            $sql = $sql->replace_comment('cohortjoin', "");
        }

        $sql->ensure_no_comments();

        return $DB->get_fieldset_sql($sql->sql, $sql->params);
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
        if (!self::is_cohort_visible($cohort)) {
            return get_string('error');
        }

        $candidates = self::get_candidates($args['supervisorid'], $value);
        if (!$candidates) {
            return get_string('error_nosubordinates', 'tool_murelation');
        }

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $args['supervisorid']], '*', MUST_EXIST);

        if ($supervisor->maxsubordinates) {
            $current = $DB->count_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id]);
            if ($current + count($candidates) > $supervisor->maxsubordinates) {
                return get_string('error_maxsubordinates', 'tool_murelation');
            }
        }

        return null;
    }
}
