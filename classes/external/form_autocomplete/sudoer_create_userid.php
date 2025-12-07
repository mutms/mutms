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

namespace tool_musudo\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_musudo\local\util;
use tool_mulib\local\sql;

/**
 * Provides list of candidates for sudo users.
 *
 * @package     tool_musudo
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer_create_userid extends \tool_mulib\external\form_autocomplete\user {
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
     * Finds users with the identity matching the given query.
     *
     * @param string $query The search request
     * @return array
     */
    public static function execute(string $query): array {
        global $DB, $CFG;

        [
            'query' => $query,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
        ]);

        util::require_admin();

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        $admins = explode(',', $CFG->siteadmins);
        $admins = array_map('intval', $admins);
        $admins = implode(',', $admins);

        $sql = (
            new sql(
                "SELECT u.*
                  FROM {user} u
             LEFT JOIN {tool_musudo_sudoer} su ON su.userid = u.id
                 WHERE su.id IS NULL AND u.deleted = 0 AND u.confirmed = 1
                       AND u.id NOT IN ($admins) /* searchsql */
              /* orderby */"
            )
        )
            ->replace_comment(
                'searchsql',
                self::get_user_search_query($query, 'u', $syscontext)->wrap('AND ', '')
            )
            ->replace_comment(
                'orderby',
                self::get_user_search_orderby($query, 'u', $syscontext)->wrap('ORDER BY ', '')
            );

        $users = $DB->get_records_sql($sql->sql, $sql->params, 0, $CFG->maxusersperpage + 1);
        return self::prepare_result($users, $syscontext);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $user = $DB->get_record('user', ['id' => $value]);
        if (!$user || $user->deleted || !$user->confirmed) {
            return get_string('error');
        }

        if ($DB->record_exists('tool_musudo_sudoer', ['userid' => $user->id])) {
            return get_string('error');
        }

        if (is_siteadmin($user->id)) {
            return get_string('error');
        }

        return null;
    }
}
