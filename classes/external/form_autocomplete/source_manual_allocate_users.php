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
 * Provides list of candidates for program allocation.
 *
 * @package     tool_muprog
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_allocate_users extends \tool_mulib\external\form_autocomplete\user {
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
     * Finds users with the identity matching the given query.
     *
     * @param string $query The search request.
     * @param int $programid The Program.
     * @return array
     */
    public static function execute(string $query, int $programid): array {
        global $DB;

        ['query' => $query, 'programid' => $programid] = self::validate_parameters(
            self::execute_parameters(),
            ['query' => $query, 'programid' => $programid]
        );

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:allocate', $context);

        $fields = \core_user\fields::for_name()->with_identity($context, false);
        $extrafields = $fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);

        [$searchsql, $searchparams] = users_search_sql($query, 'usr', true, $extrafields);
        [$sortsql, $sortparams] = users_order_by_sql('usr', $query, $context);
        $params = array_merge($searchparams, $sortparams);
        $params['programid'] = $programid;

        $tenantwhere = self::get_tenant_related_users_where('usr.id', $context);

        $sql = <<<SQL
            SELECT usr.*
              FROM {user} usr
         LEFT JOIN {tool_muprog_allocation} pa ON (pa.userid = usr.id AND pa.programid = :programid)
             WHERE pa.id IS NULL AND {$searchsql} {$tenantwhere}
                   AND usr.deleted = 0 AND usr.confirmed = 1
          ORDER BY {$sortsql}
SQL;

        $users = $DB->get_records_sql($sql, $params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($users, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        if (!$value) {
            return null;
        }

        $user = $DB->get_record('user', ['id' => $value, 'deleted' => 0, 'confirmed' => 1]);
        if (!$user) {
            return get_string('error');
        }

        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $args['programid'], 'userid' => $user->id])) {
            return get_string('error');
        }

        $error = self::validate_tenant_relation($user, $context);
        if ($error !== null) {
            return $error;
        }

        return null;
    }
}
