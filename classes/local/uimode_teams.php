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
use core\exception\coding_exception;
use tool_mulib\local\mulib;
use core\exception\invalid_parameter_exception;

/**
 * Teams UI mode helper.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class uimode_teams {
    /**
     * Create a new team.
     *
     * @param stdClass $data
     * @return stdClass supervisor record
     */
    public static function team_create(stdClass $data): stdClass {
        global $DB;
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $data->frameworkid], '*', MUST_EXIST);
        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new coding_exception('Framework is not compatible with Teams mode');
        }

        return supervisor::create($data);
    }

    /**
     * Update existing team.
     *
     * @param stdClass $data
     * @return stdClass supervisor record
     */
    public static function team_update(stdClass $data): stdClass {
        global $DB;
        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $data->id], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new coding_exception('Framework is not compatible with Teams mode');
        }

        return supervisor::update($data);
    }

    /**
     * Update existing team.
     *
     * @param int $id
     * @return void
     */
    public static function team_delete(int $id): void {
        global $DB;
        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $id]);
        if (!$supervisor) {
            return;
        }
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new coding_exception('Framework is not compatible with Teams mode');
        }

        supervisor::team_cohort_delete($supervisor->id);
        supervisor::delete($supervisor->id);
    }

    /**
     * Add team members.
     *
     * @param stdClass $data
     * @return array subordinate records
     */
    public static function members_create(stdClass $data): array {
        global $DB;

        if (empty($data->supervisorid)) {
            throw new invalid_parameter_exception('supervisorid is required');
        }
        $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $data->supervisorid], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new coding_exception('Framework is not compatible with Teams mode');
        }

        $d = (object)[
            'supervisorid' => $supervisor->id,
            'teamposition' => $data->teamposition ?? '',
        ];

        $result = [];
        foreach ($data->subuserids as $userid) {
            $d->userid = $userid;
            $subordinate = subordinate::create($d);
            $result[$subordinate->id] = $subordinate;
        }

        return $result;
    }

    /**
     * Update team member.
     *
     * @param stdClass $data
     * @return stdClass subordinate record
     */
    public static function member_update(stdClass $data): stdClass {
        return subordinate::update($data);
    }

    /**
     * Remove team member.
     *
     * @param int $subordinateid
     * @return void
     */
    public static function member_delete(int $subordinateid): void {
        subordinate::delete($subordinateid);
    }

    /**
     * Returns context for team access control.
     *
     * @param stdClass $framework
     * @param stdClass $supervisor
     * @return \context either system or tenant context for Teams mode; user context for Supervisors mode.
     */
    public static function get_team_context(stdClass $framework, stdClass $supervisor): \context {
        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new invalid_parameter_exception('Framework is not compatible with Teams mode');
        }
        if ($supervisor->frameworkid != $framework->id) {
            throw new coding_exception('Invalid Teams framework');
        }

        if ($supervisor->tenantid && mulib::is_mutenancy_active()) {
            return \context_tenant::instance($supervisor->tenantid);
        } else {
            return \context_system::instance();
        }
    }

    /**
     * Can current user create new supervisor in framework?
     *
     * @param stdClass $framework framework record if available
     * @param \context $context system or tenant context
     * @return bool
     */
    public static function can_create_team(stdClass $framework, \context $context): bool {
        global $DB, $USER;

        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new invalid_parameter_exception('Framework is not compatible with Teams mode');
        }

        $tenantid = null;
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            if (!mulib::is_mutenancy_active() || $context->contextlevel !== CONTEXT_TENANT) {
                throw new coding_exception('Invalid context for team creation');
            }
            $tenantid = $context->tenantid;
        }

        if (!has_capability('tool/murelation:viewpositions', $context)) {
            return false;
        }
        if (!has_capability('tool/murelation:managepositions', $context)) {
            return false;
        }

        if ($tenantid && !$framework->alltenants) {
            if (!$DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework->id, 'tenantid' => $tenantid])) {
                return false;
            }
        }

        if (!$framework->managecohortid) {
            return true;
        }

        if (is_siteadmin()) {
            // Cohort manager restriction is ignored for admins.
            return true;
        }

        return $DB->record_exists('cohort_members', ['cohortid' => $framework->managecohortid, 'userid' => $USER->id]);
    }

    /**
     * Can current user update/delete team and manage team supervisor and team members?
     *
     * @param stdClass $framework framework record if available
     * @param stdClass $supervisor team record
     * @return bool
     */
    public static function can_update_team(stdClass $framework, stdClass $supervisor): bool {
        global $DB, $USER;

        if ($framework->uimode != framework::UIMODE_TEAMS) {
            throw new coding_exception('Framework is not compatible with Teams mode');
        }

        if ($framework->id != $supervisor->frameworkid) {
            throw new invalid_parameter_exception('framework does not match team');
        }

        $context = self::get_team_context($framework, $supervisor);

        if (!has_capability('tool/murelation:viewpositions', $context)) {
            return false;
        }
        if (!has_capability('tool/murelation:managepositions', $context)) {
            return false;
        }

        if (!$framework->managecohortid) {
            return true;
        }

        if (is_siteadmin()) {
            // Cohort restriction for management is ignored for admins.
            return true;
        }

        return $DB->record_exists('cohort_members', ['cohortid' => $framework->managecohortid, 'userid' => $USER->id]);
    }

    /**
     * Can current user add and remove team members?
     *
     * @param stdClass $framework framework record if available
     * @param stdClass $supervisor team record
     * @return bool
     */
    public static function can_manage_members(stdClass $framework, stdClass $supervisor): bool {
        global $USER;

        // If user can update team, they can update members too.
        if (self::can_update_team($framework, $supervisor)) {
            return true;
        }

        if ($supervisor->supmanaged && $USER->id == $supervisor->userid) {
            $context = self::get_team_context($framework, $supervisor);
            return has_capability('tool/murelation:viewpositions', $context);
        }

        return false;
    }

    /**
     * Returns list of all visible teams for given subordinate user.
     *
     * @param stdClass $subuser
     * @param stdClass|null $course
     * @return array array of supervisor records (aka teams)
     */
    public static function get_visible_teams(stdClass $subuser, ?stdClass $course = null): array {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            // Guests cannot see any teams for privacy reasons.
            return [];
        }

        $iscourseteacher = false;
        $coursecontext = false;
        if ($course && $course->id != SITEID) {
            $coursecontext = \context_course::instance($course->id);
            $iscourseteacher = has_capability('moodle/user:viewhiddendetails', $coursecontext);
        }

        $syscontextcontext = \context_system::instance();

        $sql = new sql(
            "SELECT sup.*, f.visibility
               FROM {tool_murelation_framework} f
               JOIN {tool_murelation_subordinate} sub ON sub.frameworkid = f.id AND sub.userid = :subuserid
               JOIN {tool_murelation_supervisor} sup ON sup.frameworkid = f.id AND sup.id = sub.supervisorid
              WHERE f.uimode = :teamuimode AND f.visibility <> 0
                    /* tenant */
           ORDER BY sup.teamname ASC",
            [
                'subuserid' => $subuser->id,
                'teamuimode' => framework::UIMODE_TEAMS,
            ]
        );
        if (mulib::is_mutenancy_active() && $subuser->tenantid) {
            $sql = $sql->replace_comment(
                'tenant',
                "AND (f.alltenants = 1 OR EXISTS (
                    SELECT 'x'
                      FROM {tool_murelation_tenant_allow} ta
                     WHERE ta.frameworkid = f.id AND ta.tenantid = ?
                    ))",
                [$subuser->tenantid]
            );
        }
        $teams = $DB->get_records_sql($sql->sql, $sql->params);

        foreach ($teams as $k => $team) {
            if (mulib::is_mutenancy_active() && $team->tenantid) {
                $context = \context_tenant::instance($team->tenantid);
            } else {
                $context = $syscontextcontext;
            }
            $isteacher = $iscourseteacher;
            if ($isteacher && mulib::is_mutenancy_active() && $coursecontext && $coursecontext->tenantid) {
                // Do not allow peaking into global and other tenant teams from tenant courses.
                if ($coursecontext->tenantid != $team->tenantid) {
                    $isteacher = false;
                }
            }
            $ismanager = false;
            if (has_capability('tool/murelation:viewpositions', $context)) {
                $ismanager = true;
            } else if (has_capability('tool/murelation:managepositions', $context)) {
                $ismanager = true;
            }
            $visible = false;
            if ($team->visibility == framework::VISIBILITY_EVERYBODY) {
                $visible = true;
            } else if ($team->visibility == framework::VISIBILITY_SUBORDINATES) {
                if ($ismanager || $isteacher || $USER->id == $team->userid || $USER->id == $subuser->id) {
                    $visible = true;
                }
            } else if ($team->visibility == framework::VISIBILITY_SUPERVISORS) {
                if ($ismanager || $isteacher || $USER->id == $team->userid) {
                    $visible = true;
                }
            } else if ($team->visibility == framework::VISIBILITY_MANAGERS) {
                if ($ismanager || $isteacher) {
                    $visible = true;
                }
            }
            if (!$visible) {
                unset($teams[$k]);
            }
        }

        return $teams;
    }

    /**
     * Returns list of all visible teams for given supervisor.
     *
     * @param stdClass $supuser
     * @return array array of supervisor records (aka teams)
     */
    public static function get_supervised_teams(stdClass $supuser): array {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            // Guests cannot see any teams for privacy reasons.
            return [];
        }

        if ($supuser->id != $USER->id) {
            if (mulib::is_mutenancy_active() && $supuser->tenantid) {
                $context = \context_tenant::instance($supuser->tenantid);
            } else {
                $context = \context_system::instance();
            }
            if (!has_capability('tool/murelation:viewpositions', $context)) {
                return [];
            }
        }

        $sql = new sql(
            "SELECT sup.*, f.visibility
               FROM {tool_murelation_framework} f
               JOIN {tool_murelation_supervisor} sup ON sup.frameworkid = f.id
              WHERE f.uimode = :teamuimode AND f.visibility <> 0 AND sup.userid = :supuserid
           ORDER BY sup.teamname ASC",
            [
                'teamuimode' => framework::UIMODE_TEAMS,
                'supuserid' => $supuser->id,
            ]
        );

        return $DB->get_records_sql($sql->sql, $sql->params);
    }

    /**
     * Periodic data cleanup.
     *
     * @return void
     */
    public static function cron_cleanup(): void {
        global $DB;

        // NOTE: do not delete positions for frameworks not allowed in tenant, those must be fixed manually.

        if (mulib::is_mutenancy_active()) {
            // Vacate supervisors with conflicting tenant ids,
            // this is done only in cron to prevent problems when undoing moving users between tenants.
            $sql = new sql(
                "SELECT sup.id
                   FROM {tool_murelation_supervisor} sup
                   JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
                   JOIN {user} u ON u.id = sup.userid
                  WHERE f.uimode = :uimode
                        AND u.tenantid IS NOT NULL AND sup.tenantid IS NOT NULL AND u.tenantid <> sup.tenantid
               ORDER BY sup.id ASC",
                ['uimode' => framework::UIMODE_TEAMS]
            );
            $supervisorids = $DB->get_fieldset_sql($sql->sql, $sql->params);
            foreach ($supervisorids as $supervisorid) {
                supervisor::vacate($supervisorid);
            }

            // Delete subordinates with conflicting tenant ids,
            // this is done only in cron to prevent problems when undoing moving users between tenants.
            $sql = new sql(
                "SELECT sub.id
                   FROM {tool_murelation_subordinate} sub
                   JOIN {user} u ON u.id = sub.userid
                   JOIN {tool_murelation_framework} f ON f.id = sub.frameworkid
                   JOIN {tool_murelation_supervisor} sup ON sup.id = sub.supervisorid
                  WHERE f.uimode = :uimode
                        AND u.tenantid IS NOT NULL AND sup.tenantid IS NOT NULL AND u.tenantid <> sup.tenantid
               ORDER BY sup.id ASC",
                ['uimode' => framework::UIMODE_TEAMS]
            );
            $subordinateids = $DB->get_fieldset_sql($sql->sql, $sql->params);
            foreach ($subordinateids as $subordinateid) {
                subordinate::delete($subordinateid);
            }
        }
    }
}
