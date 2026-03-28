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

namespace tool_mucertify\local\source;

use stdClass;

/**
 * certification assignment for all visible cohort members.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
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
     * Render details about this enabled source in a certification management ui.
     *
     * @param stdClass $certification
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $certification, ?stdClass $source): string {
        $result = parent::render_status_details($certification, $source);

        if ($source) {
            $cohorts = self::fetch_assignment_cohorts_menu($source->id);
            \core_collator::asort($cohorts);
            if ($cohorts) {
                $cohorts = array_map('format_string', $cohorts);
                $result .= ' (' . implode(', ', $cohorts) . ')';
            }
        }

        return $result;
    }

    #[\Override]
    public static function is_assignment_archive_possible(stdClass $certification, stdClass $source, stdClass $assignment): bool {
        return false;
    }

    #[\Override]
    public static function is_assignment_restore_possible(stdClass $certification, stdClass $source, stdClass $assignment): bool {
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

        $oldcohorts = self::fetch_assignment_cohorts_menu($source->id);
        $sourceid = $DB->get_field('tool_mucertify_source', 'id', ['certificationid' => $data->certificationid, 'type' => 'cohort']);
        $data->cohortids = $data->cohortids ?? [];
        foreach ($data->cohortids as $cid) {
            if (isset($oldcohorts[$cid])) {
                unset($oldcohorts[$cid]);
                continue;
            }
            $record = (object)['sourceid' => $sourceid, 'cohortid' => $cid];
            $DB->insert_record('tool_mucertify_src_cohort', $record);
        }
        foreach ($oldcohorts as $cid => $unused) {
            $DB->delete_records('tool_mucertify_src_cohort', ['sourceid' => $sourceid, 'cohortid' => $cid]);
        }
    }

    /**
     * Fetch cohorts that allow certification assignment automatically.
     *
     * @param int $sourceid
     * @return array
     */
    public static function fetch_assignment_cohorts_menu(int $sourceid): array {
        global $DB;

        $sql = "SELECT c.id, c.name
                  FROM {cohort} c
                  JOIN {tool_mucertify_src_cohort} pc ON c.id = pc.cohortid
                 WHERE pc.sourceid = :sourceid
              ORDER BY c.name ASC, c.id ASC";
        $params = ['sourceid' => $sourceid];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Make sure users are assigned properly.
     *
     * This is expected to be called from cron and when
     * certification assignment settings are updated.
     *
     * @param int|null $certificationid
     * @param int|null $userid
     * @return bool true if anything updated
     */
    public static function fix_assignments(?int $certificationid, ?int $userid): bool {
        global $DB;

        $updated = false;

        // Assign all missing users and revert archived assignments.
        $params = [];
        $certificationselect = '';
        if ($certificationid) {
            $certificationselect = 'AND c.id = :certificationid';
            $params['certificationid'] = $certificationid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND cm.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $sql = "SELECT DISTINCT c.id, cm.userid, s.id AS sourceid, ca.id AS assignmentid
                  FROM {cohort_members} cm
                  JOIN {tool_mucertify_src_cohort} psc ON psc.cohortid = cm.cohortid
                  JOIN {tool_mucertify_source} s ON s.id = psc.sourceid
                  JOIN {tool_mucertify_certification} c ON c.id = s.certificationid
             LEFT JOIN {tool_mucertify_assignment} ca ON ca.certificationid = c.id AND ca.userid = cm.userid
                 WHERE (ca.id IS NULL OR (ca.archived = 1 AND ca.sourceid = s.id))
                       AND c.archived = 0
                       $certificationselect $userselect
              ORDER BY c.id ASC, s.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);

        $lastcertification = null;
        $lastsource = null;
        foreach ($rs as $record) {
            if ($record->assignmentid) {
                $DB->set_field('tool_mucertify_assignment', 'archived', 0, ['id' => $record->assignmentid]);
            } else {
                if ($lastcertification && $lastcertification->id == $record->id) {
                    $certification = $lastcertification;
                } else {
                    $certification = $DB->get_record('tool_mucertify_certification', ['id' => $record->id], '*', MUST_EXIST);
                    $lastcertification = $certification;
                }
                if ($lastsource && $lastsource->id == $record->sourceid) {
                    $source = $lastsource;
                } else {
                    $source = $DB->get_record('tool_mucertify_source', ['id' => $record->sourceid], '*', MUST_EXIST);
                    $lastsource = $source;
                }
                self::assignment_create($certification, $source, $record->userid, []);
                $updated = true;
            }
        }
        $rs->close();

        // Archive assignments if user not member.
        $params = [];
        $certificationselect = '';
        if ($certificationid) {
            $certificationselect = 'AND c.id = :certificationid';
            $params['certificationid'] = $certificationid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND ca.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $sql = "SELECT ca.id
                  FROM {tool_mucertify_assignment} ca
                  JOIN {tool_mucertify_source} s ON s.certificationid = ca.certificationid AND s.type = 'cohort' AND s.id = ca.sourceid
                  JOIN {tool_mucertify_certification} c ON c.id = ca.certificationid
                 WHERE c.archived = 0 AND ca.archived = 0
                       AND NOT EXISTS (
                            SELECT 1
                              FROM {cohort_members} cm
                              JOIN {tool_mucertify_src_cohort} psc ON psc.cohortid = cm.cohortid
                             WHERE cm.userid = ca.userid AND psc.sourceid = s.id
                       )
                       $certificationselect $userselect
              ORDER BY ca.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ca) {
            // NOTE: it is expected that program allocation fixing is executed right after this method.
            $DB->set_field('tool_mucertify_assignment', 'archived', 1, ['id' => $ca->id]);
            $updated = true;
        }
        $rs->close();

        return $updated;
    }
}
