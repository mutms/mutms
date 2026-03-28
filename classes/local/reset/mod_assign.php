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
 * mod_assign user data reset
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assign extends base {
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

        $sql = "DELETE
                  FROM {assign_overrides}
                WHERE userid = :userid AND assignid IN (
                    SELECT a.id
                    FROM {assign} a WHERE a.course $courses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {assign_user_flags}
                WHERE userid = :userid AND assignment IN (
                    SELECT a.id
                    FROM {assign} a WHERE a.course $courses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {assign_user_mapping}
                WHERE userid = :userid AND assignment IN (
                    SELECT a.id
                    FROM {assign} a WHERE a.course $courses)";
        $DB->execute($sql, $params);

        // Delete all grading related stuff.
        $grades = "SELECT g.id
                     FROM {assign_grades} g
                     JOIN {assign} a ON a.id = g.assignment
                    WHERE g.userid = :userid AND a.course $courses";

        // Sub-plugin assignfeedback_comments data.
        $sql = "SELECT f.*
                  FROM {files} f
                 WHERE f.component = 'assignfeedback_comments' AND f.itemid IN ($grades)";
        $files = $DB->get_recordset_sql($sql, $params);
        foreach ($files as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
        $files->close();
        $sql = "DELETE
                  FROM {assignfeedback_comments}
                 WHERE grade IN ($grades)";
        $DB->execute($sql, $params);

        // Sub-plugin assignfeedback_comments data.
        $sql = "SELECT f.*
                  FROM {files} f
                 WHERE f.component = 'assignfeedback_editpdf' AND f.itemid IN ($grades)";
        $files = $DB->get_recordset_sql($sql, $params);
        foreach ($files as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
        $files->close();
        $sql = "DELETE
                  FROM {assignfeedback_editpdf_cmnt}
                 WHERE gradeid IN ($grades)";
        $DB->execute($sql, $params);
        $sql = "DELETE
                  FROM {assignfeedback_editpdf_annot}
                 WHERE gradeid IN ($grades)";
        $DB->execute($sql, $params);
        $sql = "DELETE
                  FROM {assignfeedback_editpdf_rot}
                 WHERE gradeid IN ($grades)";
        $DB->execute($sql, $params);

        // Sub-plugin assignfeedback_file data.
        $sql = "SELECT f.*
                  FROM {files} f
                 WHERE f.component = 'assignfeedback_file' AND f.itemid IN ($grades)";
        $files = $DB->get_recordset_sql($sql, $params);
        foreach ($files as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
        $files->close();
        $sql = "DELETE
                  FROM {assignfeedback_file}
                 WHERE grade IN ($grades)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {assign_grades}
                 WHERE userid = :userid AND assignment IN (
                    SELECT a.id
                    FROM {assign} a WHERE a.course $courses)";
        $DB->execute($sql, $params);

        // Delete all submission related stuff.
        $submissions = "SELECT s.id
                          FROM {assign_submission} s
                          JOIN {assign} a ON a.id = s.assignment
                         WHERE s.userid = :userid AND a.course $courses";

        // Sub-plugin assignsubmission_comments data.
        $sql = "DELETE
                  FROM {comments}
                 WHERE component = 'assignsubmission_comments' AND commentarea = 'submission_comments'
                       AND itemid IN ($submissions)";
        $DB->execute($sql, $params);

        // Sub-plugin assignsubmission_files data.
        $sql = "SELECT f.*
                  FROM {files} f
                 WHERE f.component = 'assignsubmission_file' AND f.itemid IN ($submissions)";
        $files = $DB->get_recordset_sql($sql, $params);
        foreach ($files as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
        $files->close();
        $sql = "DELETE
                  FROM {assignsubmission_file}
                 WHERE submission IN ($submissions)";
        $DB->execute($sql, $params);

        // Sub-plugin assignsubmission_onlinetext data.
        $sql = "SELECT f.*
                  FROM {files} f
                 WHERE f.component = 'assignsubmission_onlinetext' AND f.itemid IN ($submissions)";
        $files = $DB->get_recordset_sql($sql, $params);
        foreach ($files as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
        $files->close();
        $sql = "DELETE
                  FROM {assignsubmission_onlinetext}
                 WHERE submission IN ($submissions)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {assign_submission}
                 WHERE userid = :userid AND assignment IN (
                    SELECT a.id
                    FROM {assign} a WHERE a.course $courses)";
        $DB->execute($sql, $params);
    }
}
