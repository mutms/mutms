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

namespace tool_mucertify\local;

use stdClass;

/**
 * Certification assignment helper.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment {
    /**
     * Returns list of all source classes present.
     *
     * @return string[] type => classname
     */
    public static function get_source_classes(): array {
        // Note: in theory this could be extended to load arbitrary classes.
        $types = [
            source\manual::get_type() => source\manual::class,
            source\selfassignment::get_type() => source\selfassignment::class,
            source\approval::get_type() => source\approval::class,
            source\cohort::get_type() => source\cohort::class,
        ];

        return $types;
    }

    /**
     * Find assignment source class.
     *
     * @param string $type
     * @return null|class-string<\tool_mucertify\local\source\base>
     */
    public static function get_source_classname(string $type): ?string {
        $types = self::get_source_classes();
        return $types[$type] ?? null;
    }

    /**
     * Returns list of all source names.
     *
     * @return string[] type => source name
     */
    public static function get_source_names(): array {
        /** @var source\base[] $classes */ // Type hack.
        $classes = self::get_source_classes();

        $result = [];
        foreach ($classes as $class) {
            $result[$class::get_type()] = $class::get_name();
        }

        return $result;
    }

    /**
     * Returns valid until/expiration date as HTML.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public static function get_until_html(stdClass $certification, stdClass $assignment): string {
        global $DB;

        if ($certification->id != $assignment->certificationid) {
            throw new \coding_exception('invalid parameters');
        }

        $sql = "SELECT cp.*
                  FROM {tool_mucertify_period} cp
                  JOIN {tool_mucertify_assignment} ca ON ca.userid = cp.userid AND ca.certificationid = cp.certificationid
                 WHERE cp.timerevoked IS NULL and cp.timecertified IS NOT NULL
                       AND ca.id = :assignmentid";
        $periods = $DB->get_records_sql($sql, ['assignmentid' => $assignment->id]);
        if (!$periods) {
            if ($assignment->timecertifiedtemp) {
                return userdate($assignment->timecertifiedtemp, get_string('strftimedatetimeshort'));
            } else {
                return get_string('notset', 'tool_mucertify');
            }
        }

        $until = null;
        foreach ($periods as $period) {
            if ($period->timeuntil === null) {
                $until = null;
                break;
            }
            if ($period->timeuntil > $until) {
                $until = $period->timeuntil;
            }
        }
        if ($until === null) {
            return get_string('notset', 'tool_mucertify');
        }
        if ($assignment->timecertifiedtemp && $assignment->timecertifiedtemp > $until) {
            return userdate($assignment->timecertifiedtemp, get_string('strftimedatetimeshort'));
        } else {
            return userdate($until, get_string('strftimedatetimeshort'));
        }
    }

    /**
     * Returns completion status as fancy HTML.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public static function get_status_html(stdClass $certification, stdClass $assignment): string {
        $now = time();

        if ($certification->archived || $assignment->archived) {
            return '<span class="badge bg-dark">' . get_string('certificationstatus_archived', 'tool_mucertify') . '</span>';
        }

        if (!$assignment->timecertifiedfrom || $assignment->timecertifiedfrom > $now) {
            return '<span class="badge bg-light text-dark">' . get_string('certificationstatus_notcertified', 'tool_mucertify') . '</span>';
        }

        if (($assignment->timecertifiedtemp ?? $assignment->timecertifieduntil) < $now) {
            return '<span class="badge bg-light text-dark">' . get_string('certificationstatus_expired', 'tool_mucertify') . '</span>';
        }

        if ($assignment->timecertifiedtemp && $assignment->timecertifiedtemp > $now) {
            return '<span class="badge bg-success">' . get_string('certificationstatus_temporary', 'tool_mucertify') . '</span>';
        }

        if ($assignment->timecertifieduntil > $now) {
            return '<span class="badge bg-success">' . get_string('certificationstatus_valid', 'tool_mucertify') . '</span>';
        }

        debugging('Invalid certification status', DEBUG_DEVELOPER);
        return '';
    }

    /**
     * Make sure current program status and certification completion are up-to-date.
     *
     * @param stdClass $assignment
     * @return stdClass
     */
    public static function sync_current_status(stdClass $assignment): stdClass {
        global $DB;

        if ($assignment->archived) {
            return $assignment;
        }

        $now = time();
        $params = [
            'now1' => $now,
            'now2' => $now,
            'certificationid' => $assignment->certificationid,
            'userid' => $assignment->userid,
        ];

        $sql = "SELECT cp.*
                  FROM {tool_mucertify_period} cp
                  JOIN {tool_muprog_program} p ON p.id = cp.programid
                 WHERE cp.timewindowstart < :now1 AND (cp.timewindowend IS NULL OR cp.timewindowend > :now2)
                       AND cp.certificationid = :certificationid AND cp.userid = :userid
                       AND cp.timecertified IS NULL AND cp.timerevoked IS NULL";
        $periods = $DB->get_records_sql($sql, $params);
        foreach ($periods as $period) {
            \tool_muprog\local\allocation::fix_user_enrolments($period->programid, $period->userid);
        }

        self::fix_assignment_sources($assignment->certificationid, $assignment->userid);
        \tool_muprog\local\source\mucertify::sync_certifications($assignment->certificationid, $assignment->userid);

        return $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id], '*', MUST_EXIST);
    }

    /**
     * Ask sources to fix their assignments.
     *
     * This is expected to be called from cron and when
     * certification settings are updated.
     *
     * @param int|null $certificationid
     * @param int|null $userid
     * @return void
     */
    public static function fix_assignment_sources(?int $certificationid, ?int $userid): void {
        $sources = self::get_source_classes();
        foreach ($sources as $source) {
            /** @var source\base $source */
            $source::fix_assignments($certificationid, $userid);
        }
    }

    /**
     * Does user have any active certifications?
     *
     * @param int $userid
     * @return bool
     */
    public static function has_active_assignments(int $userid): bool {
        global $DB;

        $sql = "SELECT 1
                  FROM {tool_mucertify_certification} c
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = c.id
                 WHERE c.archived = 0 AND ca.archived = 0 AND ca.userid = :userid";

        return $DB->record_exists_sql($sql, ['userid' => $userid]);
    }
}
