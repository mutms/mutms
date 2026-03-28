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

/**
 * Allowed tenants selection.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework_tenantids extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null tenant table */
    protected const ITEM_TABLE = 'tool_mutenancy_tenant';
    /** @var string|null field used for item name */
    protected const ITEM_FIELD = 'name';

    #[\Override]
    public static function get_multiple(): bool {
        return true;
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
        global $DB;

        [
            'query' => $query,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/murelation:manageframeworks', $context);

        if (!\tool_mulib\local\mulib::is_mutenancy_active()) {
            return self::prepare_result([], $context);
        }

        $sql = (
            new sql(
                "SELECT t.id, t.name
                   FROM {tool_mutenancy_tenant} t
                  WHERE t.archived = 0 /* search */
               ORDER BY t.name ASC"
            )
        )
            ->replace_comment(
                'search',
                self::get_search_query($query, ['name', 'idnumber'], 't')->wrap('AND ', '')
            );
        $tenants = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);

        return self::prepare_result($tenants, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $value]);
        if (!$tenant) {
            return get_string('error');
        }

        return null;
    }
}
