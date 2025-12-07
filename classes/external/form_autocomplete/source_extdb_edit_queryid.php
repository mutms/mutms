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

namespace tool_muprog\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\sql;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;

/**
 * External database sync query autocompletion.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_extdb_edit_queryid extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null override with name of item database table */
    protected const ITEM_TABLE = 'tool_mulib_extdb_query';
    /** @var string|null override with name of item field used for label */
    protected const ITEM_FIELD = 'name';

    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'programid' => new external_value(PARAM_INT, 'Program id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available queries.
     *
     * @param string $query The search request.
     * @param int $programid
     * @return array
     */
    public static function execute(string $query, int $programid): array {
        global $DB, $USER;

        [
            'query' => $query,
            'programid' => $programid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'programid' => $programid,
        ]);

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:edit', $context);

        $contextids = $context->get_parent_context_ids(true);
        $contextids = implode(',', $contextids);

        $sql = (
            new sql(
                "SELECT q.id, q.name, q.contextid
                   FROM {tool_mulib_extdb_query} q
                   /* capsubquery */
                   /* tenantjoin */
                  WHERE q.contextid IN ($contextids) /* search */
               ORDER BY q.name ASC"
            )
        )
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    'tool/mulib:useextdb',
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [\context_system::LEVEL, \context_coursecat::LEVEL])
                )->wrap("JOIN (", ")capctx ON capctx.id = q.contextid")
            )
            ->replace_comment(
                'search',
                self::get_search_query($query, ['name', 'note'], 'q')->wrap('AND ', '')
            );

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantjoin',
                    new sql("JOIN {context} tctx ON tctx.id = q.contextid AND (tctx.tenantid = ? OR tctx.tenantid IS NULL)", [$context->tenantid])
                );
            }
        }

        $queries = $DB->get_records_sql($sql->sql, $sql->params);
        return self::prepare_result($queries, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;
        if (!$value) {
            // No value means sync is disabled.
            return null;
        }
        $programid = $args['programid'];
        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $query = $DB->get_record('tool_mulib_extdb_query', ['id' => $value]);
        if (!$query) {
            return get_string('error');
        }
        $qcontext = \context::instance_by_id($query->contextid, IGNORE_MISSING);
        if (!$qcontext) {
            return get_string('error');
        }
        if (mulib::is_mutenancy_active()) {
            $programcontext = \context::instance_by_id($program->contextid);
            if ($qcontext->tenantid && $programcontext->tenantid && $qcontext->tenantid != $programcontext->tenantid) {
                // Do not allow queries from other tenants.
                return get_string('error');
            }
        }
        $source = $DB->get_record('tool_muprog_source', ['programid' => $programid, 'type' => 'extdb']);
        if ($source && $source->auxint1 == $value) {
            // Current value is ok, unless it breaks tenant separation rules.
            return null;
        }
        if (!has_capability('tool/mulib:useextdb', $qcontext)) {
            return get_string('error');
        }
        return null;
    }
}
