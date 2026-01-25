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

namespace tool_mucertify\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

/**
 * Provides list of certification periods.
 *
 * @package     tool_mucertify
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_certification_periods extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'certificationid' => new external_value(PARAM_INT, 'Certification id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }

    /**
     * Returns list of periods for given certification and user.
     *
     * @param int $certificationid Certification id
     * @param int $userid User id
     * @return array
     */
    public static function execute(int $certificationid, int $userid): array {
        global $DB;

        [
            'certificationid' => $certificationid,
            'userid' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'certificationid' => $certificationid,
            'userid' => $userid,
        ]);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($certification->contextid);
        self::validate_context($context);
        require_capability('tool/mucertify:view', $context);

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        $periods = $DB->get_records('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user->id], 'timewindowstart ASC');
        foreach ($periods as $k => $v) {
            unset($periods[$k]->first);
            unset($periods[$k]->certificateissueid);
        }

        return $periods;
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Certification assignment id'),
                'certificationid' => new external_value(PARAM_INT, 'Certification id'),
                'userid' => new external_value(PARAM_INT, 'User id'),
                'programid' => new external_value(PARAM_INT, 'Program id'),
                'timewindowstart' => new external_value(PARAM_INT, 'Specifies program allocation start date'),
                'timewindowdue' => new external_value(PARAM_INT, 'Specifies program allocation due date'),
                'timewindowend' => new external_value(PARAM_INT, 'Specifies program allocation end date'),
                'allocationid' => new external_value(PARAM_INT, 'Program allocation id, NULL means not yet allocated, value is kept after de-allocation, 0 means error'),
                'timecertified' => new external_value(PARAM_INT, 'Usually matches program completion date, cen be overridden with admin capability'),
                'timefrom' => new external_value(PARAM_INT, 'Start date of validity - required when timecertified set'),
                'timeuntil' => new external_value(PARAM_INT, 'End date of validity - required when timecertified set'),
                'timerevoked' => new external_value(PARAM_INT, 'Date means user is not certified even if timecertified present, required to be set before deleting period'),
                'evidencejson' => new external_value(PARAM_RAW, 'Alternative certification evidence or revoking details'),
                'recertifiable' => new external_value(PARAM_BOOL, 'Is this a candidate for recertification? value 1 or 0 - expected only in the last non-revoked period'),
            ], 'List of certification periods')
        );
    }
}
