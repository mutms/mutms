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
use core\exception\invalid_parameter_exception;
use tool_mulib\local\mulib;

/**
 * Supervisors UI mode helper.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class uimode_supervisors {
    /**
     * Create or update supervisor.
     *
     * @param stdClass $data
     * @return stdClass supervisor record
     */
    public static function supervisor_edit(stdClass $data): stdClass {
        global $DB;

        $framework = $DB->get_record('tool_murelation_framework', ['id' => $data->frameworkid], '*', MUST_EXIST);
        $subuser = $DB->get_record('user', ['id' => $data->subuserid, 'deleted' => 0], '*', MUST_EXIST);
        $supuser = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0], '*', MUST_EXIST);

        if ($framework->uimode != framework::UIMODE_SUPERVISORS) {
            throw new coding_exception('Framework is not compatible with Supervisors mode');
        }

        if ($subuser->id == $supuser->id) {
            throw new invalid_parameter_exception('Supervisors cannot supervise themselves');
        }

        $subordinate = $DB->get_record('tool_murelation_subordinate', ['frameworkid' => $framework->id, 'userid' => $subuser->id]);

        $trans = $DB->start_delegated_transaction();

        if ($subordinate) {
            $supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $subordinate->supervisorid], '*', MUST_EXIST);
            if ($supervisor->frameworkid != $framework->id) {
                throw new coding_exception('Framework mismatch');
            }
            $d = (object)[
                'id' => $supervisor->id,
                'userid' => $supuser->id,
            ];
            $supervisor = supervisor::update($d);
        } else {
            $d = (object)[
                'frameworkid' => $framework->id,
                'userid' => $supuser->id,
                'subuserid' => $subuser->id,
            ];
            $supervisor = supervisor::create($d);
        }

        $trans->allow_commit();

        return $supervisor;
    }

    /**
     * Bulk create supervisor positions.
     *
     * @param stdClass $data
     * @return array of supervisors
     */
    public static function bulk_create(stdClass $data): array {
        global $DB;

        $result = [];
        $trans = $DB->start_delegated_transaction();
        foreach ($data->subuserids as $subuserid) {
            $d = (object)[
                'frameworkid' => $data->frameworkid,
                'userid' => $data->supuserid,
                'subuserid' => $subuserid,
            ];
            $supervisor = self::supervisor_edit($d);
            $result[$supervisor->id] = $supervisor;
        }

        $trans->allow_commit();

        return $result;
    }

    /**
     * Can current user add and remove supervisors of given subordinate user?
     *
     * @param stdClass $framework framework record if available
     * @param int $subuserid team record
     * @return bool
     */
    public static function can_manage_subordinate(stdClass $framework, int $subuserid): bool {
        global $DB, $USER;

        if ($framework->uimode != framework::UIMODE_SUPERVISORS) {
            throw new invalid_parameter_exception('Framework is not compatible with Supervisors mode');
        }

        $usercontext = \context_user::instance($subuserid, IGNORE_MISSING);
        if (!$usercontext) {
            return false;
        }

        if (!has_capability('tool/murelation:managepositions', $usercontext)) {
            return false;
        }

        $subordinate = $DB->get_record('tool_murelation_subordinate', ['userid' => $subuserid, 'frameworkid' => $framework->id]);
        if (!$subordinate) {
            if ($framework->subordinatecohortid) {
                if (!$DB->record_exists('cohort_members', ['cohortid' => $framework->subordinatecohortid, 'userid' => $subuserid])) {
                    // User cannot be added as subordinate in framework.
                    return false;
                }
            }

            if (mulib::is_mutenancy_active()) {
                if ($usercontext->tenantid && !$framework->alltenants) {
                    if (!$DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework->id, 'tenantid' => $usercontext->tenantid])) {
                        return false;
                    }
                }
            }
        }

        if (is_siteadmin()) {
            // Cohort manager restriction for management is ignored for admins.
            return true;
        }

        if (!$framework->managecohortid) {
            return true;
        }

        return $DB->record_exists('cohort_members', ['cohortid' => $framework->managecohortid, 'userid' => $USER->id]);
    }

    /**
     * Can current user bulk create subordinates?
     *
     * @param stdClass $framework framework record if available
     * @param \context $context system context only for now
     * @return bool
     */
    public static function can_bulk_create(stdClass $framework, \context $context): bool {
        if ($framework->uimode != framework::UIMODE_SUPERVISORS) {
            throw new invalid_parameter_exception('Framework is not compatible with Supervisors mode');
        }

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            if (!mulib::is_mutenancy_active() || $context->contextlevel !== CONTEXT_TENANT) {
                debugging('Invalid context for team creation', DEBUG_DEVELOPER);
                return false;
            }
        }

        if (!has_capability('tool/murelation:viewpositions', $context)) {
            return false;
        }
        if (!has_capability('tool/murelation:managepositions', $context)) {
            return false;
        }

        return true;
    }

    /**
     * Returns list of all frameworks for given subordinate user the current user may see,
     * empty positions are included only if current user may add new supervisors.
     *
     * @param stdClass $subuser
     * @param stdClass|null $course
     * @return array framework records with extra 'supuserid' property set if supervisor assigned
     */
    public static function get_visible_frameworks(stdClass $subuser, ?stdClass $course = null): array {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            // Guests cannot see any supervisors or teams for privacy reasons.
            return [];
        }

        $usercontext = \context_user::instance($subuser->id);
        $isteacher = false;
        if ($course && $course->id != SITEID) {
            $coursecontext = \context_course::instance($course->id);
            $isteacher = has_capability('moodle/user:viewhiddendetails', $coursecontext);
        }

        $canviewpositions = has_capability('tool/murelation:viewpositions', $usercontext);
        $canmanagepositions = has_capability('tool/murelation:managepositions', $usercontext);

        $sql = new sql(
            "SELECT f.*, sup.userid as supuserid, f.supervisortitle, f.subordinatetitle, fm.id AS fmid
               FROM {tool_murelation_framework} f
          LEFT JOIN {tool_murelation_subordinate} sub ON sub.frameworkid = f.id AND sub.userid = :subuserid
          LEFT JOIN {tool_murelation_supervisor} sup ON sup.frameworkid = f.id AND sup.id = sub.supervisorid
          LEFT JOIN {cohort_members} fm ON fm.cohortid = f.managecohortid AND fm.userid = :me
              WHERE f.uimode = :supervisorsuimode AND f.visibility <> 0
                    /* canmanage */
                    /* tenant */
           ORDER BY f.supervisortitle ASC",
            [
                'subuserid' => $subuser->id,
                'me' => $USER->id,
                'supervisorsuimode' => framework::UIMODE_SUPERVISORS,
            ]
        );

        if ($canmanagepositions) {
            // Show empty positions that can be filled.
            $sql = $sql->replace_comment(
                'canmanage',
                "AND (sub.id IS NOT NULL OR f.subordinatecohortid IS NULL OR EXISTS (
                     SELECT 'x'
                       FROM {cohort_members} cm
                      WHERE cm.cohortid = f.subordinatecohortid AND cm.userid = ?))",
                [$subuser->id]
            );
        } else {
            $sql = $sql->replace_comment('canmanage', "AND sub.id IS NOT NULL");
        }

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

        $frameworks = $DB->get_records_sql($sql->sql, $sql->params);

        if (is_siteadmin()) {
            foreach ($frameworks as $framework) {
                $framework->canmanage = true;
            }
        } else if ($canmanagepositions || $canviewpositions) {
            foreach ($frameworks as $k => $framework) {
                if (!$canmanagepositions) {
                    $framework->canmanage = false;
                    continue;
                }
                if (!$framework->managecohortid) {
                    $framework->canmanage = true;
                    continue;
                }
                if (isset($framework->fmid)) {
                    $framework->canmanage = true;
                    continue;
                }
                if ($framework->supuserid) {
                    $framework->canmanage = false;
                    continue;
                }
                unset($frameworks[$k]);
            }
        } else {
            foreach ($frameworks as $k => $framework) {
                $framework->canmanage = false;
                if ($framework->visibility == framework::VISIBILITY_EVERYBODY) {
                    continue;
                }
                if ($framework->visibility == framework::VISIBILITY_SUBORDINATES) {
                    if (!$isteacher && $USER->id != $framework->supuserid && $USER->id != $subuser->id) {
                        unset($frameworks[$k]);
                    }
                    continue;
                }
                if ($framework->visibility == framework::VISIBILITY_SUPERVISORS) {
                    if (!$isteacher && $USER->id != $framework->supuserid) {
                        unset($frameworks[$k]);
                    }
                    continue;
                }
                // Must be framework::VISIBILITY_MANAGERS.
                unset($frameworks[$k]);
            }
        }

        foreach ($frameworks as $framework) {
            unset($framework->fmid);
        }

        return $frameworks;
    }

    /**
     * Quick look up to see if user is supervising any subordinates.
     *
     * @param int $supuserid supervisor user id
     * @return bool
     */
    public static function supervisor_has_subordinates(int $supuserid): bool {
        global $DB;

        $sql = new sql(
            "SELECT 'x'
               FROM {tool_murelation_supervisor} sup
               JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid AND f.uimode = :supervisorsuimode
              WHERE f.visibility <> 0 AND sup.userid = :userid",
            [
                'supervisorsuimode' => framework::UIMODE_SUPERVISORS,
                'userid' => $supuserid,
            ]
        );
        return $DB->record_exists_sql($sql->sql, $sql->params);
    }

    /**
     * Triggered when user allocated to different tenant
     * or if there is mismatch detected in cron.
     *
     * @param int $userid
     * @return void
     */
    public static function tenant_allocation_changed(int $userid): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$user) {
            return;
        }

        $sql = new sql(
            "UPDATE {tool_murelation_supervisor}
                SET tenantid = :tenantid
              WHERE id IN (
                  SELECT sub.supervisorid
                    FROM {tool_murelation_subordinate} sub
                    JOIN {tool_murelation_framework} f ON f.id = sub.frameworkid
                   WHERE f.uimode = :supervisormode AND sub.userid = :userid
              )",
            [
                'tenantid' => $user->tenantid,
                'supervisormode' => framework::UIMODE_SUPERVISORS,
                'userid' => $user->id,
            ]
        );

        $DB->execute($sql->sql, $sql->params);
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
            $sql = new sql(
                "SELECT DISTINCT u.id
                   FROM {user} u
                   JOIN {tool_murelation_subordinate} sub ON sub.userid = u.id
                   JOIN {tool_murelation_supervisor} sup ON sup.id = sub.supervisorid
                   JOIN {tool_murelation_framework} f ON f.id = sub.frameworkid
                  WHERE f.uimode = :supervisormode AND u.deleted = 0
                        AND (
                           (u.tenantid IS NULL AND sup.tenantid IS NOT NULL)
                             OR
                           (u.tenantid IS NOT NULL AND sup.tenantid IS NULL)
                             OR
                           (u.tenantid <> sup.tenantid)
                        )
               ORDER BY u.id ASC",
                ['supervisormode' => framework::UIMODE_SUPERVISORS]
            );
            $userids = $DB->get_fieldset_sql($sql->sql, $sql->params);
            foreach ($userids as $userid) {
                self::tenant_allocation_changed($userid);
            }

            // Delete supervisors with conflicting tenant ids,
            // this is done only in cron to prevent problems when moving both users to a different tenant.
            $sql = new sql(
                "SELECT sup.id
                   FROM {tool_murelation_supervisor} sup
                   JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
                   JOIN {user} u ON u.id = sup.userid
                  WHERE f.uimode = :supervisormode
                        AND u.tenantid IS NOT NULL AND sup.tenantid IS NOT NULL AND u.tenantid <> sup.tenantid
               ORDER BY sup.id ASC",
                ['supervisormode' => framework::UIMODE_SUPERVISORS]
            );
            $supervisorids = $DB->get_fieldset_sql($sql->sql, $sql->params);
            foreach ($supervisorids as $supervisorid) {
                supervisor::delete($supervisorid);
            }
        }
    }
}
