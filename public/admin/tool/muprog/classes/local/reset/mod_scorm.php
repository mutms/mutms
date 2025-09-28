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

namespace tool_muprog\local\reset;

use stdClass;

/**
 * mod_scorm user data reset
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scorm extends base {
    /**
     * Custom course module reset method.
     *
     * @param stdClass $user
     * @param array $courseids
     * @param array $options
     * @return void
     */
    public static function purge_data(stdClass $user, array $courseids, array $options = []): void {
        global $DB;

        if (!$courseids) {
            return;
        }

        // There is no simple way to delete all quiz data, there will be leftovers in questions
        // database tables.

        [$courses, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['userid'] = $user->id;

        $scorms = "SELECT s.id
                     FROM {scorm} s
                    WHERE s.course $courses";

        $attempts = "SELECT sa.id
                       FROM {scorm_attempt} sa
                      WHERE sa.userid = :userid AND sa.scormid IN ($scorms)";

        $sql = "DELETE
                  FROM {scorm_scoes_value}
                 WHERE attemptid IN ($attempts)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {scorm_attempt}
                 WHERE userid = :userid AND scormid IN ($scorms)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {scorm_aicc_session}
                 WHERE userid = :userid AND scormid IN ($scorms)";
        $DB->execute($sql, $params);
    }
}
