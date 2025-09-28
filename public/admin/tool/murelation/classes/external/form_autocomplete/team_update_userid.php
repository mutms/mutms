<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This framework is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This framework is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this framework.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_murelation\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_murelation\local\uimode_teams;
use tool_mulib\local\sql;

/**
 * Provides list of candidates for supervisor position when updating team.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class team_update_userid extends \tool_mulib\external\form_autocomplete\user {
    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'supervisorid' => new external_value(PARAM_INT, 'Supervisor position id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds users with the identity matching the given query.
     *
     * @param string $query The search request
     * @param int $supervisorid Supervisor id
     * @return array
     */
    public static function execute(string $query, int $supervisorid): array {
        global $DB, $CFG;

        [
            'query' => $query,
            'supervisorid' => $supervisorid,
        ]
            = self::validate_parameters(
                self::execute_parameters(),
                [
                    'query' => $query,
                    'supervisorid' => $supervisorid,
                ]
            );

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);

        $context = uimode_teams::get_team_context($framework, $supervisor);

        // Validate context.
        self::validate_context($context);

        if (!uimode_teams::can_update_team($framework, $supervisor)) {
            throw new \core\exception\invalid_parameter_exception('Cannot update team');
        }

        $sql = new sql(
            "SELECT usr.*
               FROM {user} usr
               /* cohortjoin */
              WHERE usr.deleted = 0 AND usr.confirmed = 1
                    /* search */ /* tenant */
            /* orderby */"
        );
        if ($framework->supervisorcohortid) {
            $sql->replace_comment(
                'cohortjoin',
                new sql("JOIN {cohort_members} cm ON cm.userid = usr.id AND cm.cohortid = ?", [$framework->supervisorcohortid])
            );
        }
        $sql->replace_comment(
            'search',
            self::get_user_search_query($query, 'usr', $context)->wrap('AND ', '')
        );
        $sql->replace_comment(
            'tenant',
            self::get_tenant_related_users_where('usr.id', $context, 'AND')
        );
        $sql->replace_comment(
            'orderby',
            self::get_user_search_orderby($query, 'usr', $context)->wrap('ORDER BY ', '')
        );

        $users = $DB->get_records_sql($sql->sql, $sql->params, 0, $CFG->maxusersperpage + 1);

        return self::prepare_result($users, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        if (!$value) {
            return null;
        }

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $args['supervisorid']], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);

        $user = $DB->get_record('user', ['id' => $value, 'deleted' => 0, 'confirmed' => 1]);
        if (!$user) {
            return get_string('error');
        }

        if ($supervisor->userid == $user->id) {
            return null;
        }

        $error = self::validate_tenant_relation($user, $context);
        if ($error !== null) {
            return $error;
        }

        if ($framework->supervisorcohortid) {
            if (!$DB->record_exists('cohort_members', ['cohortid' => $framework->supervisorcohortid, 'userid' => $user->id])) {
                return get_string('error');
            }
        }

        return null;
    }
}
