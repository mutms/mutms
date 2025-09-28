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

namespace tool_mucertify\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Provides list of program candidates for certification.
 *
 * @package     tool_mucertify
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification_periods_programid extends \tool_mulib\external\form_autocomplete\base {
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
            'certificationid' => new external_value(PARAM_INT, 'certification id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds candidate programs for given certification.
     *
     * @param string $query The search request.
     * @param int $certificationid The certification.
     * @return array
     */
    public static function execute(string $query, int $certificationid): array {
        global $DB;

        [
            'query' => $query,
            'certificationid' => $certificationid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'query' => $query,
                'certificationid' => $certificationid,
            ]
        );

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($certification->contextid);
        self::validate_context($context);
        require_capability('tool/mucertify:edit', $context);

        [$searchsql, $params] = \tool_muprog\local\management::get_program_search_query(null, $query, 'p');

        $tenantselect = '';
        if (\tool_mucertify\local\util::is_mutenancy_active()) {
            $certificationtenantid = $DB->get_field('context', 'tenantid', ['id' => $context->id]);
            if ($certificationtenantid) {
                $tenantselect = "AND (ctx.tenantid = :tenantid OR ctx.tenantid IS NULL)";
                $params['tenantid'] = $certificationtenantid;
            }
        }

        $sqlquery = <<<SQL
            SELECT p.*
              FROM {tool_muprog_program} p
              JOIN {tool_muprog_source} s ON s.programid = p.id and s.type = 'mucertify'
              JOIN {context} ctx ON ctx.id = p.contextid
             WHERE p.archived = 0 AND $searchsql
                   $tenantselect
          ORDER BY p.fullname ASC
SQL;

        $rs = $DB->get_recordset_sql($sqlquery, $params);

        $count = 0;
        $programs = [];

        foreach ($rs as $program) {
            $pcontext = \context::instance_by_id($program->contextid);
            if (!has_capability('tool/muprog:addtocertifications', $pcontext)) {
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

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $args['certificationid']], '*', MUST_EXIST);
        if ($program->id == $certification->programid1 || $program->id == $certification->programid2) {
            // Current value is always ok.
            return null;
        }

        return null;
    }
}
