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

namespace tool_muprog\local;

use stdClass;
use tool_mucertify\local\certification;

/**
 * Course reset helper.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_reset {
    /** @var int no program and course reset, repeated recertification will likely fail */
    public const RESETTYPE_NONE = 0;
    /** @var int forced program deallocation only */
    public const RESETTYPE_DEALLOCATE = 1;
    /** @var int program deallocation and forced course un-enrolment */
    public const RESETTYPE_STANDARD = 2;
    /** @var int unenrol plus experimental privacy API user purge */
    public const RESETTYPE_FULL = 3;

    /**
     * Purge program courses.
     *
     * @param stdClass $user
     * @param int $resettype
     * @param int $programid
     */
    public static function reset_courses(stdClass $user, int $resettype, int $programid): void {
        if ($resettype == self::RESETTYPE_DEALLOCATE) {
            return;
        }

        self::purge_enrolments($user, $programid);

        if ($resettype == self::RESETTYPE_STANDARD) {
            self::purge_standard($user, $programid);
        } else if ($resettype == self::RESETTYPE_FULL) {
            self::purge_full($user, $programid);
        } else {
            debugging('Unknown reset type: ' . $resettype, DEBUG_DEVELOPER);
        }

        self::purge_completions($user, $programid);
    }

    /**
     * Force deleting of course enrolments - even if protected by enrol plugins!
     *
     * @param stdClass $user
     * @param int $programid
     * @return void
     */
    public static function purge_enrolments(stdClass $user, int $programid): void {
        global $DB;
        $sql = "SELECT DISTINCT ue.*
                  FROM {tool_muprog_item} i
                  JOIN {course} c ON c.id = i.courseid
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE i.programid = :programid AND ue.userid = :userid
              ORDER BY ue.id ASC";
        $ues = $DB->get_records_sql($sql, ['programid' => $programid, 'userid' => $user->id]);
        foreach ($ues as $ue) {
            $instance = $DB->get_record('enrol', ['id' => $ue->enrolid], '*', MUST_EXIST);
            $enrolplugin = enrol_get_plugin($instance->enrol);
            if (!$enrolplugin) {
                $instance->enrol = 'manual'; // Hack to work around missing enrol plugins.
                $enrolplugin = enrol_get_plugin('manual');
            }
            $enrolplugin->unenrol_user($instance, $ue->userid);
        }
    }

    /**
     * Purge course data using programs code.
     *
     * @param stdClass $user
     * @param int $programid
     * @return void
     */
    public static function purge_standard(stdClass $user, int $programid): void {
        global $DB;

        $modules = $DB->get_records_menu('modules', [], 'id ASC', 'id, name');
        foreach ($modules as $mid => $module) {
            $resetclassname = '\\tool_muprog\\local\\reset\\mod_' . $module;
            if (!class_exists($resetclassname)) {
                continue;
            }
            $sql = "SELECT DISTINCT cm.course
                      FROM {course_modules} cm
                      JOIN {tool_muprog_item} i ON i.courseid = cm.course
                     WHERE cm.module = :mid AND i.programid = :programid";
            $courseids = $DB->get_fieldset_sql($sql, ['mid' => $mid, 'programid' => $programid]);
            if (!$courseids) {
                continue;
            }
            $resetclassname::purge_data($user, $courseids);
        }
    }

    /**
     * Purge course data using privacy API.
     *
     * @param stdClass $user
     * @param int $programid
     * @return void
     */
    public static function purge_full(stdClass $user, int $programid): void {
        global $DB;

        self::purge_standard($user, $programid);

        $modules = $DB->get_records_menu('modules', [], 'id ASC', 'id, name');
        foreach ($modules as $mid => $module) {
            $resetclassname = '\\tool_muprog\\local\\reset\\mod_' . $module;
            if (class_exists($resetclassname)) {
                // Already purged.
                continue;
            }
            /** @var class-string<\core\privacy\provider> $privacyclass */
            $privacyclass = 'mod_' . $module . '\\privacy\\provider';
            if (!class_exists($privacyclass) || !method_exists($privacyclass, 'delete_data_for_user')) {
                // Ignore missing or broken unsupported plugins.
                continue;
            }
            $interfaces = class_implements($privacyclass);
            if (isset($interfaces['core_privacy\local\metadata\null_provider'])) {
                // Better skip this, the legacy_polyfill may have broken delete_data_for_user method, such as mod_poster.
                continue;
            }
            $sql = "SELECT DISTINCT ctx.id
                      FROM {course_modules} cm
                      JOIN {tool_muprog_item} i ON i.courseid = cm.course
                      JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :modulecontext
                     WHERE cm.module = :mid AND i.programid = :programid";
            $contextids = $DB->get_fieldset_sql(
                $sql,
                ['mid' => $mid, 'programid' => $programid, 'modulecontext' => CONTEXT_MODULE]
            );
            if (!$contextids) {
                continue;
            }
            $list = new \core_privacy\local\request\approved_contextlist($user, 'mod_' . $module, $contextids);
            try {
                $privacyclass::delete_data_for_user($list);
            } catch (\Throwable $ex) {
                debugging(
                    "Exception detected in $privacyclass::delete_data_for_user(): " . $ex->getMessage(),
                    DEBUG_DEVELOPER,
                    $ex->getTrace()
                );
            }
        }
    }

    /**
     * Purge all course and activity completion records
     * and reset relevant caches.
     *
     * @param stdClass $user
     * @param int $programid
     * @return void
     */
    public static function purge_completions(stdClass $user, int $programid): void {
        global $DB;

        $params = ['programid' => $programid, 'userid' => $user->id];
        $courses =
            "SELECT c.id
               FROM {course} c
               JOIN {tool_muprog_item} i ON i.courseid = c.id AND i.programid = :programid";
        $coursemodules =
            "SELECT cm.id
               FROM {course_modules} cm
               JOIN {tool_muprog_item} i ON i.courseid = cm.course AND i.programid = :programid";

        $sql = "DELETE
                  FROM {course_modules_completion}
                 WHERE userid = :userid AND coursemoduleid IN ($coursemodules)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {course_modules_viewed}
                 WHERE userid = :userid AND coursemoduleid IN ($coursemodules)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {course_completion_crit_compl}
                 WHERE userid = :userid AND course IN ($courses)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {course_completions}
                 WHERE userid = :userid AND course IN ($courses)";
        $DB->execute($sql, $params);

        $sql = "SELECT DISTINCT c.id
                  FROM {tool_muprog_item} i
                  JOIN {course} c ON c.id = i.courseid
                 WHERE i.programid = :programid
              ORDER BY c.id ASC";

        $courseids = $DB->get_fieldset_sql($sql, ['programid' => $programid]);

        $cache1 = \cache::make('core', 'coursecompletion');
        $cache2 = \cache::make('core', 'completion');
        foreach ($courseids as $courseid) {
            $cache1->delete($user->id . '_' . $courseid);
            $cache2->delete($user->id . '_' . $courseid);
        }

        $cache3 = \cache::make('availability_grade', 'scores');
        $cache3->delete($user->id);

        $hook = new \tool_muprog\hook\course_completions_purged($user->id, $programid);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
    }
}
