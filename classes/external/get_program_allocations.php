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
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User id'),
                'List of user ids for whom the program allocation must be fetched, NULL or empty array means all',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
        ]);
    }

    /**
     * Returns list of programs allocations for given programid and optional users.
     *
     * @param int $programid Program id
     * @param array|null $userids Users for whom this info has to be returned, NULL and empty array means all
     * @return array
     */
    public static function execute(int $programid, ?array $userids = null): array {
        global $DB;

        [
            'programid' => $programid,
            'userids' => $userids,
        ] = self::validate_parameters(self::execute_parameters(), [
            'programid' => $programid,
            'userids' => $userids,
        ]);

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:view', $context);

        $results = [];
        if (!$userids) {
            $allocations = $DB->get_records('tool_muprog_allocation', ['programid' => $programid], 'id');
        } else {
            $allocations = [];
            foreach ($userids as $userid) {
                $allocationrecord = $DB->get_record('tool_muprog_allocation', ['programid' => $programid, 'userid' => $userid]);
                if (!$allocationrecord) {
                    continue;
                }
                $allocations[$allocationrecord->id] = $allocationrecord;
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
            $allocation->deletepossible = $sourceclass::is_allocation_delete_possible($program, $source, $allocation);
            $allocation->archivepossible = $sourceclass::is_allocation_archive_possible($program, $source, $allocation);
            $allocation->restorepossible = $sourceclass::is_allocation_restore_possible($program, $source, $allocation);
            $allocation->editpossible = $sourceclass::is_allocation_update_possible($program, $source, $allocation);

            unset($allocation->sourcedatajson);
            unset($allocation->sourceinstanceid);

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
            update_program_allocation::execute_returns()
        );
    }
}
