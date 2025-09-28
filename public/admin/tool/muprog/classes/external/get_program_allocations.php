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

namespace tool_muprog\external;

use tool_muprog\local\allocation;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

/**
 * Provides list of program allocations for given program and optional list of users.
 *
 * @package     tool_muprog
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_program_allocations extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     * NOTE For now Moodle does not allow multiple_structure to be null , so have left it as value default and empty array.
     * see MDL-78192 for details, when possible convert empty array to null.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User id'),
                'List of user ids for whom the program allocation must be fetched',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Returns list of programs allocations for given programid and optional users.
     *
     * @param int $programid Program id
     * @param array $userids Users for whom this info has to be returned (optional).
     * @return array
     */
    public static function execute(int $programid, array $userids = []): array {
        global $DB;

        ['programid' => $programid, 'userids' => $userids] = self::validate_parameters(
            self::execute_parameters(),
            ['programid' => $programid, 'userids' => $userids]
        );

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:view', $context);

        $results = [];
        if (empty($userids)) {
            $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $programid], 'id');
        } else {
            $allocations = [];
            foreach ($userids as $userid) {
                $allocationrecord = $DB->get_record('tool_muprog_allocation', ['programid' => $programid, 'userid' => $userid]);
                if ($allocationrecord) {
                    $allocations[$allocationrecord->id] = $allocationrecord;
                }
            }
            ksort($allocations, SORT_NUMERIC);
        }

        $sourceclasses = allocation::get_source_classes();
        $sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id]);
        foreach ($allocations as $allocation) {
            if (!isset($sources[$allocation->sourceid]) || !isset($sourceclasses[$sources[$allocation->sourceid]->type])) {
                // Ignore invalid data.
                continue;
            }
            $source = $sources[$allocation->sourceid];
            /** @var class-string<\tool_muprog\local\source\base> $sourceclass */
            $sourceclass = $sourceclasses[$source->type];
            $allocation->sourcetype = $source->type;
            $allocation->deletesupported = $sourceclass::is_allocation_delete_possible($program, $source, $allocation);
            $allocation->editsupported = $sourceclass::is_allocation_update_possible($program, $source, $allocation);
            $results[] = $allocation;
        }

        return $results;
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Program allocation id'),
                'programid' => new external_value(PARAM_INT, 'Program id'),
                'userid' => new external_value(PARAM_INT, 'User id'),
                'sourceid' => new external_value(PARAM_INT, 'Allocation source id'),
                'archived' => new external_value(PARAM_BOOL, 'Archived flag (Archived allocations do not change)'),
                'sourcedatajson' => new external_value(PARAM_RAW, 'Source data json (internal)'),
                'sourceinstanceid' => new external_value(PARAM_INT, 'Allocation source instance id (internal)'),
                'timeallocated' => new external_value(PARAM_INT, 'Allocation date'),
                'timestart' => new external_value(PARAM_INT, 'Allocation start date'),
                'timedue' => new external_value(PARAM_INT, 'Allocation due date'),
                'timeend' => new external_value(PARAM_INT, 'Allocation end date'),
                'timecompleted' => new external_value(PARAM_INT, 'Allocation completed date'),
                'timecreated' => new external_value(PARAM_INT, 'Allocation created date'),
                'sourcetype' => new external_value(PARAM_ALPHANUMEXT, 'Internal source name'),
                'deletesupported' => new external_value(PARAM_BOOL, 'Flag to indicate if delete is supported'),
                'editsupported' => new external_value(PARAM_BOOL, 'Flag to indicate if edit is supported'),
            ], 'List of program allocations')
        );
    }
}
