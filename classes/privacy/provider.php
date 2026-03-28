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

namespace tool_mutenancy\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\manager;

/**
 * Multi-tenancy privacy provider.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns data about this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_mutenancy_manager',
            [
                'tenantid' => 'privacy:metadata:tool_mutenancy_manager:tenantid',
                'userid' => 'privacy:metadata:tool_mutenancy_manager:userid',
                'usercreated' => 'privacy:metadata:tool_mutenancy_manager:usercreated', // This is not their private info!
                'timecreated' => 'privacy:metadata:tool_mutenancy_manager:timecreated',
            ],
            'privacy:metadata:tool_mutenancy_manager'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        if (!tenancy::is_active()) {
            return $contextlist;
        }

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {tool_mutenancy_manager} tm ON tm.tenantid = ctx.instanceid
                 WHERE ctx.contextlevel = :tenantlevel AND tm.userid = :userid";
        $params = [
            'tenantlevel' => \context_tenant::LEVEL,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!tenancy::is_active()) {
            return;
        }

        $tenantids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_tenant) {
                $tenantids[] = $context->instanceid;
            }
        }
        if (!$tenantids) {
            return;
        }

        [$select, $params] = $DB->get_in_or_equal($tenantids, SQL_PARAMS_NAMED);
        $sql = "SELECT t.*
                  FROM {tool_mutenancy_tenant} t
                  JOIN {tool_mutenancy_manager} tm ON tm.tenantid = t.id
                 WHERE tm.userid = :userid AND t.id $select
              ORDER BY t.id ASC";
        $params['userid'] = $contextlist->get_user()->id;
        $tenants = $DB->get_records_sql($sql, $params);
        foreach ($tenants as $tenant) {
            $tenantcontext = \context_tenant::instance($tenant->id);
            $data = (object)[
                'timecreated' => transform::datetime($tenant->timecreated),
                // Do not expose who created the manager, we cannot leak other users data here.
            ];
            $subcontext = [get_string('tenant_manager', 'tool_mutenancy')];
            writer::with_context($tenantcontext)->export_related_data($subcontext, 'data', $data);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if (!tenancy::is_active()) {
            return;
        }

        if (!$context instanceof \context_tenant) {
            return;
        }
        manager::set_userids($context->instanceid, []);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (!tenancy::is_active()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_tenant) {
                manager::remove($context->instanceid, $userid);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        if (!tenancy::is_active()) {
            return;
        }

        $context = $userlist->get_context();
        if (!$context instanceof \context_tenant) {
            return;
        }

        $sql = "SELECT tm.userid
                  FROM {tool_mutenancy_manager} tm
                  JOIN {user} u ON u.id = tm.userid AND u.deleted = 0
                 WHERE tm.tenantid = :tenantid
              ORDER BY tm.userid ASC";

        $userlist->add_users($DB->get_fieldset_sql($sql, ['tenantid' => $context->instanceid]));
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if (!tenancy::is_active()) {
            return;
        }

        $context = $userlist->get_context();
        if (!$context instanceof \context_tenant) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            manager::remove($context->instanceid, $userid);
        }
    }
}
