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

namespace tool_mulib\local;

/**
 * Role related helper code.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class role_util {
    /**
     * Returns menu of roles assignable in context level.
     *
     * @param int $contextlevel
     * @return array roleid => role name
     */
    public static function get_contextlevel_roles_menu(int $contextlevel): array {
        global $DB;

        $sql = "SELECT r.*
                  FROM {role} r
                  JOIN {role_context_levels} rcl ON rcl.roleid = r.id
                 WHERE rcl.contextlevel = :contextlevel
              ORDER BY r.sortorder ASC";
        $params = ['contextlevel' => $contextlevel];
        $roles = $DB->get_records_sql($sql, $params);
        return role_fix_names($roles, null, ROLENAME_ORIGINAL, true);
    }
}
