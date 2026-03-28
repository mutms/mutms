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

namespace tool_mulib\external\form_autocomplete;

use tool_mulib\local\sql;
use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use stdClass;
use core_text;

/**
 * Base class for required category context (or system context) auto-completion fields.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class categorycontext extends base {
    /**
     * Returns required capability.
     * @return string
     */
    abstract public static function get_required_capability(): string;

    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function is_required(): bool {
        // Override to return true if element values required.
        return true;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available category contexts and system context.
     *
     * @param string $query The search request.
     * @return array
     */
    public static function execute(string $query): array {
        global $DB, $USER;

        ['query' => $query] = self::validate_parameters(self::execute_parameters(), ['query' => $query]);

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        $sql = new sql(
            "SELECT ctx.id, cat.name
               FROM {course_categories} cat
               JOIN {context} ctx ON ctx.instanceid = cat.id
               /* capjoin */
              WHERE ctx.contextlevel = :catlevel /* capwhere */
                    /* search */ /* tenant */
           GROUP BY ctx.id, cat.name
           ORDER BY cat.name ASC",
            ['catlevel' => CONTEXT_COURSECAT]
        );
        if (trim($query) !== '') {
            $sql = $sql->replace_comment(
                'search',
                static::get_search_query($query, ['name', 'idnumber', 'description'], 'cat')->wrap('AND ', '')
            );
        }
        $joins = context_map::get_contexts_by_capability_join(static::get_required_capability(), $USER->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));

        if (mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $sql = $sql->replace_comment(
                    'tenant',
                    "AND (ctx.tenantid IS NULL OR ctx.tenantid = ?)",
                    [$tenantid]
                );
            }
        }

        $categories = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        foreach ($categories as $category) {
            $categories[$category->id]->name = self::get_label($category->id, [], $syscontext);
        }
        \core_collator::asort_objects_by_property($categories, 'name');

        if (has_capability(static::get_required_capability(), $syscontext)) {
            $systemname = $syscontext->get_context_name(false);
            if (trim($query) === '' || str_contains(core_text::strtolower($systemname), core_text::strtolower($query))) {
                $sysoption = (object)['id' => $syscontext->id, 'name' => self::get_label($syscontext->id, [], $syscontext)];
                $categories = [$syscontext->id => $sysoption] + $categories;
            }
        }

        return self::prepare_result($categories, $syscontext);
    }

    #[\Override]
    public static function format_label(stdClass $item, \context $context): string {
        // Already formatted in execute().
        return $item->name;
    }

    #[\Override]
    public static function get_label(int $value, array $args, \context $context): string {
        if (!$value) {
            return get_string('invalidcontext', 'error');
        }

        $syscontext = \context_system::instance();
        if ($value == $syscontext->id) {
            return get_string('coresystem');
        }

        $valuecontext = \context::instance_by_id($value, IGNORE_MISSING);
        if (!$valuecontext) {
            return get_string('invalidcontext', 'error');
        }
        $contexts = $valuecontext->get_parent_contexts(true);
        $contexts = array_reverse($contexts);
        $result = [];
        foreach ($contexts as $c) {
            if ($c->id == $syscontext->id) {
                continue;
            }
            $result[] = $c->get_context_name(false);
        }
        return implode(' / ', $result);
    }

    #[\Override]
    public static function validate_value(mixed $value, array $args, \context $context): ?string {
        global $DB;

        if (!$value) {
            return get_string('required');
        }

        $syscontext = \context_system::instance();
        if ($value == $syscontext->id) {
            // Special case - system context id and empty value are allowed.
            if (!isset($args['currentValue']) || $args['currentValue'] != $value) {
                if (!has_capability(static::get_required_capability(), $syscontext)) {
                    return get_string('invalidcontext', 'error');
                }
            }
            return null;
        }

        $valuecontext = \context::instance_by_id($value, IGNORE_MISSING);
        if (!$valuecontext || $valuecontext->contextlevel != CONTEXT_COURSECAT) {
            return get_string('invalidcontext', 'error');
        }

        if (!isset($args['currentValue']) || $args['currentValue'] != $value) {
            if (!has_capability(static::get_required_capability(), $valuecontext)) {
                return get_string('invalidcontext', 'error');
            }
        }

        $category = $DB->get_record('course_categories', ['id' => $valuecontext->instanceid]);
        if (!$category) {
            return get_string('invalidcontext', 'error');
        }

        return null;
    }
}
