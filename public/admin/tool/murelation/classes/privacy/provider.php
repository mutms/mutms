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

namespace tool_murelation\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use tool_murelation\local\framework;
use tool_murelation\local\supervisor;
use tool_murelation\local\subordinate;

/**
 * Supervisors and teams privacy provider.
 *
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\request\core_userlist_provider, \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider {
    /**
     * Returns data about this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_murelation_supervisor',
            [
                'frameworkid' => 'privacy:metadata:field:frameworkid',
                'userid' => 'privacy:metadata:field:userid',
                'teamname' => 'privacy:metadata:field:teamname',
                'teamidnumber' => 'privacy:metadata:field:teamidnumber',
            ],
            'privacy:metadata:table:tool_murelation_supervisor'
        );
        $collection->add_database_table(
            'tool_murelation_subordinate',
            [
                'supervisorid' => 'privacy:metadata:field:supervisorid',
                'userid' => 'privacy:metadata:field:userid',
                'teamposition' => 'privacy:metadata:field:teamposition',
            ],
            'privacy:metadata:table:tool_murelation_subordinate'
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
        global $DB;

        $contextlist = new contextlist();

        if ($DB->record_exists('tool_murelation_supervisor', ['userid' => $userid])) {
            $contextlist->add_system_context();
        } else if ($DB->record_exists('tool_murelation_subordinate', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.deleted = 0 AND (
                     EXISTS (
                         SELECT 'x'
                           FROM {tool_murelation_supervisor} sup
                          WHERE sup.userid = u.id
                     ) OR EXISTS (
                         SELECT 'x'
                           FROM {tool_murelation_subordinate} sub
                          WHERE sub.userid = u.id
                     )
                 )";
        $userlist->add_from_sql('id', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $syscontext = \context_system::instance();

        $contextids = $contextlist->get_contextids();
        if (!in_array($syscontext->id, $contextids)) {
            return;
        }

        $user = $contextlist->get_user();

        $sql = "SELECT sup.teamname, sup.teamidnumber, f.supervisortitle
                  FROM {tool_murelation_supervisor} sup
                  JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
                 WHERE sup.userid = :userid
              ORDER BY sup.id";
        $params = ['userid' => $user->id];
        $sups = $DB->get_records_sql($sql, $params);
        $subcontext = [get_string('supervisor', 'tool_murelation')];
        writer::with_context($syscontext)->export_related_data($subcontext, 'data', array_values($sups));

        $sql = "SELECT sub.teamposition, f.subordinatetitle
                  FROM {tool_murelation_subordinate} sub
                  JOIN {tool_murelation_framework} f ON f.id = sub.frameworkid
                 WHERE sub.userid = :userid
              ORDER BY sub.id";
        $params = ['userid' => $user->id];
        $subs = $DB->get_records_sql($sql, $params);
        $subcontext = [get_string('subordinate', 'tool_murelation')];
        writer::with_context($syscontext)->export_related_data($subcontext, 'data', array_values($subs));
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('tool_murelation_subordinate', []);

        $sql = "DELETE
                  FROM {tool_murelation_supervisor}
                 WHERE frameworkid IN (SELECT id FROM {tool_murelation_framework} WHERE uimode = ?)";
        $DB->execute($sql, [framework::UIMODE_SUPERVISORS]);

        $DB->set_field('tool_murelation_supervisor', 'userid', null, []);

        ob_start();
        $task = new \tool_murelation\task\cron();
        $task->execute();
        ob_end_clean();

        $trans->allow_commit();
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $syscontext = \context_system::instance();

        $contextids = $contextlist->get_contextids();
        if (!in_array($syscontext->id, $contextids)) {
            return;
        }

        $user = $contextlist->get_user();

        $sql = "SELECT sup.id
                  FROM {tool_murelation_supervisor} sup
                  JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
                 WHERE sup.userid = :userid AND f.uimode = :supervisors
              ORDER BY sup.id";
        $params = ['userid' => $user->id, 'supervisors' => framework::UIMODE_SUPERVISORS];
        $supervisorids = $DB->get_fieldset_sql($sql, $params);
        foreach ($supervisorids as $supervisorid) {
            supervisor::delete($supervisorid);
        }

        $sql = "SELECT sub.supervisorid
                  FROM {tool_murelation_subordinate} sub
                  JOIN {tool_murelation_framework} f ON f.id = sub.frameworkid
                 WHERE sub.userid = :userid AND f.uimode = :supervisors
              ORDER BY sub.id";
        $params = ['userid' => $user->id, 'supervisors' => framework::UIMODE_SUPERVISORS];
        $supervisorids = $DB->get_fieldset_sql($sql, $params);
        foreach ($supervisorids as $supervisorid) {
            supervisor::delete($supervisorid);
        }

        $sql = "SELECT sup.id
                  FROM {tool_murelation_supervisor} sup
                  JOIN {tool_murelation_framework} f ON f.id = sup.frameworkid
                 WHERE sup.userid = :userid AND f.uimode = :teams
              ORDER BY sup.id";
        $params = ['userid' => $user->id, 'teams' => framework::UIMODE_TEAMS];
        $supervisorids = $DB->get_fieldset_sql($sql, $params);
        foreach ($supervisorids as $supervisorid) {
            supervisor::vacate($supervisorid);
        }

        $sql = "SELECT sub.id
                  FROM {tool_murelation_subordinate} sub
                  JOIN {tool_murelation_framework} f ON f.id = sub.frameworkid
                 WHERE sub.userid = :userid AND f.uimode = :teams
              ORDER BY sub.id";
        $params = ['userid' => $user->id, 'teams' => framework::UIMODE_TEAMS];
        $subordinateids = $DB->get_fieldset_sql($sql, $params);
        foreach ($subordinateids as $subordinateid) {
            subordinate::delete($subordinateid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        foreach ($userlist->get_users() as $user) {
            $contextlist = new approved_contextlist($user, $userlist->get_component(), [$context->id]);
            self::delete_data_for_user($contextlist);
        }
    }
}
