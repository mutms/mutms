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
 * Provides list of programs from which the user can import content.
 *
 * @package     tool_muprog
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_allocation_import_fromprogram extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null program table */
    protected const ITEM_TABLE = 'tool_muprog_program';
    /** @var string|null field used for item name */
    protected const ITEM_FIELD = 'fullname';

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
     * Gets list of available programs.
     *
     * @param string $query The search request.
     * @param int $programid The Program to which the program has to be imported, we will exclude this program.
     * @return array
     */
    public static function execute(string $query, int $programid): array {
        global $DB;

        [
            'query' => $query,
            'programid' => $programid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'query' => $query,
                'programid' => $programid,
            ]
        );

        $targetprogram = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $context = \context::instance_by_id($targetprogram->contextid);

        self::validate_context($context);
        require_capability('tool/muprog:edit', $context);

        [$searchsql, $params] = \tool_muprog\local\management::get_program_search_query(null, $query, 'p');
        $params['programid'] = $programid;

        $tenantselect = '';
        if (\tool_muprog\local\util::is_mutenancy_active()) {
            $targetprogramtenantid = $DB->get_field('context', 'tenantid', ['id' => $context->id]);
            if ($targetprogramtenantid) {
                $tenantselect = "AND (c.tenantid = :tenantid OR c.tenantid IS NULL)";
                $params['tenantid'] = $targetprogramtenantid;
            }
        }

        $sql = "SELECT p.id, p.fullname, p.contextid
                  FROM {tool_muprog_program} p
                  JOIN {context} c ON c.id = p.contextid
                 WHERE p.id <> :programid AND $searchsql
                       $tenantselect
              ORDER BY p.fullname ASC";
        $rs = $DB->get_recordset_sql($sql, $params);

        $programs = [];
        $count = 0;
        foreach ($rs as $program) {
            $pcontext = \context::instance_by_id($program->contextid);
            if (!has_capability('tool/muprog:clone', $pcontext)) {
                continue;
            }
            $programs[] = $program;
            $count++;
            if ($count > self::MAX_RESULTS) {
                break;
            }
        }
        $rs->close();

        return self::prepare_result($programs, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $value]);
        if (!$program) {
            return get_string('error');
        }
        $context = \context::instance_by_id($program->contextid);
        if (!has_capability('tool/muprog:clone', $context)) {
            return get_string('error');
        }

        return null;
    }
}
