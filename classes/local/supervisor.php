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
use coding_exception;
use tool_mulib\local\mulib;

/**
 * Supervisor/team helper class.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class supervisor {
    /**
     * Create new supervisor position or team.
     *
     * @param stdClass $data
     * @return stdClass supervisor record
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $framework = $DB->get_record('tool_murelation_framework', ['id' => $data->frameworkid], '*', MUST_EXIST);

        $subusers = [];

        $record = new stdClass();
        $record->frameworkid = $framework->id;

        if ($framework->uimode == framework::UIMODE_SUPERVISORS) {
            $user = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0, 'confirmed' => 1]);
            if (!$user) {
                throw new \invalid_parameter_exception('supervisor userid is required');
            }
            if (!$data->subuserid) {
                throw new \invalid_parameter_exception('subordinate subuserid is required');
            }
            if (isset($data->subuserids)) {
                debugging('subuserids data property is not meant to be use in Supervisors mode', DEBUG_DEVELOPER);
            }
            $subuser = $DB->get_record('user', ['id' => $data->subuserid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);
            if (mulib::is_mutenancy_active()) {
                if (isset($subuser->tenantid)) {
                    $record->tenantid = $subuser->tenantid;
                } else {
                    $record->tenantid = null;
                }
            }
            $record->userid = $user->id;
            $record->teamname = null;
            $record->teamidnumber = null;
            $record->maxsubordinates = 1;
            $record->supmanaged = 0;
            $subusers[] = $subuser;
        } else if ($framework->uimode == framework::UIMODE_TEAMS) {
            if (mulib::is_mutenancy_active()) {
                if (isset($data->tenantid)) {
                    $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $data->tenantid], '*', MUST_EXIST);
                    $record->tenantid = $tenant->id;
                } else {
                    $record->tenantid = null;
                }
            }
            if (!empty($data->userid)) {
                $user = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);
                $record->userid = $user->id;
            } else {
                $record->userid = null;
            }
            $record->supmanaged = (int)(bool)($data->supmanaged ?? 0);

            if (trim($data->teamname ?? '') === '') {
                throw new \invalid_parameter_exception('teamname is required');
            }
            $record->teamname = $data->teamname;

            if (trim($data->teamidnumber ?? '') === '') {
                $record->teamidnumber = null;
            } else {
                if ($DB->record_exists_select('tool_murelation_supervisor', "LOWER(teamidnumber) = LOWER(?)", [$data->teamidnumber])) {
                    throw new \invalid_parameter_exception('teamidnumber must be unique');
                }
                $record->teamidnumber = $data->teamidnumber;
            }

            if (isset($data->maxsubordinates) && $data->maxsubordinates > 0) {
                $record->maxsubordinates = (int)$data->maxsubordinates;
            } else {
                $record->maxsubordinates = null;
            }

            if (isset($data->subuserids)) {
                foreach ($data->subuserids as $subuserid) {
                    $subusers[] = $DB->get_record('user', ['id' => $subuserid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);
                }
            }
            if (isset($data->subuserid)) {
                debugging('subuserid data property is not meant to be use in Teams mode', DEBUG_DEVELOPER);
            }
        } else {
            throw new coding_exception('invalid framework uimode');
        }

        $trans = $DB->start_delegated_transaction();

        $record->timecreated = time();
        $record->id = $DB->insert_record('tool_murelation_supervisor', $record);

        foreach ($subusers as $subuser) {
            $d = (object)[
                'supervisorid' => $record->id,
                'userid' => $subuser->id,
                'teamposition' => $data->teamposition ?? '',
            ];
            subordinate::create($d);
        }

        if ($framework->uimode == framework::UIMODE_TEAMS) {
            if (!empty($data->teamcohortcreate)) {
                if (property_exists($data, 'teamcohortname')) {
                    $teamcohortname = $data->teamcohortname;
                } else {
                    $teamcohortname = $record->teamname;
                }
                self::team_cohort_create((object)[
                    'id' => $record->id,
                    'name' => $teamcohortname,
                ]);
                self::sync_team_cohorts($record->id);
            }
        }

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        return $supervisor;
    }

    /**
     * Update supervisor position.
     *
     * @param stdClass $data
     * @return stdClass supervisor record
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $oldsupervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $data->id], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $oldsupervisor->frameworkid], '*', MUST_EXIST);

        $record = new stdClass();
        $record->id = $oldsupervisor->id;

        if ($framework->uimode == framework::UIMODE_SUPERVISORS) {
            if (property_exists($data, 'userid') && $data->userid != $oldsupervisor->userid) {
                $user = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0, 'confirmed' => 1]);
                if (!$user) {
                    throw new \invalid_parameter_exception('supervisor userid is required');
                }
                $record->userid = $user->id;
            }
            $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $oldsupervisor->id]);
            if (count($subordinates) > 1) {
                throw new coding_exception('Invalid subordinates detected for supervisor: ' . $oldsupervisor->id);
            } else if (!$subordinates) {
                throw new coding_exception('Missing subordinate detected for supervisor: ' . $oldsupervisor->id);
            }
            $subordinate = reset($subordinates);
            $subuser = $DB->get_record('user', ['id' => $subordinate->userid, 'deleted' => 0]);
            if (!$subuser) {
                throw new coding_exception('Invalid subordinate user detected for supervisor: ' . $oldsupervisor->id);
            }
            if (mulib::is_mutenancy_active()) {
                // Fix tenant if user moved and event missed.
                if ($oldsupervisor->tenantid !== $subuser->tenantid) {
                    $record->tenantid = $subuser->tenantid;
                }
            }

            $record->teamname = null;
            $record->teamidnumber = null;
            $record->maxsubordinates = 1;
            $record->supmanaged = 0;
        } else if ($framework->uimode == framework::UIMODE_TEAMS) {
            // Do not allow changes of tenantid of teams here!
            if (property_exists($data, 'userid') && $data->userid != $oldsupervisor->userid) {
                if (!empty($data->userid)) {
                    $user = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);
                    $record->userid = $user->id;
                } else {
                    $record->userid = null;
                }
            }
            if (property_exists($data, 'supmanaged')) {
                $record->supmanaged = (int)(bool)$data->supmanaged;
            }

            if (property_exists($data, 'teamname')) {
                if (trim($data->teamname ?? '') === '') {
                    throw new \invalid_parameter_exception('teamname is required');
                }
                $record->teamname = $data->teamname;
            }

            if (property_exists($data, 'teamidnumber')) {
                if (trim($data->teamidnumber ?? '') === '') {
                    $record->teamidnumber = null;
                } else {
                    $select = "LOWER(teamidnumber) = LOWER(?) AND id <> ?";
                    $params = [$data->teamidnumber, $oldsupervisor->id];
                    if ($DB->record_exists_select('tool_murelation_supervisor', $select, $params)) {
                        throw new \invalid_parameter_exception('teamidnumber must be unique');
                    }
                    $record->teamidnumber = $data->teamidnumber;
                }
            }

            if (property_exists($data, 'maxsubordinates')) {
                if (isset($data->maxsubordinates) && $data->maxsubordinates > 0) {
                    $record->maxsubordinates = (int)$data->maxsubordinates;
                } else {
                    $record->maxsubordinates = null;
                }
            }
        } else {
            throw new coding_exception('invalid framework uimode');
        }

        $trans = $DB->start_delegated_transaction();

        $cohort = false;
        $syncmembers = false;
        if ($oldsupervisor->teamcohortid) {
            $syncmembers = true;
            $cohort = $DB->get_record('cohort', ['id' => $oldsupervisor->teamcohortid]);
            if (!$cohort) {
                $record->teamcohortid = null;
            }
        }

        if (count((array)$record) > 1) {
            $DB->update_record('tool_murelation_supervisor', $record);
        }

        self::sync_roles($record->id);

        if ($cohort) {
            if (property_exists($data, 'teamcohortname')) {
                self::team_cohort_update((object)[
                    'id' => $record->id,
                    'name' => $data->teamcohortname,
                ]);
            }
        } else if (!empty($data->teamcohortcreate)) {
            if (property_exists($data, 'teamcohortname')) {
                $teamcohortname = $data->teamcohortname;
            } else {
                $teamcohortname = $record->teamname;
            }
            self::team_cohort_create((object)[
                'id' => $record->id,
                'name' => $teamcohortname,
            ]);
            $syncmembers = true;
        }

        if ($syncmembers) {
            self::sync_team_cohorts($record->id);
        }

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        return $supervisor;
    }

    /**
     * Dismiss user from team supervisor position.
     *
     * @param int $supervisorid
     * @return stdClass supervisor record
     */
    public static function vacate(int $supervisorid): stdClass {
        global $DB;

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);

        if ($framework->uimode == framework::UIMODE_SUPERVISORS) {
            throw new coding_exception('supervisor must be assigned in supervisors mode');
        }

        if ($supervisor->userid) {
            $DB->set_field('tool_murelation_supervisor', 'userid', null, ['id' => $supervisor->id]);
            $supervisor->userid = null;
        }

        self::sync_roles($supervisor->id);

        return $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor->id], '*', MUST_EXIST);
    }

    /**
     * Delete supervisor position.
     *
     * @param int $supervisorid
     */
    public static function delete(int $supervisorid): void {
        global $DB;

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid]);
        if (!$supervisor) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid]);

        $DB->delete_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id]);
        $DB->delete_records('tool_murelation_supervisor', ['id' => $supervisor->id]);

        if ($framework && $framework->uimode == framework::UIMODE_TEAMS) {
            self::team_cohort_delete($supervisor->id);
        }

        role_unassign_all(['component' => 'tool_murelation', 'itemid' => $supervisor->id]);

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
            "SELECT sup.id, f.uimode
               FROM {tool_murelation_supervisor} sup
               JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
              WHERE sup.userid = :userid
           ORDER BY sup.id ASC",
            ['userid' => $userid]
        );
        $sups = $DB->get_records_sql($sql->sql, $sql->params);
        foreach ($sups as $sup) {
            if ($sup->uimode == framework::UIMODE_TEAMS) {
                self::vacate($sup->id);
            } else if ($sup->uimode == framework::UIMODE_SUPERVISORS) {
                self::delete($sup->id);
            }
        }
    }

    /**
     * Sync roles of all supervisor positions or just one.
     *
     * @param int|null $supervisorid
     * @return void
     */
    public static function sync_roles(?int $supervisorid = null): void {
        global $DB;

        // Add missing roles.
        $sql = new sql(
            "SELECT r.id AS roleid, sup.userid, ctx.id AS contextid, sup.id AS itemid
               FROM {tool_murelation_supervisor} sup
               JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
               JOIN {role} r ON r.id = f.supervisorroleid
               JOIN {tool_murelation_subordinate} sub ON sub.supervisorid = sup.id
               JOIN {user} usup ON usup.id = sup.userid AND usup.deleted = 0 AND usup.confirmed = 1
               JOIN {user} usub ON usub.id = sub.userid AND usub.deleted = 0 AND usub.confirmed = 1
               JOIN {context} ctx ON ctx.instanceid = sub.userid AND ctx.contextlevel = :userlevel
          LEFT JOIN {role_assignments} ra ON ra.roleid = r.id AND ra.userid = sup.userid AND ra.contextid = ctx.id
                                             AND ra.itemid = sup.id AND ra.component = 'tool_murelation'
              WHERE ra.id IS NULL /* onesupervisor */
           ORDER BY sup.id ASC, sub.id ASC",
            ['userlevel' => CONTEXT_USER]
        );
        if ($supervisorid) {
            $sql->replace_comment('onesupervisor', "AND sup.id = ?", [$supervisorid]);
        }
        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);
        foreach ($rs as $ra) {
            role_assign($ra->roleid, $ra->userid, $ra->contextid, 'tool_murelation', $ra->itemid);
        }
        $rs->close();

        // Remove leftover roles.
        $sql = new sql(
            "SELECT ra.*
               FROM {role_assignments} ra
              WHERE ra.component = 'tool_murelation' /* onesupervisor */ AND NOT EXISTS (
                     SELECT 'x'
                       FROM {tool_murelation_supervisor} sup
                       JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
                       JOIN {role} r ON r.id = f.supervisorroleid
                       JOIN {tool_murelation_subordinate} sub ON sub.supervisorid = sup.id
                       JOIN {user} usup ON usup.id = sup.userid AND usup.deleted = 0 AND usup.confirmed = 1
                       JOIN {user} usub ON usub.id = sub.userid AND usub.deleted = 0 AND usub.confirmed = 1
                       JOIN {context} ctx ON ctx.instanceid = sub.userid AND ctx.contextlevel = :userlevel
                      WHERE ra.roleid = r.id AND ra.userid = sup.userid AND ra.contextid = ctx.id AND ra.itemid = sup.id
                    )
           ORDER BY ra.id ASC",
            ['userlevel' => CONTEXT_USER]
        );
        if ($supervisorid) {
            $sql->replace_comment('onesupervisor', "AND ra.itemid = ?", [$supervisorid]);
        }
        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);
        foreach ($rs as $ra) {
            role_unassign($ra->roleid, $ra->userid, $ra->contextid, $ra->component, $ra->itemid);
        }
        $rs->close();
    }

    /**
     * Create team cohort.
     *
     * @param stdClass $data
     * @return stdClass cohort record
     */
    public static function team_cohort_create(stdClass $data): stdClass {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $data->id], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid]);

        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new coding_exception('team cohort can be created only in teams mode');
        }

        if ($supervisor->teamcohortid) {
            if ($DB->record_exists('cohort', ['id' => $supervisor->teamcohortid])) {
                throw new coding_exception('Team cohort already exists');
            }
        }
        if (trim($data->name ?? '') === '') {
            throw new \core\exception\invalid_parameter_exception('team cohort name is required');
        }

        if (mulib::is_mutenancy_active() && $supervisor->tenantid) {
            $tenant = \tool_mutenancy\local\tenant::fetch($supervisor->tenantid);
            $context = \context_coursecat::instance($tenant->categoryid);
        } else {
            $context = \context_system::instance();
        }

        $record = new stdClass();
        $record->contextid = $context->id;
        $record->component = 'tool_murelation';
        $record->name = $data->name;
        $record->description = $data->description ?? '';
        $record->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
        $record->visible = $data->visible ?? 0;

        $cohortid = cohort_add_cohort($record);
        $DB->set_field('tool_murelation_supervisor', 'teamcohortid', $cohortid, ['id' => $supervisor->id]);

        return $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
    }

    /**
     * Update team cohort.
     *
     * @param stdClass $data
     * @return stdClass cohort record
     */
    public static function team_cohort_update(stdClass $data): stdClass {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $data->id], '*', MUST_EXIST);
        if (!$supervisor->teamcohortid) {
            throw new coding_exception('Team cohort was not created yet');
        }

        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid]);
        if (!$cohort) {
            throw new coding_exception('Team cohort was deleted');
        }

        $update = false;

        if (property_exists($data, 'name') && $cohort->name !== $data->name) {
            $cohort->name = $data->name;
            $update = true;
        }
        if (property_exists($data, 'description') && $cohort->description !== $data->description) {
            $cohort->description = $data->description;
            $update = true;
        }
        if (property_exists($data, 'descriptionformat') && $cohort->descriptionformat != $data->descriptionformat) {
            $cohort->descriptionformat = $data->descriptionformat;
            $update = true;
        }
        if (property_exists($data, 'visible') && $cohort->visible != $data->visible) {
            $cohort->visible = (int)(bool)$data->visible;
            $update = true;
        }

        if ($update) {
            cohort_update_cohort((object)$cohort);
        }

        return $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
    }

    /**
     * Delete team cohort.
     *
     * @param int $supervisorid
     */
    public static function team_cohort_delete(int $supervisorid): void {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid]);
        if (!$supervisor || !$supervisor->teamcohortid) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_murelation_supervisor', 'teamcohortid', null, ['id' => $supervisor->id]);

        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid, 'component' => 'tool_murelation']);
        if ($cohort) {
            cohort_delete_cohort($cohort);
        }

        $trans->allow_commit();
    }

    /**
     * Sync team cohort membership and delete orphaned team cohorts.
     *
     * NOTE: team cohorts are not created automatically.
     *
     * @param int|null $supervisorid one supervisor only (to improve performance)
     * @return void
     */
    public static function sync_team_cohorts(?int $supervisorid = null): void {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        if (!$supervisorid) {
            // Delete orphaned team cohorts, there should not be any.
            $sql = new sql(
                "SELECT c.*
                   FROM {cohort} c
              LEFT JOIN {tool_murelation_supervisor} sup ON sup.teamcohortid = c.id
                  WHERE c.component = 'tool_murelation' AND sup.id IS NULL
               ORDER BY c.id ASC"
            );
            $rs = $DB->get_recordset_sql($sql->sql);
            foreach ($rs as $c) {
                cohort_delete_cohort($c);
            }
            $rs->close();
        }

        $supervisor = false;
        if ($supervisorid) {
            $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid]);
            if (!$supervisor || !$supervisor->teamcohortid) {
                return;
            }
        }

        // Remove obsolete members.
        $sql = new sql(
            "SELECT cm.cohortid, cm.userid
               FROM {cohort_members} cm
               JOIN {cohort} c ON c.id = cm.cohortid AND c.component = 'tool_murelation' /* supjoin */
              WHERE NOT EXISTS (
                          SELECT 'x'
                            FROM {tool_murelation_subordinate} sub
                            JOIN {tool_murelation_supervisor} sup ON sup.id = sub.supervisorid
                            JOIN {user} u ON u.id = sub.userid AND u.deleted = 0
                           WHERE sub.userid = cm.userid AND sup.teamcohortid = c.id
                                 /* supwhere */
                    )
           ORDER BY cm.cohortid ASC, cm.userid ASC"
        );
        if ($supervisor) {
            $sql->replace_comment('supjoin', "AND c.id = ?", [$supervisor->teamcohortid]);
            $sql->replace_comment('supwhere', "AND sup.id = ?", [$supervisor->id]);
        }
        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);
        foreach ($rs as $cm) {
            cohort_remove_member($cm->cohortid, $cm->userid);
        }
        $rs->close();

        // Add cohort missing cohort members.
        $sql = new sql(
            "SELECT c.id AS cohortid, u.id AS userid
               FROM {cohort} c
               JOIN {tool_murelation_supervisor} sup ON sup.teamcohortid = c.id
               JOIN {tool_murelation_subordinate} sub ON sub.supervisorid = sup.id
               JOIN {user} u ON u.id = sub.userid AND u.deleted = 0
          LEFT JOIN {cohort_members} cm ON cm.cohortid = c.id AND cm.userid = u.id
              WHERE c.component = 'tool_murelation'
                    AND cm.id IS NULL
                    /* supwhere */
           ORDER BY c.id ASC, u.id ASC"
        );
        if ($supervisorid) {
            $sql->replace_comment('supwhere', "AND sup.id = ?", [$supervisorid]);
        }
        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);
        foreach ($rs as $cm) {
            cohort_add_member($cm->cohortid, $cm->userid);
        }
        $rs->close();
    }

    /**
     * Periodic data cleanup.
     *
     * @return void
     */
    public static function cron_cleanup(): void {
        global $DB;

        // Deal with skipped user deleted events.
        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                  JOIN {tool_murelation_supervisor} sup ON sup.userid = u.id
                 WHERE u.deleted = 1
              ORDER BY u.id ASC";
        $userids = $DB->get_fieldset_sql($sql);
        foreach ($userids as $userid) {
            self::user_deleted($userid);
        }

        self::sync_roles();
        self::sync_team_cohorts();
    }
}
