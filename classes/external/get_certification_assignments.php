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
 * Provides list of certification assignments.
 *
 * @package     tool_mucertify
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_certification_assignments extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'certificationid' => new external_value(PARAM_INT, 'Certification id'),
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User id'),
                'List of user ids for whom the certification assignment must be fetched, NULL or empty array means all',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
        ]);
    }

    /**
     * Returns list of certifications assignments for given certificationid and optional users.
     *
     * @param int $certificationid Certification id
     * @param array|null $userids Users for whom this info has to be returned, NULL and empty array means all
     * @return array
     */
    public static function execute(int $certificationid, ?array $userids = null): array {
        global $DB;

        [
            'certificationid' => $certificationid,
            'userids' => $userids,
        ] = self::validate_parameters(self::execute_parameters(), [
            'certificationid' => $certificationid,
            'userids' => $userids,
        ]);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($certification->contextid);
        self::validate_context($context);
        require_capability('tool/mucertify:view', $context);

        if (!$userids) {
            $assignments = $DB->get_records('tool_mucertify_assignment', ['certificationid' => $certificationid], 'id ASC');
        } else {
            $assignments = [];
            foreach ($userids as $userid) {
                $assignmentrecord = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certificationid, 'userid' => $userid]);
                if (!$assignmentrecord) {
                    continue;
                }
                $assignments[$assignmentrecord->id] = $assignmentrecord;
            }
            ksort($assignments, SORT_NUMERIC);
        }

        $sources = $DB->get_records('tool_mucertify_source', ['certificationid' => $certification->id]);

        $results = [];
        foreach ($assignments as $assignment) {
            if (!isset($sources[$assignment->sourceid])) {
                // Ignore invalid data.
                continue;
            }
            $source = $sources[$assignment->sourceid];
            $sourceclass = \tool_mucertify\local\assignment::get_source_classname($source->type);
            if (!$sourceclass) {
                continue;
            }
            $assignment->sourcetype = $source->type;
            $assignment->deletepossible = $sourceclass::is_assignment_delete_possible($certification, $source, $assignment);
            $assignment->archivepossible = $sourceclass::is_assignment_archive_possible($certification, $source, $assignment);
            $assignment->restorepossible = $sourceclass::is_assignment_restore_possible($certification, $source, $assignment);

            unset($assignment->sourcedatajson);

            $results[] = $assignment;
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
                'id' => new external_value(PARAM_INT, 'Certification assignment id'),
                'certificationid' => new external_value(PARAM_INT, 'Certification id'),
                'userid' => new external_value(PARAM_INT, 'User id'),
                'sourceid' => new external_value(PARAM_INT, 'Assignment source id'),
                'sourcetype' => new external_value(PARAM_ALPHANUMEXT, 'Internal source type'),
                'archived' => new external_value(PARAM_BOOL, 'Archived flag - Archived assignments should not change'),
                'timecertifiedtemp' => new external_value(PARAM_INT, 'Temporary certification valid until date'),
                'evidencejson' => new external_value(PARAM_RAW, 'Other evidence for temporary certification'),
                'timecertifiedfrom' => new external_value(PARAM_INT, 'Date of first certification period start'),
                'timecertifieduntil' => new external_value(PARAM_INT, 'Date of latest certification end'),
                'timecreated' => new external_value(PARAM_INT, 'Assignment date'),
                'deletepossible' => new external_value(PARAM_BOOL, 'Flag to indicate if delete is supported'),
                'archivepossible' => new external_value(PARAM_BOOL, 'Flag to indicate if archiving is possible'),
                'restorepossible' => new external_value(PARAM_BOOL, 'Flag to indicate if restoring is possible'),
            ], 'List of certification assignments')
        );
    }
}
