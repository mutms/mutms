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
 * Provides list of candidates for adding courses to program.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_create_course_courseids extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null course table */
    protected const ITEM_TABLE = 'course';
    /** @var string|null field used for item name */
    protected const ITEM_FIELD = 'fullname';

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
     * Finds candidates for adding courses to program.
     *
     * @param string $query The search request.
     * @param int $programid The program.
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

        $capjoin = context_map::get_contexts_by_capability_join('tool/muprog:addcourse', $USER->id, 'ctx');

        $sql = (
            new sql(
                "SELECT c.id, c.fullname
                   FROM {course} c
                   JOIN {context} ctx ON ctx.contextlevel = :courselevel AND ctx.instanceid = c.id
                   /* capjoin */
              LEFT JOIN {tool_muprog_item} pi ON pi.programid = :programid AND pi.courseid = c.id
                  WHERE c.category <> 0 AND pi.id IS NULL
                        /* capwhere */ /* searchsql */ /* tenantwhere */
               GROUP BY c.id, c.fullname
               ORDER BY c.fullname ASC",
                ['courselevel' => \context_course::LEVEL, 'programid' => $program->id]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                self::get_search_query($query, ['fullname', 'shortname', 'idnumber'], 'c')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql = $sql->replace_comment(
                    'tenantwhere',
                    new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$context->tenantid])
                );
            }
        }

        $courses = $DB->get_records_sql($sql->sql, $sql->params, 0, self::MAX_RESULTS + 1);
        return self::prepare_result($courses, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;

        $course = $DB->get_record('course', ['id' => $value]);
        if (!$course || !$course->category) {
            return get_string('error');
        }

        if ($DB->record_exists('tool_muprog_item', ['programid' => $args['programid'], 'courseid' => $course->id])) {
            return get_string('error');
        }

        $coursecontext = \context_course::instance($course->id);
        if (!has_capability('tool/muprog:addcourse', $coursecontext)) {
            return get_string('error');
        }

        if (mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                if ($coursecontext->tenantid && $coursecontext->tenantid != $context->tenantid) {
                    return get_string('errordifferenttenant', 'tool_muprog');
                }
            }
        }

        return null;
    }
}
