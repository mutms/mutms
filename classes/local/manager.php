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
 * Multi-tenancy tenant manager helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manager {
    /** @var string tenant manager role short name and archetype */
    public const ROLESHORTNAME = 'tenantmanager';

    /**
     * Get default capabilities for 'tenantmanager' archetype.
     *
     * @return array
     */
    public static function get_default_capabilities(): array {
        $alldefs = [];
        $components = [];

        $allcaps = get_all_capabilities();
        foreach ($allcaps as $cap) {
            if (!isset($components[$cap['component']])) {
                $components[$cap['component']] = true;
                $alldefs = array_merge($alldefs, load_capability_def($cap['component']));
            }
        }
        unset($components);
        unset($allcaps);

        $defaults = [];
        foreach ($alldefs as $name => $def) {
            if (isset($def['archetypes'])) {
                if (isset($def['archetypes']['manager'])) {
                    if ($def['contextlevel'] > CONTEXT_SYSTEM) {
                        $defaults[$name] = $def['archetypes']['manager'];
                    }
                }
            }
        }

        // Remove unwanted 'tenantmanager' archetype capabilities.
        unset($defaults['tool/mutenancy:admin']);
        unset($defaults['moodle/user:editprofile']);
        unset($defaults['moodle/user:update']);
        unset($defaults['moodle/user:delete']);

        // Add extra capabilities here if necessary.
        $hook = new \tool_mutenancy\hook\tenant_manager_capabilities($defaults);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        return $hook->get_capabilities();
    }

    /**
     * Create tenant manager role.
     *
     * @return int
     */
    public static function create_role(): int {
        global $DB;

        if (!tenancy::is_active()) {
            throw new \core\exception\coding_exception('Multi-tenancy must be active before creating tenant manager role.');
        }

        $role = $DB->get_record('role', ['shortname' => self::ROLESHORTNAME]);
        if ($role) {
            return $role->id;
        }

        $syscontext = \context_system::instance();

        // Apply capability fixes - mostly level changes for capabilities used by multi-tenancy.
        update_capabilities('moodle');

        $roleid = create_role('', self::ROLESHORTNAME, '', self::ROLESHORTNAME);

        // Add default role capabilities.
        $defaultpermissions = get_default_capabilities(self::ROLESHORTNAME);
        foreach ($defaultpermissions as $capability => $permission) {
            assign_capability($capability, $permission, $roleid, $syscontext->id, true);
        }

        // Apply defaults.
        $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
        foreach (['assign', 'override', 'switch', 'view'] as $type) {
            $function = "core_role_set_{$type}_allowed";
            $allows = get_default_role_archetype_allows($type, $role->archetype);
            foreach ($allows as $allowid) {
                $function($role->id, $allowid);
            }
        }

        // Allow global manager to view and override the tenant manager role details.
        $manager = $DB->get_record('role', ['shortname' => 'manager', 'archetype' => 'manager']);
        if ($manager) {
            core_role_set_view_allowed($manager->id, $roleid);
            core_role_set_override_allowed($manager->id, $roleid);
        }

        return $roleid;
    }

    /**
     * Returns tenant manager role record.
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
     * Delete tenant manager role.
     */
    public static function delete_role(): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ROLESHORTNAME]);
        if ($role) {
            delete_role($role->id);
        }
    }

    /**
     * Add tenant manager.
     *
     * @param int $tenantid
     * @param int $userid
     * @return bool success
     */
    public static function add(int $tenantid, int $userid): bool {
        global $DB, $USER;

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'confirmed' => 1]);
        if (!$user) {
            return false;
        }

        $tenantcontext = \context_tenant::instance($tenant->id);
        $categorycontext = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);

        if ($user->tenantid && $user->tenantid != $tenant->id) {
            debugging('tenant members cannot be added to another tenant as managers', DEBUG_DEVELOPER);
            return false;
        }

        $trans = $DB->start_delegated_transaction();

        $record = $DB->get_record('tool_mutenancy_manager', ['tenantid' => $tenant->id, 'userid' => $user->id]);
        if (!$record) {
            $record = (object)[
                'tenantid' => $tenant->id,
                'userid' => $user->id,
                'usercreated' => $USER->id,
                'timecreated' => time(),
            ];
            $record->id = $DB->insert_record('tool_mutenancy_manager', $record);
        }

        $role = self::get_role();
        role_assign($role->id, $user->id, $tenantcontext->id, 'tool_mutenancy', 0);
        if ($categorycontext) {
            role_assign($role->id, $user->id, $categorycontext->id, 'tool_mutenancy', 0);
        }

        $trans->allow_commit();

        return true;
    }

    /**
     * Remove tenant manager.
     *
     * @param int $tenantid
     * @param int $userid
     */
    public static function remove(int $tenantid, int $userid): void {
        global $DB;

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('tool_mutenancy_manager', ['tenantid' => $tenant->id, 'userid' => $userid]);

        $role = self::get_role();

        $tenantcontext = \context_tenant::instance($tenant->id);
        role_unassign($role->id, $userid, $tenantcontext->id, 'tool_mutenancy', 0);

        $categorycontext = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);
        if ($categorycontext) {
            role_unassign($role->id, $userid, $categorycontext->id, 'tool_mutenancy', 0);
        }

        $trans->allow_commit();
    }

    /**
     * Update tenant managers.
     *
     * @param int $tenantid
     * @param array $userids
     */
    public static function set_userids(int $tenantid, array $userids): void {
        global $DB;
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);

        $currentmanagers = $DB->get_records_menu('tool_mutenancy_manager', ['tenantid' => $tenant->id], '', 'userid, 1');

        foreach ($userids as $userid) {
            if (self::add($tenantid, $userid)) {
                unset($currentmanagers[$userid]);
            }
        }

        foreach ($currentmanagers as $userid => $unused) {
            self::remove($tenantid, $userid);
        }
    }

    /**
     * Fix tenant tenant manager roles.
     *
     * @return void
     */
    public static function sync(): void {
        global $DB;

        $role = self::get_role();

        // Remove deleted managers and managers from other tenants.
        $sql = "SELECT m.*
                  FROM {tool_mutenancy_manager} m
                  JOIN {tool_mutenancy_tenant} t ON t.id = m.tenantid
             LEFT JOIN {user} u ON u.id = m.userid AND u.deleted = 0 AND (u.tenantid IS NULL OR u.tenantid = t.id)
                 WHERE u.id IS NULL
              ORDER BY m.userid ASC, m.tenantid ASC";
        $params = [];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $manager) {
            self::remove($manager->tenantid, $manager->userid);
        }
        $rs->close();

        // Add missing tenant manager roles.

        $sql = "SELECT m.userid, c.id AS contextid
                  FROM {tool_mutenancy_tenant} t
                  JOIN {tool_mutenancy_manager} m ON m.tenantid = t.id
                  JOIN {user} u ON u.id = m.userid AND u.deleted = 0
                  JOIN {context} c ON c.contextlevel = :catlevel AND c.instanceid = t.categoryid
             LEFT JOIN {role_assignments} ra ON ra.contextid = c.id AND ra.roleid = :roleid
                                                AND ra.userid = m.userid AND ra.component = 'tool_mutenancy'
                                                AND ra.itemid = 0
                 WHERE ra.id IS NULL
              ORDER BY m.userid ASC, c.id ASC";
        $params = [
            'catlevel' => CONTEXT_COURSECAT,
            'roleid' => $role->id,
        ];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $manager) {
            role_assign($role->id, $manager->userid, $manager->contextid, 'tool_mutenancy', 0);
        }
        $rs->close();

        $sql = "SELECT m.userid, c.id AS contextid
                  FROM {tool_mutenancy_tenant} t
                  JOIN {tool_mutenancy_manager} m ON m.tenantid = t.id
                  JOIN {user} u ON u.id = m.userid AND u.deleted = 0
                  JOIN {context} c ON c.contextlevel = :tenantlevel AND c.instanceid = t.id
             LEFT JOIN {role_assignments} ra ON ra.contextid = c.id AND ra.roleid = :roleid
                                                AND ra.userid = m.userid AND ra.component = 'tool_mutenancy'
                                                AND ra.itemid = 0
                 WHERE ra.id IS NULL
              ORDER BY m.userid ASC, c.id ASC";
        $params = [
            'tenantlevel' => CONTEXT_TENANT,
            'roleid' => $role->id,
        ];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $manager) {
            role_assign($role->id, $manager->userid, $manager->contextid, 'tool_mutenancy', 0);
        }
        $rs->close();

        // Remove stale tenant manager roles.

        $sql = "SELECT ra.*
                  FROM {role_assignments} ra
                  JOIN {context} c ON c.id = ra.contextid AND c.contextlevel = :catlevel
                 WHERE ra.component = 'tool_mutenancy' AND ra.itemid = 0
                       AND (
                           ra.roleid <> :roleid
                           OR NOT EXISTS(
                               SELECT 'x'
                                 FROM {tool_mutenancy_tenant} t
                                 JOIN {tool_mutenancy_manager} m ON m.tenantid = t.id AND m.userid = ra.userid
                                WHERE t.categoryid = c.instanceid)
                       )
              ORDER BY ra.id ASC";
        $params = [
            'catlevel' => CONTEXT_COURSECAT,
            'roleid' => $role->id,
        ];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ra) {
            role_unassign($ra->roleid, $ra->userid, $ra->contextid, $ra->component, $ra->itemid);
        }
        $rs->close();

        $sql = "SELECT ra.*
                  FROM {role_assignments} ra
                  JOIN {context} c ON c.id = ra.contextid AND c.contextlevel = :tenantlevel
                 WHERE ra.component = 'tool_mutenancy' AND ra.itemid = 0
                       AND (
                           ra.roleid <> :roleid
                           OR NOT EXISTS(
                               SELECT 'x'
                                 FROM {tool_mutenancy_tenant} t
                                 JOIN {tool_mutenancy_manager} m ON m.tenantid = t.id AND m.userid = ra.userid
                                WHERE t.id = c.instanceid)
                       )
              ORDER BY ra.id ASC";
        $params = [
            'tenantlevel' => CONTEXT_TENANT,
            'roleid' => $role->id,
        ];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ra) {
            role_unassign($ra->roleid, $ra->userid, $ra->contextid, $ra->component, $ra->itemid);
        }
        $rs->close();
    }

    /**
     * Get tenant managers.
     *
     * NOTE: deleted users and members of other tenants are ignored.
     *
     * @param int $tenantid
     * @return array user full names indexed with userid
     */
    public static function get_manager_users(int $tenantid): array {
        global $DB;

        [$sortsql, $params] = users_order_by_sql('u');
        $params['tenantid'] = $tenantid;

        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {tool_mutenancy_manager} tm ON tm.userid = u.id AND tm.tenantid = :tenantid
                 WHERE u.deleted = 0 AND u.confirmed = 1
              ORDER BY $sortsql";

        $users = $DB->get_records_sql($sql, $params);
        foreach ($users as $k => $user) {
            $users[$k] = fullname($user, true);
        }

        return $users;
    }

    /**
     * User deleted event observer.
     *
     * @param \core\event\user_deleted $event
     * @return void
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;
        if (!tenancy::is_active()) {
            return;
        }

        $DB->delete_records('tool_mutenancy_manager', ['userid' => $event->objectid]);
    }
}
