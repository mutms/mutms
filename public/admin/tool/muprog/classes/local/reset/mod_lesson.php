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
 * mod_lesson user data reset
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_lesson extends base {
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

        $fs = get_file_storage();

        [$courses, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['userid'] = $user->id;

        $lessons = "SELECT l.id
                      FROM {lesson} l
                     WHERE l.course $courses";

        $sql = "DELETE
                  FROM {lesson_overrides}
                 WHERE userid = :userid AND lessonid IN ($lessons)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {lesson_branch}
                 WHERE userid = :userid AND lessonid IN ($lessons)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {lesson_timer}
                 WHERE userid = :userid AND lessonid IN ($lessons)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {lesson_grades}
                 WHERE userid = :userid AND lessonid IN ($lessons)";
        $DB->execute($sql, $params);

        $attempts = "SELECT la.id
                       FROM {lesson_attempts} la
                      WHERE la.userid = :userid AND la.lessonid IN ($lessons)";
        $sql = "SELECT f.*
                  FROM {files} f
                 WHERE f.component = 'mod_lesson' AND (f.filearea = 'essay_responses' OR f.filearea = 'essay_answers')
                       AND f.itemid IN ($attempts)";
        $files = $DB->get_recordset_sql($sql, $params);
        foreach ($files as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
        $files->close();
        $sql = "DELETE
                  FROM {lesson_attempts}
                 WHERE userid = :userid AND lessonid IN ($lessons)";
        $DB->execute($sql, $params);
    }
}
