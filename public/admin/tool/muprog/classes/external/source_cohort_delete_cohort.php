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

use tool_muprog\local\source\cohort;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

/**
 * Remove cohort from the list of synchronised cohorts in a program.
 *
 * @package     tool_muprog
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_cohort_delete_cohort extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'cohortid' => new external_value(PARAM_INT, 'Cohort id'),
        ]);
    }

    /**
     * Removes the cohort from the list of cohorts that are synced with the program.
     *
     * @param int $programid Program id for which the cohorts have to be fetched.
     * @param int $cohortid Cohort id that has to be removed from the program.
     * @return array
     */
    public static function execute(int $programid, int $cohortid): array {
        global $DB;
        ['programid' => $programid, 'cohortid' => $cohortid] = self::validate_parameters(
            self::execute_parameters(),
            ['programid' => $programid, 'cohortid' => $cohortid]
        );

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'cohort'], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:edit', $context);

        $oldcohorts = cohort::fetch_allocation_cohorts_menu($source->id);
        if (isset($oldcohorts[$cohortid])) {
            unset($oldcohorts[$cohortid]);
            $data = (object)[
                'id' => $source->id,
                'type' => $source->type,
                'programid' => $source->programid,
                'enable' => 1,
                'cohortids' => array_keys($oldcohorts),
            ];
            cohort::update_source($data);
        }

        return source_cohort_get_cohorts::get_cohorts($program->id);
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return source_cohort_get_cohorts::get_cohorts_returns();
    }
}
