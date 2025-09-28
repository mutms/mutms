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

namespace tool_mutrain\local\area;

/**
 * Course completion training area.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class core_course_course extends base {
    /**
     * SQL select for the area fields.
     *
     * @param string $alias custom field category table
     * @return string
     */
    public static function get_category_select(string $alias): string {
        return "$alias.component = 'core_course' AND $alias.area = 'course'";
    }

    /**
     * Synchronise cached values of course completions.
     */
    public static function sync_area_completions(): void {
        global $DB;

        $courselevel = CONTEXT_COURSE;

        // Add completions.
        $sql = "INSERT INTO {tool_mutrain_completion}
                       (fieldid, instanceid, userid, timecompleted, contextid)

                SELECT DISTINCT cd.fieldid, cd.instanceid, cc.userid, cc.timecompleted, ctx.id AS contextid
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course AND c.enablecompletion = 1
                  JOIN {customfield_data} cd ON cd.instanceid = cc.course AND cd.intvalue > 0
                  JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                  JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                  JOIN {user} u ON u.id = cc.userid AND u.deleted = 0 AND u.confirmed = 1
                  JOIN {context} ctx ON ctx.instanceid = cc.course AND ctx.contextlevel = $courselevel
             LEFT JOIN {tool_mutrain_completion} ctc ON ctc.fieldid = cd.fieldid AND ctc.instanceid = cd.instanceid AND ctc.userid = cc.userid
                 WHERE ctc.id IS NULL AND cc.timecompleted IS NOT NULL
              ORDER BY cc.timecompleted ASC";
        $DB->execute($sql);

        // Remove completions for non-existent course completions.
        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE EXISTS (

                    SELECT 'x'
                      FROM {customfield_data} cd
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id

                 ) AND NOT EXISTS (

                    SELECT cc.id
                      FROM {course_completions} cc
                      JOIN {course} c ON c.id = cc.course AND c.enablecompletion = 1
                      JOIN {customfield_data} cd ON cd.instanceid = cc.course
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id
                           AND {tool_mutrain_completion}.instanceid = cd.instanceid
                           AND {tool_mutrain_completion}.userid = cc.userid
                           AND cc.timecompleted IS NOT NULL

                 )";
        $DB->execute($sql);

        // Sync completion dates.
        $sql = "UPDATE {tool_mutrain_completion}
                   SET timecompleted = (

                        SELECT cc.timecompleted
                          FROM {course_completions} cc
                          JOIN {course} c ON c.id = cc.course AND c.enablecompletion = 1
                          JOIN {customfield_data} cd ON cd.instanceid = cc.course
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                         WHERE {tool_mutrain_completion}.fieldid = cf.id AND {tool_mutrain_completion}.instanceid = cd.instanceid
                               AND {tool_mutrain_completion}.userid = cc.userid

                   )
                 WHERE EXISTS (

                        SELECT cc.id
                          FROM {course_completions} cc
                          JOIN {course} c ON c.id = cc.course AND c.enablecompletion = 1
                          JOIN {customfield_data} cd ON cd.instanceid = cc.course
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                         WHERE {tool_mutrain_completion}.fieldid = cf.id AND {tool_mutrain_completion}.instanceid = cd.instanceid
                               AND {tool_mutrain_completion}.userid = cc.userid
                               AND {tool_mutrain_completion}.timecompleted <> cc.timecompleted AND cc.timecompleted IS NOT NULL

                 )
        ";
        $DB->execute($sql);
    }

    /**
     * Course completion observer.
     *
     * @param \core\event\course_completed $event
     */
    public static function observe_course_completed(\core\event\course_completed $event): void {
        global $DB;

        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        // NOTE: do not check course_completions.reaggregate here!
        $completion = $event->get_record_snapshot('course_completions', $event->objectid);
        if ($completion->timecompleted === null) {
            return;
        }

        $sql = "SELECT cf.*, ctc.id AS ctcid
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                  JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = :courseid AND cd.intvalue > 0
             LEFT JOIN {tool_mutrain_completion} ctc ON ctc.fieldid = cf.id AND ctc.instanceid = cd.instanceid AND ctc.userid = :userid
                 WHERE cf.type = 'mutrain'
              ORDER BY cd.id ASC";
        $params = ['courseid' => $courseid, 'userid' => $userid];

        $inserted = [];
        $fields = $DB->get_records_sql($sql, $params);
        foreach ($fields as $field) {
            if ($field->ctcid) {
                $DB->set_field(
                    'tool_mutrain_completion',
                    'timecompleted',
                    $completion->timecompleted,
                    ['id' => $field->ctcid]
                );
            } else {
                $context = \context_course::instance($courseid);
                $record = (object)[
                    'fieldid' => $field->id,
                    'instanceid' => $courseid,
                    'userid' => $userid,
                    'timecompleted' => $completion->timecompleted,
                    'contextid' => $context->id,
                ];
                $inserted[] = (int)$DB->insert_record('tool_mutrain_completion', $record);
            }
        }

        if ($inserted) {
            $inserted = implode(',', $inserted);
            $sql = "SELECT DISTINCT tf.frameworkid
                      FROM {tool_mutrain_field} tf
                      JOIN {tool_mutrain_framework} tfw ON tfw.id = tf.frameworkid
                     WHERE tfw.archived = 0 AND tf.fieldid IN ($inserted)";
            $frameworkids = $DB->get_fieldset_sql($sql);
            if ($frameworkids) {
                // This should trigger things like program completion recalculation when user completes course.
                $hook = new \tool_mutrain\hook\completion_updated($userid, $frameworkids);
                \core\di::get(\core\hook\manager::class)->dispatch($hook);
            }
        }
    }

    /**
     * Course deletion observer.
     *
     * @param \core\event\course_deleted $event
     */
    public static function observe_course_deleted(\core\event\course_deleted $event): void {
        global $DB;

        if (!$DB->record_exists('customfield_field', ['type' => 'mutrain'])) {
            return;
        }

        $params = ['courseid' => $event->courseid];

        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE instanceid = :courseid AND EXISTS (

                    SELECT 'x'
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id
                           AND cf.type = 'mutrain'

                 )";
        $DB->execute($sql, $params);
    }

    /**
     * Hook callback.
     *
     * @param \tool_muprog\hook\course_completions_purged $hook
     */
    public static function program_course_completions_purged(\tool_muprog\hook\course_completions_purged $hook): void {
        global $DB;

        $params = ['programid' => $hook->programid, 'userid' => $hook->userid];

        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE userid = :userid AND instanceid IN (

                     SELECT pi.courseid
                       FROM {tool_muprog_item} pi
                      WHERE pi.programid = :programid AND pi.courseid IS NOT NULL

                       ) AND EXISTS (

                    SELECT 'x'
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'core_course' AND cat.area = 'course'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id
                           AND cf.type = 'mutrain'

                 )";
        $DB->execute($sql, $params);
    }
}
