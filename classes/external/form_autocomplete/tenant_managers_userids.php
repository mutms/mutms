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
 * Tenant managers assignment candidates.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_managers_userids extends \tool_mulib\external\form_autocomplete\user {
    #[\Override]
    public static function get_multiple(): bool {
        return true;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'tenantid' => new external_value(PARAM_INT, 'Tenant id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available tenant manager candidates.
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

        $context = \context_tenant::instance($tenantid);
        self::validate_context($context);
        require_capability('tool/mutenancy:admin', $context);

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);

        $fields = \core_user\fields::for_name()->with_identity($context, false);
        $extrafields = $fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);

        [$searchsql, $searchparams] = users_search_sql($query, 'u', true, $extrafields);
        [$sortsql, $sortparams] = users_order_by_sql('u', $query, $context);
        $params = array_merge($searchparams, $sortparams);
        $params['tenantid'] = $tenant->id;

        $sql = "SELECT u.*
                  FROM {user} u
                 WHERE {$searchsql}
                       AND u.deleted = 0 AND u.confirmed = 1
                       AND u.tenantid IS NULL OR u.tenantid = :tenantid
              ORDER BY {$sortsql}";

        $users = $DB->get_records_sql($sql, $params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($users, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $tenantid = $args['tenantid'];

        $user = $DB->get_record('user', ['id' => $value, 'deleted' => 0, 'confirmed' => 1]);
        if (!$user) {
            return get_string('error');
        }

        // Tenant manager can be only global user or same tenant member.
        if ($user->tenantid && $user->tenantid != $tenantid) {
            return get_string('error');
        }

        return null;
    }
}
