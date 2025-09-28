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
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'query' => $query,
            ]
        );

        util::require_admin();

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        $fields = \core_user\fields::for_name()->with_identity($syscontext, false);
        $extrafields = $fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);

        [$searchsql, $searchparams] = users_search_sql($query, 'usr', true, $extrafields);
        [$sortsql, $sortparams] = users_order_by_sql('usr', $query, $syscontext);
        $params = array_merge($searchparams, $sortparams);

        $admins = explode(',', $CFG->siteadmins);
        $admins = array_map('intval', $admins);
        $admins = implode(',', $admins);

        $sqlquery = <<<SQL
            SELECT usr.*
              FROM {user} usr
         LEFT JOIN {tool_musudo_sudoer} su ON su.userid = usr.id
             WHERE {$searchsql}
                   AND su.id IS NULL AND usr.deleted = 0 AND usr.confirmed = 1
                   AND usr.id NOT IN ($admins)
          ORDER BY {$sortsql}
SQL;

        $users = $DB->get_records_sql($sqlquery, $params, 0, $CFG->maxusersperpage + 1);

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
