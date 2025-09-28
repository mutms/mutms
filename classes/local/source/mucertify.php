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

namespace tool_muprog\local\source;

use tool_muprog\local\course_reset;
use stdClass;

/**
 * Program allocation for certifications from tool_mucertify.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mucertify extends base {
    /**
     * Returns short type name of source.
     *
     * @return string
     */
    public static function get_type(): string {
        return 'mucertify';
    }

    #[\Override]
    public static function is_allocation_update_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return false;
    }

    #[\Override]
    public static function is_allocation_archive_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return false;
    }

    #[\Override]
    public static function is_allocation_restore_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return false;
    }

    /**
     * Is it possible to manually delete user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function is_allocation_delete_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        // Ignore program archived flag here.
        if ($allocation->archived) {
            return true;
        }
        if ($allocation->timeend && $allocation->timeend < time()) {
            // Allow manual deallocation after certification window closes.
            return true;
        }
        return false;
    }

    /**
     * Render details about this enabled source in a programs management ui.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        global $DB;

        $result = parent::render_status_details($program, $source);

        if ($source) {
            $params = [];
            $params['sourceid'] = $source->id;
            $sql = "SELECT c.*
                      FROM {tool_mucertify_certification} c
                      JOIN {tool_muprog_source} s ON s.id = :sourceid AND (c.programid1 = s.programid OR c.programid2 = s.programid)
                  ORDER BY c.fullname ASC";
            $certifications = $DB->get_records_sql($sql, $params);
            if ($certifications) {
                foreach ($certifications as $k => $certification) {
                    $name = format_string($certification->fullname);
                    $certcontext = \context::instance_by_id($certification->contextid, IGNORE_MISSING);
                    if ($certcontext && has_capability('tool/mucertify:view', $certcontext)) {
                        $viewurl = new \moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
                        $name = \html_writer::link($viewurl, $name);
                    }
                    $certifications[$k] = $name;
                }
                $result .= ' - ' . implode(', ', $certifications);
            }
        }

        return $result;
    }

    /**
     * Sync certification periods with program allocations.
     *
     * @param int|null $certificationid
     * @param int|null $userid
     * @return void
     */
    public static function sync_certifications(?int $certificationid, ?int $userid): void {
        global $DB;

        if (!PHPUNIT_TEST && !$userid && $DB->is_transaction_started()) {
            debugging('assignment::sync_certifications() is not supposed to be used in transactions without userid', DEBUG_DEVELOPER);
        }

        $sourceclasses = \tool_muprog\local\allocation::get_source_classes();

        // Delete allocations from deleted certifications, assignments and periods, or when period data does not match allocation.
        $params = [];
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        } else {
            $userselect = '';
        }
        if ($certificationid) {
            $certificationselect = "AND pa.sourceinstanceid = :certificationid";
            $params['certificationid'] = $certificationid;
        } else {
            $certificationselect = '';
        }
        $sql = "SELECT pa.id, pa.programid, pa.userid
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_muprog_source} ps ON ps.programid = p.id AND ps.type = 'mucertify' AND ps.id = pa.sourceid
                 WHERE NOT EXISTS (
                            SELECT 'x'
                              FROM {tool_mucertify_period} cp
                              JOIN {tool_mucertify_assignment} ca ON ca.certificationid = cp.certificationid AND ca.userid = cp.userid
                              JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
                             WHERE cp.allocationid = pa.id AND cp.programid = pa.programid AND cp.userid = pa.userid)";
        $sql .= " $userselect $certificationselect";
        $sql .= " ORDER BY pa.id ASC";
        $allocations = $DB->get_records_sql($sql, $params);
        foreach ($allocations as $allocation) {
            self::purge_allocation($allocation->id);
            \tool_muprog\local\allocation::fix_user_enrolments($allocation->programid, $allocation->userid);
            \tool_muprog\local\calendar::delete_allocation_events($allocation->id);
        }
        unset($allocations);

        // Archive allocations for historic, revoked and archived periods.
        $params = [];
        $params['now2'] = $params['now1'] = time();
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        } else {
            $userselect = '';
        }
        if ($certificationid) {
            $certificationselect = "AND pa.sourceinstanceid = :certificationid";
            $params['certificationid'] = $certificationid;
        } else {
            $certificationselect = '';
        }
        $sql = "SELECT pa.id, pa.programid, pa.userid
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_muprog_source} ps ON ps.programid = p.id AND ps.type = 'mucertify' AND ps.id = pa.sourceid
                  JOIN {tool_mucertify_period} cp ON cp.allocationid = pa.id
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = cp.certificationid AND ca.userid = cp.userid
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
                 WHERE pa.archived = 0
                       AND (
                            cp.timerevoked IS NOT NULL
                            OR ca.archived = 1
                            OR c.archived = 1
                            OR (cp.timewindowend < :now1)
                            OR (cp.timeuntil < :now2)
                       )";
        $sql .= " $userselect $certificationselect";
        $sql .= " ORDER BY pa.id ASC";
        $allocations = $DB->get_records_sql($sql, $params);
        foreach ($allocations as $allocation) {
            $DB->set_field('tool_muprog_allocation', 'archived', 1, ['id' => $allocation->id]);
            \tool_muprog\local\allocation::fix_user_enrolments($allocation->programid, $allocation->userid);
        }
        unset($allocations);

        // Restore incorrectly archived users.
        $params = [];
        $params['now2'] = $params['now1'] = time();
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        } else {
            $userselect = '';
        }
        if ($certificationid) {
            $certificationselect = "AND pa.sourceinstanceid = :certificationid";
            $params['certificationid'] = $certificationid;
        } else {
            $certificationselect = '';
        }
        $sql = "SELECT pa.id, pa.programid, pa.userid
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_mucertify_period} cp ON cp.allocationid = pa.id
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = cp.certificationid AND ca.userid = cp.userid
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
                  JOIN {tool_muprog_source} ps ON ps.programid = p.id AND ps.type = 'mucertify' AND ps.id = pa.sourceid
                 WHERE pa.archived = 1
                       AND cp.timerevoked IS NULL AND ca.archived = 0 AND c.archived = 0 AND p.archived = 0
                       AND (cp.timewindowend IS NULL OR cp.timewindowend > :now1)
                       AND (cp.timeuntil IS NULL OR cp.timeuntil > :now2)
                       $userselect $certificationselect
              ORDER BY pa.id ASC";
        $allocations = $DB->get_records_sql($sql, $params);
        foreach ($allocations as $allocation) {
            $period = $DB->get_record('tool_mucertify_period', ['allocationid' => $allocation->id]);
            if (!$period) {
                continue;
            }
            $record = new stdClass();
            $record->id = $allocation->id;
            $record->archived = 0;
            $record->timestart = $period->timewindowstart;
            $record->timedue = $period->timewindowdue;
            $record->timeend = $period->timewindowend;
            $DB->update_record('tool_muprog_allocation', $record);
            \tool_muprog\local\allocation::fix_user_enrolments($allocation->programid, $allocation->userid);
        }
        unset($allocations);

        // Sync program dates.
        $params = [];
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        } else {
            $userselect = '';
        }
        if ($certificationid) {
            $certificationselect = "AND pa.sourceinstanceid = :certificationid";
            $params['certificationid'] = $certificationid;
        } else {
            $certificationselect = '';
        }
        $sql = "SELECT pa.id, pa.programid, pa.userid
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_mucertify_period} cp ON cp.allocationid = pa.id
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = cp.certificationid AND ca.userid = cp.userid
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
                  JOIN {tool_muprog_source} ps ON ps.programid = p.id AND ps.type = 'mucertify' AND ps.id = pa.sourceid
                 WHERE pa.archived = 0 AND p.archived = 0
                       AND (
                           (pa.timestart <> cp.timewindowstart)
                           OR (pa.timedue IS NULL AND cp.timewindowdue IS NOT NULL)
                           OR (pa.timedue IS NOT NULL AND cp.timewindowdue IS NULL)
                           OR (pa.timedue <> cp.timewindowdue)
                           OR (pa.timeend IS NULL AND cp.timewindowend IS NOT NULL)
                           OR (pa.timeend IS NOT NULL AND cp.timewindowend IS NULL)
                           OR (pa.timeend <> cp.timewindowend)
                       )
                       $userselect $certificationselect
              ORDER BY pa.id ASC";
        $allocations = $DB->get_records_sql($sql, $params);
        foreach ($allocations as $allocation) {
            $period = $DB->get_record('tool_mucertify_period', ['allocationid' => $allocation->id]);
            if (!$period) {
                continue;
            }
            $record = new stdClass();
            $record->id = $allocation->id;
            $record->timestart = $period->timewindowstart;
            $record->timedue = $period->timewindowdue;
            $record->timeend = $period->timewindowend;
            $DB->update_record('tool_muprog_allocation', $record);
            \tool_muprog\local\allocation::fix_user_enrolments($allocation->programid, $allocation->userid);
        }
        unset($allocations);

        // Allocate users to programs in active windows.
        $params = [];
        $params['now2'] = $params['now1'] = time();
        $params['soon'] = $params['now1'] + (HOURSECS * 2); // This should be twice the recommended cron period.
        if ($userid) {
            $userselect = "AND u.id = :userid";
            $params['userid'] = $userid;
        } else {
            $userselect = '';
        }
        if ($certificationid) {
            $certificationselect = "AND c.id = :certificationid";
            $params['certificationid'] = $certificationid;
        } else {
            $certificationselect = '';
        }
        $sql = "SELECT cp.id
                  FROM {tool_mucertify_period} cp
                  JOIN {user} u ON u.id = cp.userid AND u.deleted = 0 AND u.confirmed = 1
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
                  JOIN {tool_mucertify_assignment} ca ON ca.userid = u.id AND ca.certificationid = c.id
                  JOIN {tool_muprog_program} p ON p.id = cp.programid
                  JOIN {tool_muprog_source} ps ON ps.programid = p.id AND ps.type = 'mucertify'
                 WHERE c.archived = 0 AND ca.archived = 0 AND p.archived = 0
                       AND cp.allocationid IS NULL AND cp.timecertified IS NULL AND cp.timerevoked IS NULL
                       AND cp.timewindowstart < :soon
                       AND (cp.timewindowend IS NULL OR cp.timewindowend > :now1)
                       AND (cp.timeuntil IS NULL OR cp.timeuntil > :now2)
                       $userselect $certificationselect
              ORDER BY cp.id ASC";
        $periods = $DB->get_records_sql($sql, $params);
        foreach ($periods as $period) {
            // Always load current data, this loop may take a long time, this could run in parallel.
            $period = $DB->get_record('tool_mucertify_period', ['id' => $period->id]);
            if (!$period || isset($period->allocationid)) {
                continue;
            }
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $period->certificationid], '*', MUST_EXIST);
            $settings = \tool_mucertify\local\certification::get_periods_settings($certification);

            $program = $DB->get_record('tool_muprog_program', ['id' => $period->programid], '*', MUST_EXIST);
            $allocation = $DB->get_record('tool_muprog_allocation', ['userid' => $period->userid, 'programid' => $period->programid]);
            $user = $DB->get_record('user', ['id' => $period->userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

            if ($period->first) {
                $resettype = $settings->resettype1;
            } else {
                $resettype = $settings->resettype2;
            }

            if ($resettype == course_reset::RESETTYPE_NONE) {
                if ($allocation) {
                    // Do not delete allocation, use whatever is there.
                    $DB->set_field('tool_mucertify_period', 'allocationid', 0, ['id' => $period->id]);
                    continue;
                }
            } else {
                // Remove all previous allocations.
                if ($allocation) {
                    $delsource = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);
                    /** @var \tool_muprog\local\source\base $sourceclass */
                    $sourceclass = $sourceclasses[$delsource->type];
                    $sourceclass::allocation_delete($program, $delsource, $allocation);
                }

                course_reset::reset_courses($user, $resettype, $program->id);

                $allocation = $DB->get_record('tool_muprog_allocation', ['userid' => $period->userid, 'programid' => $period->programid]);
                if ($allocation) {
                    // Something is wrong, probably some automatic allocation source messing this up or a race condition,
                    // just use the allocation because it must be fresh new allocation with optional course reset,
                    // note that the dates may be wrong.
                    $DB->set_field('tool_mucertify_period', 'allocationid', 0, ['id' => $period->id]);
                    continue;
                }
            }

            // Finally allocate user to program.
            $source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'mucertify'], '*', MUST_EXIST);
            $dateoverrides = [
                'timestart' => $period->timewindowstart,
                'timedue' => $period->timewindowdue,
                'timeend' => $period->timewindowend,
            ];
            $allocation = self::allocation_create($program, $source, $period->userid, [], $dateoverrides, $certification->id);
            $DB->set_field('tool_mucertify_period', 'allocationid', $allocation->id, ['id' => $period->id]);
            \tool_muprog\local\allocation::fix_user_enrolments($program->id, $period->userid);
        }
        unset($periods);

        // Sync program completions if necessary - event may be future, missed or program may be already completed when period created.
        $params = [];
        $params['now4'] = $params['now3'] = $params['now2'] = $params['now1'] = time();
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        } else {
            $userselect = '';
        }
        if ($certificationid) {
            $certificationselect = "AND ca.certificationid = :certificationid";
            $params['certificationid'] = $certificationid;
        } else {
            $certificationselect = '';
        }
        $sql = "SELECT pa.id
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_mucertify_period} cp ON cp.programid = pa.programid AND cp.userid = pa.userid
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = cp.certificationid AND ca.userid = cp.userid
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
                 WHERE pa.archived = 0 AND ca.archived = 0 AND c.archived = 0 AND p.archived = 0
                       AND pa.timecompleted <= :now4
                       AND cp.timecertified IS NULL AND cp.timerevoked IS NULL
                       AND (cp.timewindowend IS NULL OR cp.timewindowend > :now1)
                       AND (cp.timeuntil IS NULL OR cp.timeuntil > :now2)
                       AND cp.timewindowstart <= :now3
                       $userselect $certificationselect
              ORDER BY pa.id ASC";
        $allocations = $DB->get_records_sql($sql, $params);
        foreach ($allocations as $allocation) {
            $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id]);
            if (!$allocation || !isset($allocation->timecompleted) || $allocation->archived) {
                continue;
            }
            $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid]);
            if (!$program || $program->archived) {
                continue;
            }
            \tool_mucertify\local\period::allocation_completed($program, $allocation);
        }
        unset($allocations);
    }

    /**
     * Render allocation source information.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return string HTML fragment
     */
    public static function render_allocation_source(stdClass $program, stdClass $source, stdClass $allocation): string {
        global $DB, $USER;

        $type = static::get_type();

        if ($source && $source->type !== $type) {
            throw new \coding_exception('Invalid source type');
        }

        $period = $DB->get_record('tool_mucertify_period', ['allocationid' => $allocation->id]);
        if ($period) {
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $period->certificationid]);
            $cname = format_string($certification->fullname);
            if ($period->userid == $USER->id) {
                $curl = new \moodle_url('/admin/tool/mucertify/my/certification.php', ['id' => $certification->id]);
                return \html_writer::link($curl, $cname);
            }
            $context = \context::instance_by_id($certification->contextid, IGNORE_MISSING);
            if ($context && has_capability('tool/mucertify:view', $context)) {
                $curl = new \moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
                return \html_writer::link($curl, $cname);
            }
        }

        return static::get_name();
    }
}
