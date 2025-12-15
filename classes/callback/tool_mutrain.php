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

namespace tool_muprog\callback;

/**
 * Hook callbacks from tool_mutrain related code.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_mutrain {
    /**
     * Callback to discover credits framework usage.
     *
     * @param \tool_mutrain\hook\framework_usage $hook
     * @return void
     */
    public static function framework_usage(\tool_mutrain\hook\framework_usage $hook): void {
        global $DB;

        $count = $DB->count_records('tool_muprog_item', ['creditframeworkid' => $hook->get_frameworkid()]);
        if ($count) {
            $hook->add_usage($count);
        }
    }

    /**
     * Event announcing framework required framework credits were accumulated by user.
     *
     * @param \tool_mutrain\event\required_credits_reached $event
     * @return void
     */
    public static function required_credits_reached(\tool_mutrain\event\required_credits_reached $event): void {
        global $DB;

        $sql = "SELECT DISTINCT pi.programid
                  FROM {tool_muprog_item} pi
                  JOIN {tool_muprog_allocation} pa ON pa.programid = pi.programid
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                 WHERE pa.userid = :userid AND pi.creditframeworkid = :frameworkid
                       AND p.archived = 0 AND pa.archived = 0
              ORDER BY pi.programid";
        $params = ['userid' => $event->relateduserid, 'frameworkid' => $event->other['frameworkid']];
        $programids = $DB->get_fieldset_sql($sql, $params);

        if (!$programids) {
            return;
        }
        if (count($programids) > 1) {
            \tool_muprog\local\allocation::fix_user_enrolments(null, $params['userid']);
        } else {
            $programid = reset($programids);
            \tool_muprog\local\allocation::fix_user_enrolments($programid, $params['userid']);
        }
    }
}
