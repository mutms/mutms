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

namespace tool_mulib\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Additional tools privacy support.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns meta-data about additional tools plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_mulib_notification_user',
            [
                'notificationid' => 'privacy:metadata:notificationid',
                'userid' => 'privacy:metadata:userid',
                'timenotified' => 'privacy:metadata:timenotified',
            ],
            'privacy:metadata:tool_mulib_notification_user:tableexplanation'
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
        $list = new contextlist();
        // Note that we do not know which context the notification belonged to,
        // so use system context for everything.
        $list->add_system_context();
        return $list;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $syscontext = \context_system::instance();

        $foundsystem = false;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->id == $syscontext->id) {
                $foundsystem = true;
                break;
            }
        }

        if (!$foundsystem) {
            // We do not know the actual context where the notification came from,
            // so we use only system context here.
            return;
        }

        $subcontexts = [get_string('privacy:metadata:tool_mulib_notification_user:tableexplanation', 'tool_mulib')];

        $sql = "SELECT nu.timenotified, n.component, n.notificationtype, n.instanceid, nu.otherid1, nu.otherid2
                  FROM {tool_mulib_notification_user} nu
                  JOIN {tool_mulib_notification} n ON n.id = nu.notificationid
                 WHERE nu.userid = :userid
              ORDER BY nu.id ASC";
        $params = ['userid' => $contextlist->get_user()->id];

        $data = [];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $notification) {
            $notification->timenotified = transform::datetime($notification->timenotified);
            $data[] = $notification;
        }
        $rs->close();
        // Method export_related_data() supports arrays, not just objects.
        writer::with_context($syscontext)->export_related_data(
            $subcontexts,
            'data',
            $data
        );
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        $syscontext = \context_system::instance();
        if ($context->id != $syscontext->id) {
            return;
        }

        // This is a bad idea, users may get notified again.
        $DB->delete_records('tool_mulib_notification_user', []);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $syscontext = \context_system::instance();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->id != $syscontext->id) {
                continue;
            }
            $userid = $contextlist->get_user()->id;
            $DB->delete_records('tool_mulib_notification_user', ['userid' => $userid]);
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $syscontext = \context_system::instance();
        if ($userlist->get_context()->id != $syscontext->id) {
            return;
        }

        $sql = "SELECT nu.userid
                  FROM {tool_mulib_notification_user} nu
                  JOIN {tool_mulib_notification} n ON n.id = nu.notificationid
                  JOIN {user} u ON u.id = nu.userid AND u.deleted = 0
              ORDER BY nu.userid ASC";

        $userlist->add_users($DB->get_fieldset_sql($sql));
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $syscontext = \context_system::instance();
        if ($userlist->get_context()->id != $syscontext->id) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('tool_mulib_notification_user', ['userid' => $userid]);
        }
    }
}
