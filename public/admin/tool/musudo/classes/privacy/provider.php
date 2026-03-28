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

namespace tool_musudo\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Sudo privacy provider.
 *
 * @package     tool_musudo
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
            'tool_musudo_sudoer',
            [
                'userid' => 'privacy:metadata:tool_musudo_sudoer:userid',
                'note' => 'privacy:metadata:tool_musudo_sudoer:note',
                'mfarequired' => 'privacy:metadata:tool_musudo_sudoer:mfarequired',
                'privilegesjson' => 'privacy:metadata:tool_musudo_sudoer:privilegesjson',
                'timecreated' => 'privacy:metadata:tool_musudo_sudoer:timecreated',
            ],
            'privacy:metadata:tool_musudo_sudoer:tableexplanation'
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

        if ($DB->record_exists('tool_musudo_sudoer', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $sudoer = $DB->get_record('tool_musudo_sudoer', ['userid' => $contextlist->get_user()->id]);
        if (!$sudoer) {
            return;
        }
        unset($sudoer->id);
        $sudoer->timecreated = transform::datetime($sudoer->timecreated);

        $syscontext = \context_system::instance();
        $subcontext = [get_string('sudoer', 'tool_musudo')];
        writer::with_context($syscontext)->export_related_data($subcontext, 'data', [$sudoer]);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_system) {
            return;
        }

        $DB->delete_records('tool_musudo_sudoer', []);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                $DB->delete_records('tool_musudo_sudoer', ['userid' => $contextlist->get_user()->id]);
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

        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $sql = "SELECT u.id
                  FROM {tool_musudo_sudoer} su
                  JOIN {user} u ON u.id = su.userid AND u.deleted = 0
              ORDER BY u.id ASC";

        $userlist->add_users($DB->get_fieldset_sql($sql));
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('tool_musudo_sudoer', ['userid' => $userid]);
        }
    }
}
