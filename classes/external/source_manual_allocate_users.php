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

use tool_muprog\local\source\manual;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

/**
 * Allocates the given users to the program.
 *
 * @package     tool_muprog
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_allocate_users extends external_api {
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
                'User ids to be allocated the program',
                VALUE_DEFAULT,
                []
            ),
            'cohortids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Cohort id'),
                'Cohort ids to be allocated to the program',
                VALUE_DEFAULT,
                []
            ),
            'dateoverrides' => new external_single_structure([
                'timestart' => new external_value(PARAM_INT, 'time start', VALUE_OPTIONAL),
                'timedue' => new external_value(PARAM_INT, 'time due', VALUE_OPTIONAL),
                'timeend' => new external_value(PARAM_INT, 'time start', VALUE_OPTIONAL),
            ], 'Array of date overrides, timestart timedue timeend can be passed as unix timestamps', VALUE_DEFAULT, []),
        ]);
    }

    /**
     * Allocates the users to the program.
     *
     * @param int $programid Program id.
     * @param array $userids Users list to whom the program should be allocated.
     * @param array $cohortids Cohort list to whom the program should be allocated.
     * @param array $dateoverrides Date overrides.
     * @return array
     */
    public static function execute(int $programid, array $userids = [], array $cohortids = [], array $dateoverrides = []): array {
        global $DB;
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['programid' => $programid, 'userids' => $userids, 'cohortids' => $cohortids, 'dateoverrides' => $dateoverrides]
        );
        $userids = $params['userids'];
        $programid = $params['programid'];
        $cohortids = $params['cohortids'];
        $dateoverrides = $params['dateoverrides'];

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record(
            'tool_muprog_source',
            ['type' => 'manual', 'programid' => $program->id],
            '*',
            MUST_EXIST
        );

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:allocate', $context);

        if ($program->archived) {
            throw new \invalid_parameter_exception('Program is archived');
        }

        if (!manual::is_valid_dateoverrides($program, $dateoverrides)) {
            throw new \invalid_parameter_exception('Invalid program allocation dates');
        }

        $useridstoallocate = [];
        foreach ($userids as $userid) {
            if ($DB->record_exists('tool_muprog_allocation', ['userid' => $userid, 'programid' => $program->id])) {
                continue;
            }
            $useridstoallocate[$userid] = $userid;
        }
        foreach ($cohortids as $cohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
            $cohortcontext = \context::instance_by_id($cohort->contextid);
            require_capability('moodle/cohort:view', $cohortcontext);
            $cohrotuserids = $DB->get_fieldset_select('cohort_members', 'userid', "cohortid = ?", [$cohort->id]);
            foreach ($cohrotuserids as $userid) {
                if ($DB->record_exists('tool_muprog_allocation', ['userid' => $userid, 'programid' => $program->id])) {
                    continue;
                }
                $useridstoallocate[$userid] = $userid;
            }
        }

        if (\tool_muprog\local\util::is_mutenancy_active()) {
            $programtenantid = $DB->get_field('context', 'tenantid', ['id' => $program->contextid]);
            if ($programtenantid) {
                foreach ($useridstoallocate as $userid) {
                    $usertenantid = \tool_mutenancy\local\tenancy::get_user_tenantid($userid);
                    if ($usertenantid && $usertenantid != $programtenantid) {
                        throw new \invalid_parameter_exception('Tenant mismatch');
                    }
                }
            }
        }

        if ($useridstoallocate) {
            $useridstoallocate = array_values($useridstoallocate);
            manual::allocate_users($program->id, $source->id, $useridstoallocate, $dateoverrides);
        }

        return $useridstoallocate;
    }
    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_value(PARAM_INT, 'User id'),
            'List of users who were enrolled'
        );
    }
}
