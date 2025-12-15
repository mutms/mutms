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
 * Provides list of candidates for adding frameworks to program.
 *
 * @package     tool_muprog
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_append_creditframeworkid extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null credits framework table */
    protected const ITEM_TABLE = 'tool_mutrain_framework';
    /** @var string|null field used for item name */
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
     * Finds candidates for adding credits frameworks to program.
     *
     * @param string $query The search request.
     * @param int $programid The framework.
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

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:edit', $context);

        if (!mulib::is_mutrain_available()) {
            throw new \core\exception\coding_exception('mutrain is not available');
        }

        $sql = (
            new sql(
                "SELECT f.id, f.name
                   FROM {tool_mutrain_framework} f
                   /* capsubquery */
                   /* tenantjoin */
                  WHERE f.archived = 0 /* capwhere */ /* searchsql */
               ORDER BY f.name ASC"
            )
        )
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    'tool/mutrain:viewframeworks',
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [\context_system::LEVEL, \context_coursecat::LEVEL])
                )->wrap("LEFT JOIN (", ")capctx ON capctx.id = f.contextid")
            )
            ->replace_comment(
                'capwhere',
                new sql("AND (f.publicaccess = 1 OR capctx.id IS NOT NULL)")
            )
            ->replace_comment(
                'searchsql',
                self::get_search_query($query, ['name', 'idnumber'], 'f')->wrap('AND ', '')
            );

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantjoin',
                    new sql("JOIN {context} tctx ON tctx.id = f.contextid AND (tctx.tenantid = ? OR tctx.tenantid IS NULL)", [$context->tenantid])
                );
            }
        }

        $frameworks = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS);
        return self::prepare_result($frameworks, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $value]);
        if (!$framework || $framework->archived) {
            return get_string('error');
        }

        if ($framework->publicaccess) {
            return null;
        }

        $context = \context::instance_by_id($framework->contextid);
        if (!has_capability('tool/mutrain:viewframeworks', $context)) {
            return get_string('error');
        }
        return null;
    }
}
