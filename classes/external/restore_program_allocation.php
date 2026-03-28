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
 * Restores the allocation for the given userid and program id.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class restore_program_allocation extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }

    /**
     * Restores the allocation for the given userid and programid.
     *
     * @param int $programid Program id.
     * @param int $userid User id.
     * @return \stdClass
     */
    public static function execute(int $programid, int $userid): \stdClass {
        global $DB;
        [
            'programid' => $programid,
            'userid' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'programid' => $programid,
            'userid' => $userid,
        ]);

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:allocate', $context);

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

        if (!$sourceclass::is_allocation_restore_possible($program, $source, $allocation)) {
            throw new invalid_parameter_exception('Allocation cannot be restored');
        }

        $allocation = \tool_muprog\local\source\base::allocation_restore($allocation->id);

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
        return update_program_allocation::execute_returns();
    }
}
