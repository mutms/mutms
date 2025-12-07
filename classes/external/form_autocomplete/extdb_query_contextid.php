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

namespace tool_mulib\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\sql;

/**
 * Category context selection autocompletion for external database queries.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class extdb_query_contextid extends categorycontext {
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

    #[\Override]
    public static function get_noselectionstring(): string {
        return get_string('coresystem');
    }

    /**
     * Gets list of available category contexts.
     *
     * @param string $query The search request.
     * @return array
     */
    public static function execute(string $query): array {
        global $DB;

        ['query' => $query] = self::validate_parameters(self::execute_parameters(), ['query' => $query]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $sql = new sql(
            "SELECT ctx.id, cat.name
               FROM {course_categories} cat
               JOIN {context} ctx ON ctx.instanceid = cat.id
              WHERE ctx.contextlevel = :catlevel /* search */ /* tenant */
           ORDER BY cat.name ASC",
            ['catlevel' => CONTEXT_COURSECAT]
        );
        $sql = $sql->replace_comment(
            'search',
            self::get_categorycontext_search_query($query, 'cat')->wrap('AND ', '')
        );

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $sql = $sql->replace_comment(
                    'tenant',
                    "AND (ctx.tenantid IS NULL OR ctx.tenantid = ?)",
                    [$tenantid]
                );
            }
        }

        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);

        $categories = [];
        $i = 0;
        foreach ($rs as $category) {
            $categories[$category->id] = $category;
            $i++;
            if ($i > self::MAX_RESULTS) {
                break;
            }
        }
        $rs->close();

        return self::prepare_result($categories, $context);
    }

    #[\Override]
    public static function validate_value(mixed $value, array $args, \context $context): ?string {
        global $DB;
        $syscontext = \context_system::instance();
        if (!$value || $value == $syscontext->id) {
            return null;
        }

        $context = \context::instance_by_id($value, IGNORE_MISSING);
        if (!$context || $context->contextlevel != CONTEXT_COURSECAT) {
            return get_string('error');
        }

        $category = $DB->get_record('course_categories', ['id' => $context->instanceid]);
        if (!$category) {
            return get_string('error');
        }

        return null;
    }
}
