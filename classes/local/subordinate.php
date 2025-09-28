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

namespace tool_murelation\local;

use stdClass;
use tool_mulib\local\sql;

/**
 * Subordinate position helper class.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class subordinate {
    /**
     * Add subordinate to supervisor position.
     *
     * @param stdClass $data
     * @return stdClass subordinate record
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $data->supervisorid], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

        $subordinate = $DB->get_record('tool_murelation_subordinate', ['userid' => $user->id, 'frameworkid' => $framework->id]);
        if ($subordinate) {
            if ($subordinate->supervisorid == $supervisor->id) {
                return $subordinate;
            }
            throw new \core\exception\invalid_parameter_exception('subordinate already has other supervisor');
        }

        if ($supervisor->maxsubordinates) {
            $count = $DB->count_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id]);
            if ($count >= $supervisor->maxsubordinates) {
                throw new \core\exception\invalid_parameter_exception('subordinate limit was already reached');
            }
        }

        $teamposition = null;
        if ($framework->uimode == framework::UIMODE_SUPERVISORS) {
            if ($supervisor->userid == $user->id) {
                throw new \core\exception\invalid_parameter_exception('supervisor cannot be own subordinate in supervisors mode');
            }
        } else if ($framework->uimode == framework::UIMODE_TEAMS) {
            if (trim($data->teamposition ?? '') !== '') {
                $teamposition = $data->teamposition;
            }
        }

        $trans = $DB->start_delegated_transaction();

        $record = (object)[
            'supervisorid' => $supervisor->id,
            'frameworkid' => $framework->id,
            'userid' => $user->id,
            'teamposition' => $teamposition,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record('tool_murelation_subordinate', $record);

        $subordinate = $DB->get_record('tool_murelation_subordinate', ['id' => $record->id]);

        supervisor::sync_roles($supervisor->id);

        if ($supervisor->teamcohortid) {
            supervisor::sync_team_cohorts($supervisor->id);
        }

        $trans->allow_commit();

        return $subordinate;
    }

    /**
     * Update subordinate position name.
     *
     * NOTE: userid and supervisorid cannot be changed here.
     *
     * @param stdClass $data
     * @return stdClass subordinate record
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $subordinate = $DB->get_record('tool_murelation_subordinate', ['id' => $data->id], '*', MUST_EXIST);
        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $subordinate->supervisorid]);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $subordinate->userid, 'deleted' => 0], '*', MUST_EXIST);

        if ($framework->uimode == framework::UIMODE_TEAMS) {
            if (property_exists($data, 'teamposition')) {
                if (trim($data->teamposition ?? '') !== '') {
                    $teamposition = $data->teamposition;
                } else {
                    $teamposition = null;
                }
                $DB->set_field('tool_murelation_subordinate', 'teamposition', $teamposition, ['id' => $subordinate->id]);
                $subordinate = $DB->get_record('tool_murelation_subordinate', ['id' => $data->id], '*', MUST_EXIST);
            }
        }

        return $subordinate;
    }

    /**
     * Remove subordinate from supervisor position.
     *
     * @param int $subordinateid
     * @return void
     */
    public static function delete(int $subordinateid): void {
        global $DB;

        $sub = $DB->get_record('tool_murelation_subordinate', ['id' => $subordinateid]);
        if (!$sub) {
            return;
        }

        $framework = $DB->get_record('tool_murelation_framework', ['id' => $sub->frameworkid], '*', MUST_EXIST);
        if ($framework->uimode == framework::UIMODE_SUPERVISORS) {
            throw new \core\exception\coding_exception('delete supervisor instead of subordinate in Supervisors mode');
        }

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('tool_murelation_subordinate', ['id' => $sub->id]);

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $sub->supervisorid]);
        if ($supervisor) {
            supervisor::sync_roles($supervisor->id);
            if ($supervisor->teamcohortid) {
                supervisor::sync_team_cohorts($supervisor->id);
            }
        }

        $trans->allow_commit();
    }

    /**
     * Deal with deleted user.
     *
     * @param int $userid
     * @return void
     */
    public static function user_deleted(int $userid): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);
        if ($user && !$user->deleted) {
            debugging('User is NOT deleted!', DEBUG_DEVELOPER);
            return;
        }
        unset($user);

        $sql = new sql(
            "SELECT sub.id, sub.supervisorid, f.uimode
               FROM {tool_murelation_subordinate} sub
               JOIN {tool_murelation_framework} f ON f.id = sub.frameworkid
              WHERE sub.userid = :userid
           ORDER BY sub.id ASC",
            ['userid' => $userid]
        );
        $subs = $DB->get_records_sql($sql->sql, $sql->params);
        foreach ($subs as $sub) {
            if ($sub->uimode == framework::UIMODE_TEAMS) {
                self::delete($sub->id);
            } else if ($sub->uimode == framework::UIMODE_SUPERVISORS) {
                supervisor::delete($sub->supervisorid);
            }
        }
    }

    /**
     * Periodic data cleanup.
     *
     * @return void
     */
    public static function cron_cleanup(): void {
        global $DB;

        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                  JOIN {tool_murelation_subordinate} sub ON sub.userid = u.id
                 WHERE u.deleted = 1
              ORDER BY u.id ASC";
        $userids = $DB->get_fieldset_sql($sql);
        foreach ($userids as $userid) {
            self::user_deleted($userid);
        }
    }
}
