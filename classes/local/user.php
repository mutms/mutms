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

namespace tool_mutenancy\local;

use stdClass;

/**
 * Multi-tenancy tenant user helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user {
    /** @var string tenant user role short name and archetype */
    public const ROLESHORTNAME = 'tenantuser';

    /**
     * Get default capabilities for 'tenantuser' archetype.
     *
     * @return array
     */
    public static function get_default_capabilities(): array {

        $defaults = [];
        $defaults['moodle/category:viewcourselist'] = CAP_ALLOW;

        return $defaults;
    }

    /**
     * Create tenant user role.
     *
     * @return int
     */
    public static function create_role(): int {
        global $DB;

        if (!tenancy::is_active()) {
            throw new \core\exception\coding_exception('Multi-tenancy must be active before creating tenant user role.');
        }

        $role = $DB->get_record('role', ['shortname' => self::ROLESHORTNAME]);
        if ($role) {
            return $role->id;
        }

        $syscontext = \context_system::instance();

        $roleid = create_role('', self::ROLESHORTNAME, '', self::ROLESHORTNAME);

        // Add default role capabilities.
        $defaultpermissions = get_default_capabilities(self::ROLESHORTNAME);
        foreach ($defaultpermissions as $capability => $permission) {
            assign_capability($capability, $permission, $roleid, $syscontext->id, true);
        }

        // Apply defaults.
        $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
        foreach (['override', 'view'] as $type) {
            $function = "core_role_set_{$type}_allowed";
            $allows = get_default_role_archetype_allows($type, $role->archetype);
            foreach ($allows as $allowid) {
                $function($role->id, $allowid);
            }
        }

        // Allow global manager to view and override the tenant user role details.
        $manager = $DB->get_record('role', ['shortname' => 'manager', 'archetype' => 'manager']);
        if ($manager) {
            core_role_set_view_allowed($manager->id, $roleid);
            core_role_set_override_allowed($manager->id, $roleid);
        }

        return $roleid;
    }

    /**
     * Returns tenant user role record.
     *
     * Role is created if it does not exist or shortname was modified.
     *
     * @return stdClass
     */
    public static function get_role(): stdClass {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ROLESHORTNAME]);
        if ($role) {
            return $role;
        }

        $roleid = self::create_role();
        return $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
    }

    /**
     * Delete tenant user role.
     */
    public static function delete_role(): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ROLESHORTNAME]);
        if ($role) {
            delete_role($role->id);
        }
    }

    /**
     * Fix tenant cohort membership and tenant user role.
     *
     * @param int|null $tenantid
     * @param int|null $userid
     * @return void
     */
    public static function sync(?int $tenantid, ?int $userid): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        // Add tenant members to tenant cohort.

        $sql = "SELECT t.cohortid, u.id AS userid
                  FROM {tool_mutenancy_tenant} t
                  JOIN {user} u ON u.tenantid = t.id
             LEFT JOIN {cohort_members} cm ON cm.cohortid = t.cohortid AND cm.userid = u.id
                 WHERE u.deleted = 0";
        $params = [];
        if ($tenantid) {
            $sql .= " AND t.id = :tenantid";
            $params['tenantid'] = $tenantid;
        }
        if ($userid) {
            $sql .= " AND u.id = :userid";
            $params['userid'] = $userid;
        }
        $sql .= " ORDER BY t.id ASC, u.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $cm) {
            cohort_add_member($cm->cohortid, $cm->userid);
        }
        $rs->close();

        // Add associated users to tenant cohort.

        $sql = "SELECT t.cohortid, u.id AS userid
                  FROM {tool_mutenancy_tenant} t
                  JOIN {user} u ON u.tenantid IS NULL
                  JOIN {cohort_members} acm ON acm.cohortid = t.assoccohortid AND acm.userid = u.id
             LEFT JOIN {cohort_members} cm ON cm.cohortid = t.cohortid AND cm.userid = u.id
                 WHERE u.deleted = 0";
        $params = [];
        if ($tenantid) {
            $sql .= " AND t.id = :tenantid";
            $params['tenantid'] = $tenantid;
        }
        if ($userid) {
            $sql .= " AND u.id = :userid";
            $params['userid'] = $userid;
        }
        $sql .= " ORDER BY t.id ASC, u.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $cm) {
            cohort_add_member($cm->cohortid, $cm->userid);
        }
        $rs->close();

        // Remove stale tenant cohort membership.

        $sql = "SELECT t.cohortid, cm.userid
                  FROM {tool_mutenancy_tenant} t
                  JOIN {cohort_members} cm ON cm.cohortid = t.cohortid
                 WHERE NOT EXISTS (
                            SELECT 'x'
                              FROM {user} u
                              JOIN {cohort_members} acm ON acm.userid = u.id AND acm.cohortid = t.assoccohortid
                             WHERE u.deleted = 0 AND u.tenantid IS NULL)
                       AND NOT EXISTS(
                            SELECT 'x'
                              FROM {user} u
                             WHERE u.deleted = 0 AND u.tenantid = t.id AND u.id = cm.userid)";
        $params = [];
        if ($tenantid) {
            $sql .= " AND t.id = :tenantid";
            $params['tenantid'] = $tenantid;
        }
        if ($userid) {
            $sql .= " AND cm.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql .= " ORDER BY t.id ASC, cm.userid ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $cm) {
            cohort_remove_member($cm->cohortid, $cm->userid);
        }
        $rs->close();

        $role = self::get_role();

        // Add missing tenant user roles.

        $sql = "SELECT t.id AS itemid, cm.userid, c.id AS contextid
                  FROM {tool_mutenancy_tenant} t
                  JOIN {cohort_members} cm ON cm.cohortid = t.cohortid
                  JOIN {context} c ON c.contextlevel = :catlevel AND c.instanceid = t.categoryid
             LEFT JOIN {role_assignments} ra ON ra.contextid = c.id AND ra.roleid = :roleid
                                                AND ra.userid = cm.userid AND ra.component = 'tool_mutenancy'
                                                AND ra.itemid = t.id
                 WHERE ra.id IS NULL";
        $params = [
            'catlevel' => CONTEXT_COURSECAT,
            'roleid' => $role->id,
        ];
        if ($tenantid) {
            $sql .= " AND t.id = :tenantid";
            $params['tenantid'] = $tenantid;
        }
        if ($userid) {
            $sql .= " AND cm.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql .= " ORDER BY t.id ASC, cm.userid ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ra) {
            role_assign($role->id, $ra->userid, $ra->contextid, 'tool_mutenancy', $ra->itemid);
        }
        $rs->close();

        // Remove stale tenant user roles.

        $sql = "SELECT ra.*
                  FROM {role_assignments} ra
                  JOIN {context} c ON c.id = ra.contextid AND c.contextlevel = :catlevel
                 WHERE ra.component = 'tool_mutenancy' AND ra.itemid > 0
                       AND NOT EXISTS (
                           SELECT 'x'
                             FROM {cohort_members} cm
                             JOIN {tool_mutenancy_tenant} t ON t.categoryid = c.instanceid AND t.cohortid = cm.cohortid
                            WHERE ra.userid = cm.userid AND ra.itemid = t.id
                                  AND ra.roleid = :roleid AND c.depth = 2)";
        $params = [
            'catlevel' => CONTEXT_COURSECAT,
            'roleid' => $role->id,
        ];
        if ($tenantid) {
            $sql .= " AND ra.itemid = :tenantid";
            $params['tenantid'] = $tenantid;
        }
        if ($userid) {
            $sql .= " AND ra.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql .= " ORDER BY ra.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ra) {
            role_unassign($ra->roleid, $ra->userid, $ra->contextid, $ra->component, $ra->itemid);
        }
        $rs->close();
    }

    /**
     * User added to cohort event observer.
     *
     * @param \core\event\cohort_member_added $event
     * @return void
     */
    public static function cohort_member_added(\core\event\cohort_member_added $event): void {
        global $DB, $CFG;
        if (!tenancy::is_active()) {
            return;
        }

        $userid = $event->relateduserid;
        if (tenancy::get_user_tenantid($userid)) {
            return;
        }

        $sql = "SELECT t.id, t.cohortid, t.categoryid
                  FROM {cohort} tc
                  JOIN {tool_mutenancy_tenant} t ON t.cohortid = tc.id
                  JOIN {cohort} ac ON ac.id = t.assoccohortid AND ac.id = :assoccohortid
             LEFT JOIN {cohort_members} cm ON cm.cohortid = tc.id AND cm.userid = :userid
                 WHERE cm.id IS NULL
              ORDER BY t.id ASC";
        $params = ['assoccohortid' => $event->objectid, 'userid' => $userid];
        $tenants = $DB->get_records_sql($sql, $params);

        if (!$tenants) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');
        $role = self::get_role();
        foreach ($tenants as $tenant) {
            cohort_add_member($tenant->cohortid, $userid);
            $categorycontext = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);
            if ($categorycontext) {
                role_assign($role->id, $userid, $categorycontext->id, 'tool_mutenancy', $tenant->id);
            }
        }
    }

    /**
     * User removed from cohort event observer.
     *
     * @param \core\event\cohort_member_removed $event
     * @return void
     */
    public static function cohort_member_removed(\core\event\cohort_member_removed $event): void {
        global $DB, $CFG;
        if (!tenancy::is_active()) {
            return;
        }

        $userid = $event->relateduserid;
        if (tenancy::get_user_tenantid($userid)) {
            return;
        }

        $sql = "SELECT t.id, t.cohortid, t.categoryid
                  FROM {cohort} tc
                  JOIN {tool_mutenancy_tenant} t ON t.cohortid = tc.id AND t.assoccohortid = :assoccohortid
                  JOIN {cohort_members} cm ON cm.cohortid = tc.id AND cm.userid = :userid
              ORDER BY t.id ASC";
        $params = ['assoccohortid' => $event->objectid, 'userid' => $userid];
        $tenants = $DB->get_records_sql($sql, $params);

        if (!$tenants) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');
        $role = self::get_role();
        foreach ($tenants as $tenant) {
            cohort_remove_member($tenant->cohortid, $userid);
            $categorycontext = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);
            if ($categorycontext) {
                role_unassign($role->id, $userid, $categorycontext->id, 'tool_mutenancy', $tenant->id);
            }
        }
    }

    /**
     * Allocate user to tenant or de-allocate user.
     *
     * @param int $userid
     * @param int|null $tenantid null means global user
     * @return stdClass user record
     */
    public static function allocate(int $userid, ?int $tenantid): stdClass {
        global $DB, $CFG;

        if ($CFG->siteguest == $userid) {
            throw new \core\exception\coding_exception('Guest cannot be a tenant member');
        }
        if (is_siteadmin($userid) && $tenantid) {
            throw new \core\exception\coding_exception('Admins cannot be tenant members');
        }

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        unset($userid);

        if ($user->deleted) {
            throw new \core\exception\coding_exception('Deleted users cannot be allocated to tenants');
        }

        if ($user->mnethostid != $CFG->mnet_localhost_id) {
            throw new \core\exception\coding_exception('mnet users must not be tenant members');
        }

        if ($user->tenantid == $tenantid) {
            // Nothing to do.
            return $user;
        }

        $usercontext = \context_user::instance($user->id);

        if ($tenantid) {
            $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
        } else {
            $tenant = null;
        }
        unset($tenantid);

        $trans = $DB->start_delegated_transaction();

        if ($tenant) {
            $DB->set_field('user', 'tenantid', $tenant->id, ['id' => $user->id]);
            $ms = $DB->get_records('tool_mutenancy_manager', ['userid' => $user->id]);
            foreach ($ms as $m) {
                if ($m->tenantid !== $tenant->id) {
                    // Keep being manager only in the new tenant if already there.
                    manager::remove($m->tenantid, $m->userid);
                }
            }
            $user->tenantid = (string)$tenant->id;
        } else {
            $DB->set_field('user', 'tenantid', null, ['id' => $user->id]);
            $user->tenantid = null;
        }

        if ($tenant) {
            $tenantcontext = \context_tenant::instance($tenant->id);
            $usercontext->update_moved($tenantcontext);
        } else {
            $syscontext = \context_system::instance();
            $usercontext->update_moved($syscontext);
        }

        // Fix tenant cohort membership and tenant user roles.
        self::sync(null, $user->id);

        $trans->allow_commit();

        \core\session\manager::destroy_user_sessions($user->id);

        $event = \tool_mutenancy\event\user_allocated::create_from_user($user);
        $event->trigger();

        return $user;
    }

    /**
     * Count tenant users.
     *
     * @param int $tenantid
     * @return int
     */
    public static function count_users(int $tenantid): int {
        global $DB;

        $tenant = tenant::fetch($tenantid);
        if (!$tenant) {
            return 0;
        }

        $sql = "SELECT COUNT('x')
                  FROM {user} tuser
             LEFT JOIN {cohort_members} cm ON cm.cohortid = :assoccohortid AND cm.userid = tuser.id
                 WHERE (tuser.deleted = 0 AND tuser.tenantid IS NULL AND cm.id IS NOT NULL)
                       OR (tuser.deleted = 0 AND tuser.tenantid = :tenantid)";
        $params = ['assoccohortid' => $tenant->assoccohortid, 'tenantid' => $tenant->id];
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Count tenant members.
     *
     * @param int $tenantid
     * @return int
     */
    public static function count_members(int $tenantid): int {
        global $DB;
        return $DB->count_records('user', ['tenantid' => $tenantid, 'deleted' => 0]);
    }

    /**
     * Returns user's non-archived associated tenants, nothing for member tenants.
     *
     * @param int $userid
     * @return array tenant records
     */
    public static function get_associated_tenants(int $userid): array {
        global $DB;

        if (isguestuser() || tenancy::get_user_tenantid($userid)) {
            return [];
        }

        $sql = "SELECT t.*
                  FROM {tool_mutenancy_tenant} t
                  JOIN {user} u ON u.deleted = 0 AND u.tenantid IS NULL
                  JOIN {cohort_members} cm ON cm.cohortid = t.cohortid AND cm.userid = u.id
                 WHERE t.archived = 0 AND u.id = :userid
              ORDER BY t.name ASC";
        $params = ['userid' => $userid];
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Bulk user actions hook.
     *
     * @param \core_user\hook\extend_bulk_user_actions $hook
     */
    public static function hook_extend_bulk_user_actions(\core_user\hook\extend_bulk_user_actions $hook): void {
        if (!tenancy::is_active()) {
            return;
        }

        if (!has_capability('tool/mutenancy:allocate', \context_system::instance())) {
            return;
        }

        $hook->add_action('tool_mutenancy_allocate', new \action_link(
            new \core\url('/admin/tool/mutenancy/management/bulk_allocate.php'),
            get_string('bulk_allocate', 'tool_mutenancy')
        ));

        $hook->add_action('tool_mutenancy_deallocate', new \action_link(
            new \core\url('/admin/tool/mutenancy/management/bulk_deallocate.php'),
            get_string('bulk_deallocate', 'tool_mutenancy')
        ));
    }
}
