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

namespace tool_muprog\external;

use tool_muprog\local\allocation;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core\exception\invalid_parameter_exception;

/**
 * Updates the allocation for the given userid and program id.
 *
 * @package     tool_muprog
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class update_program_allocation extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
            'allocationdates' => new external_single_structure([
                'timestart' => new external_value(PARAM_INT, 'time start', VALUE_OPTIONAL),
                'timedue' => new external_value(PARAM_INT, 'time due', VALUE_OPTIONAL),
                'timeend' => new external_value(PARAM_INT, 'time start', VALUE_OPTIONAL),
            ], 'Array of updates for timestart, timedue, timeend can be passed as unix timestamps', VALUE_DEFAULT, []),
        ]);
    }

    /**
     * Updates the allocation for the given userid and programid.
     *
     * @param int $programid Program id.
     * @param int $userid User id.
     * @param array $allocationdates optional allocation dates.
     * @return \stdClass
     */
    public static function execute(int $programid, int $userid, array $allocationdates = []): \stdClass {
        global $DB;
        [
            'programid' => $programid,
            'userid' => $userid,
            'allocationdates' => $allocationdates,
        ] = self::validate_parameters(self::execute_parameters(), [
            'programid' => $programid,
            'userid' => $userid,
            'allocationdates' => $allocationdates,
        ]);

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:admin', $context);

        $allocation = $DB->get_record(
            'tool_muprog_allocation',
            ['programid' => $program->id, 'userid' => $user->id],
            '*',
            MUST_EXIST
        );

        $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid]);
        $sourceclass = allocation::get_source_classname($source->type);
        if (!$sourceclass) {
            throw new invalid_parameter_exception('Invalid allocation data');
        }

        if (!$sourceclass::is_allocation_update_possible($program, $source, $allocation)) {
            throw new invalid_parameter_exception('Allocation data cannot be updated');
        }

        foreach ($allocationdates as $name => $value) {
            if ($name !== 'timestart' && $name !== 'timedue' && $name !== 'timeend') {
                throw new invalid_parameter_exception('Invalid date type');
            }
            $allocation->$name = $value;
        }
        $errors = allocation::validate_allocation_dates(
            $allocation->timestart,
            $allocation->timedue,
            $allocation->timeend
        );
        if ($errors) {
            throw new invalid_parameter_exception('Allocation dates are invalid:' . implode($errors));
        }

        $allocation = \tool_muprog\local\source\base::allocation_update($allocation);

        $allocation->sourcetype = $source->type;
        $allocation->deletepossible = $sourceclass::is_allocation_delete_possible($program, $source, $allocation);
        $allocation->archivepossible = $sourceclass::is_allocation_archive_possible($program, $source, $allocation);
        $allocation->restorepossible = $sourceclass::is_allocation_restore_possible($program, $source, $allocation);
        $allocation->editpossible = $sourceclass::is_allocation_update_possible($program, $source, $allocation);

        unset($allocation->sourcedatajson);
        unset($allocation->sourceinstanceid);

        return $allocation;
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        // NOTE: This is reused from all other methods that return allocation info.
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Program allocation id'),
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
            'sourceid' => new external_value(PARAM_INT, 'Allocation source id'),
            'sourcetype' => new external_value(PARAM_ALPHANUMEXT, 'Internal source name'),
            'archived' => new external_value(PARAM_BOOL, 'Indicates allocation is archived'),
            'timeallocated' => new external_value(PARAM_INT, 'Allocation date'),
            'timestart' => new external_value(PARAM_INT, 'Allocation start date'),
            'timedue' => new external_value(PARAM_INT, 'Allocation due date'),
            'timeend' => new external_value(PARAM_INT, 'Allocation end date'),
            'timecompleted' => new external_value(PARAM_INT, 'Allocation completed date'),
            'timecreated' => new external_value(PARAM_INT, 'Allocation created date'),
            'deletepossible' => new external_value(PARAM_BOOL, 'Flag to indicate if delete is supported'),
            'archivepossible' => new external_value(PARAM_BOOL, 'Flag to indicate if archiving is possible'),
            'restorepossible' => new external_value(PARAM_BOOL, 'Flag to indicate if restoring is possible'),
            'editpossible' => new external_value(PARAM_BOOL, 'Flag to indicate if edit is supported'),
        ], 'Details of the program allocation');
    }
}
