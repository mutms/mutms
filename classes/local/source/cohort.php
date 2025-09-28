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

use stdClass;

/**
 * Program allocation for all visible cohort members.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cohort extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'cohort';
    }

    /**
     * Can settings of this source be imported to other program?
     *
    /**
     * Can settings of this source be imported to other program?
     *
     * @param stdClass $fromprogram
     * @param stdClass $targetprogram
     * @return bool
     */
    public static function is_import_allowed(stdClass $fromprogram, stdClass $targetprogram): bool {
        global $DB;

        if (!$DB->record_exists('tool_muprog_source', ['type' => static::get_type(), 'programid' => $fromprogram->id])) {
            return false;
        }

        if (!$DB->record_exists('tool_muprog_source', ['type' => static::get_type(), 'programid' => $targetprogram->id])) {
            if (!static::is_new_allowed($targetprogram)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Import source data from one program to another.
     *
     * @param int $fromprogramid
     * @param int $targetprogramid
     * @return stdClass created or updated source record
     */
    public static function import_source_data(int $fromprogramid, int $targetprogramid): stdClass {
        global $DB;

        $targetsource = parent::import_source_data($fromprogramid, $targetprogramid);

        $sql = "SELECT fc.*
                  FROM {tool_muprog_src_cohort} fc
                  JOIN {tool_muprog_source} fs ON fs.id = fc.sourceid AND fs.programid = :fromprogramid AND fs.type = 'cohort'
             LEFT JOIN {tool_muprog_src_cohort} tc ON tc.cohortid = fc.cohortid AND tc.sourceid = :targetsourceid
                 WHERE tc.id IS NULL
              ORDER BY fc.id ASC";
        $params = ['fromprogramid' => $fromprogramid, 'targetsourceid' => $targetsource->id];
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            unset($record->id);
            $record->sourceid = $targetsource->id;
            $DB->insert_record('tool_muprog_src_cohort', $record);
        }

        return $targetsource;
    }

    /**
     * Render details about this enabled source in a programs management ui.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        $result = parent::render_status_details($program, $source);

        if ($source) {
            $cohorts = self::fetch_allocation_cohorts_menu($source->id);
            \core_collator::asort($cohorts);
            if ($cohorts) {
                $cohorts = array_map('format_string', $cohorts);
                $result .= ' (' . implode(', ', $cohorts) . ')';
            }
        }

        return $result;
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
     * Callback method for source updates.
     *
     * @param stdClass|null $oldsource
     * @param stdClass $data
     * @param stdClass|null $source
     * @return void
     */
    public static function after_update(?stdClass $oldsource, stdClass $data, ?stdClass $source): void {
        global $DB;

        if (!$source) {
            // Just deleted or not enabled at all.
            return;
        }

        if (isset($data->cohorts)) {
            debugging('Use cohortids instead of cohorts key', DEBUG_DEVELOPER);
        }

        $oldcohorts = self::fetch_allocation_cohorts_menu($source->id);
        $sourceid = $DB->get_field('tool_muprog_source', 'id', ['programid' => $data->programid, 'type' => 'cohort']);
        $data->cohortids = $data->cohortids ?? [];
        foreach ($data->cohortids as $cid) {
            if (isset($oldcohorts[$cid])) {
                unset($oldcohorts[$cid]);
                continue;
            }
            $record = (object)['sourceid' => $sourceid, 'cohortid' => $cid];
            $DB->insert_record('tool_muprog_src_cohort', $record);
        }
        foreach ($oldcohorts as $cid => $unused) {
            $DB->delete_records('tool_muprog_src_cohort', ['sourceid' => $sourceid, 'cohortid' => $cid]);
        }
    }

    /**
     * Fetch cohorts that allow program allocation automatically.
     *
     * @param int $sourceid
     * @return array
     */
    public static function fetch_allocation_cohorts_menu(int $sourceid): array {
        global $DB;

        $sql = "SELECT c.id, c.name
                  FROM {cohort} c
                  JOIN {tool_muprog_src_cohort} pc ON c.id = pc.cohortid
                 WHERE pc.sourceid = :sourceid
              ORDER BY c.name ASC, c.id ASC";
        $params = ['sourceid' => $sourceid];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Make sure users are allocated properly.
     *
     * This is expected to be called from cron and when
     * program allocation settings are updated.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @return bool true if anything updated
     */
    public static function fix_allocations(?int $programid, ?int $userid): bool {
        global $DB;

        $updated = false;

        // Allocate all missing users and revert archived allocations.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = 'AND p.id = :programid';
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND cm.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $sql = "SELECT DISTINCT p.id, cm.userid, s.id AS sourceid, pa.id AS allocationid
                  FROM {cohort_members} cm
                  JOIN {tool_muprog_src_cohort} psc ON psc.cohortid = cm.cohortid
                  JOIN {tool_muprog_source} s ON s.id = psc.sourceid AND s.type = 'cohort'
                  JOIN {tool_muprog_program} p ON p.id = s.programid
             LEFT JOIN {tool_muprog_allocation} pa ON pa.programid = p.id AND pa.userid = cm.userid
                 WHERE (pa.id IS NULL OR (pa.archived = 1 AND pa.sourceid = s.id))
                       AND p.archived = 0
                       AND (p.timeallocationstart IS NULL OR p.timeallocationstart <= :now1)
                       AND (p.timeallocationend IS NULL OR p.timeallocationend > :now2)
                       $programselect $userselect
              ORDER BY p.id ASC, s.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        $lastprogram = null;
        $lastsource = null;
        foreach ($rs as $record) {
            if ($record->allocationid) {
                $DB->set_field('tool_muprog_allocation', 'archived', 0, ['id' => $record->allocationid]);
            } else {
                if ($lastprogram && $lastprogram->id == $record->id) {
                    $program = $lastprogram;
                } else {
                    $program = $DB->get_record('tool_muprog_program', ['id' => $record->id], '*', MUST_EXIST);
                    $lastprogram = $program;
                }
                if ($lastsource && $lastsource->id == $record->sourceid) {
                    $source = $lastsource;
                } else {
                    $source = $DB->get_record('tool_muprog_source', ['id' => $record->sourceid], '*', MUST_EXIST);
                    $lastsource = $source;
                }
                self::allocation_create($program, $source, $record->userid, []);
                $updated = true;
            }
        }
        $rs->close();

        // Archive allocations if user not member.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = 'AND p.id = :programid';
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $sql = "SELECT pa.id
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_source} s ON s.programid = pa.programid AND s.type = 'cohort' AND s.id = pa.sourceid
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                 WHERE p.archived = 0 AND pa.archived = 0
                       AND NOT EXISTS (
                            SELECT 1
                              FROM {cohort_members} cm
                              JOIN {tool_muprog_src_cohort} psc ON psc.cohortid = cm.cohortid
                             WHERE cm.userid = pa.userid AND psc.sourceid = s.id
                       )
                       AND (p.timeallocationstart IS NULL OR p.timeallocationstart <= :now1)
                       AND (p.timeallocationend IS NULL OR p.timeallocationend > :now2)
                       $programselect $userselect
              ORDER BY pa.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $pa) {
            // NOTE: it is expected that enrolment fixing is executed right after this method.
            $DB->set_field('tool_muprog_allocation', 'archived', 1, ['id' => $pa->id]);
            $updated = true;
        }
        $rs->close();

        return $updated;
    }
}
