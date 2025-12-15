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

use tool_mutrain\local\framework;
use tool_mulib\local\mudb;

/**
 * Program completion credits area.
 *
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_muprog_program extends base {
    /**
     * SQL select for the area fields.
     *
     * @param string $alias custom field category table
     * @return string
     */
    public static function get_category_select(string $alias): string {
        return "$alias.component = 'tool_muprog' AND $alias.area = 'program'";
    }

    /**
     * Synchronise cached values of program completions.
     */
    public static function sync_area_completions(): void {
        global $DB;

        if (!class_exists(\tool_muprog\local\program::class)) {
            $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE EXISTS (

                    SELECT 'x'
                      FROM {customfield_data} cd
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id

                 )";
            $DB->execute($sql);
            return;
        }

        // Add completions.
        $sql = "INSERT INTO {tool_mutrain_completion} (fieldid, instanceid, userid, timecompleted, contextid)

                SELECT DISTINCT cd.fieldid, cd.instanceid, pa.userid, pa.timecompleted, p.contextid
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {customfield_data} cd ON cd.instanceid = p.id AND cd.decvalue IS NOT NULL
                  JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                  JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                  JOIN {user} u ON u.id = pa.userid AND u.deleted = 0 AND u.confirmed = 1
             LEFT JOIN {tool_mutrain_completion} ctc ON ctc.fieldid = cd.fieldid AND ctc.instanceid = cd.instanceid AND ctc.userid = pa.userid
                 WHERE ctc.id IS NULL AND pa.timecompleted IS NOT NULL
              ORDER BY pa.timecompleted ASC";
        $DB->execute($sql);

        // Remove completions for non-existent program completions.
        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE EXISTS (

                    SELECT 'x'
                      FROM {customfield_data} cd
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id

                 ) AND NOT EXISTS (

                    SELECT 'x'
                      FROM {tool_muprog_allocation} pa
                      JOIN {tool_muprog_program} p ON p.id = pa.programid
                      JOIN {customfield_data} cd ON cd.instanceid = p.id AND cd.decvalue IS NOT NULL
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id
                           AND {tool_mutrain_completion}.instanceid = cd.instanceid
                           AND {tool_mutrain_completion}.userid = pa.userid
                           AND pa.timecompleted IS NOT NULL

                 )";
        $DB->execute($sql);

        // Sync completion dates.
        $sql = "UPDATE {tool_mutrain_completion}
                   SET timecompleted = (

                        SELECT pa.timecompleted
                          FROM {tool_muprog_allocation} pa
                          JOIN {tool_muprog_program} p ON p.id = pa.programid
                          JOIN {customfield_data} cd ON cd.instanceid = p.id
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                         WHERE {tool_mutrain_completion}.fieldid = cf.id AND {tool_mutrain_completion}.instanceid = cd.instanceid
                               AND {tool_mutrain_completion}.userid = pa.userid

                   )
                 WHERE EXISTS (

                        SELECT 'x'
                          FROM {tool_muprog_allocation} pa
                          JOIN {tool_muprog_program} p ON p.id = pa.programid
                          JOIN {customfield_data} cd ON cd.instanceid = p.id
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                         WHERE {tool_mutrain_completion}.fieldid = cf.id AND {tool_mutrain_completion}.instanceid = cd.instanceid
                               AND {tool_mutrain_completion}.userid = pa.userid
                               AND {tool_mutrain_completion}.timecompleted <> pa.timecompleted AND pa.timecompleted IS NOT NULL

                 )
        ";
        $DB->execute($sql);

        // Fix contextid when program moved.
        $sql = "UPDATE {tool_mutrain_completion}
                   SET contextid = (

                        SELECT p.contextid
                          FROM {tool_muprog_program} p
                          JOIN {customfield_data} cd ON cd.instanceid = p.id
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                         WHERE {tool_mutrain_completion}.fieldid = cf.id AND {tool_mutrain_completion}.instanceid = cd.instanceid
                   )
                 WHERE EXISTS (

                        SELECT 'x'
                          FROM {tool_muprog_program} p
                          JOIN {customfield_data} cd ON cd.instanceid = p.id
                          JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                          JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                         WHERE {tool_mutrain_completion}.fieldid = cf.id AND {tool_mutrain_completion}.instanceid = cd.instanceid
                               AND {tool_mutrain_completion}.contextid <> p.contextid
                 )
         ";
        $DB->execute($sql);
    }

    /**
     * Program completion observer.
     *
     * @param \tool_muprog\event\allocation_completed $event
     */
    public static function observe_allocation_completed(\tool_muprog\event\allocation_completed $event): void {
        global $DB;

        $allocation = $event->get_record_snapshot('tool_muprog_allocation', $event->objectid);
        if ($allocation->timecompleted === null) {
            return;
        }
        $program = $event->get_record_snapshot('tool_muprog_program', $allocation->programid);

        $sql = "SELECT cf.*
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                  JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = :programid AND cd.decvalue IS NOT NULL
                 WHERE cf.type = 'mutrain'
              ORDER BY cd.id ASC";
        $params = ['programid' => $program->id, 'userid' => $allocation->userid];

        $fields = $DB->get_records_sql($sql, $params);
        if (!$fields) {
            return;
        }

        foreach ($fields as $field) {
            $record = [
                'fieldid' => $field->id,
                'instanceid' => $program->id,
                'userid' => $allocation->userid,
                'timecompleted' => $allocation->timecompleted,
                'contextid' => $program->contextid,
            ];
            mudb::upsert_record('tool_mutrain_completion', $record, ['fieldid', 'instanceid', 'userid']);
        }

        $fieldids = array_keys($fields);
        $fieldids = implode(',', $fieldids);

        $sql = "SELECT DISTINCT tf.frameworkid
                  FROM {tool_mutrain_field} tf
                  JOIN {tool_mutrain_framework} tfw ON tfw.id = tf.frameworkid
                 WHERE tfw.archived = 0 AND tf.fieldid IN ($fieldids)";
        $frameworkids = $DB->get_fieldset_sql($sql);
        foreach ($frameworkids as $frameworkid) {
            framework::sync_credits($allocation->userid, $frameworkid);
        }
    }

    /**
     * Program deletion observer.
     *
     * @param \tool_muprog\event\program_deleted $event
     */
    public static function observe_program_deleted(\tool_muprog\event\program_deleted $event): void {
        global $DB;

        if (!$DB->record_exists('customfield_field', ['type' => 'mutrain'])) {
            return;
        }

        $params = ['programid' => $event->objectid];

        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE instanceid = :programid AND EXISTS (

                    SELECT 'x'
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id
                           AND cf.type = 'mutrain'

                 )";
        $DB->execute($sql, $params);
    }

    /**
     * Program allocation deleted observer.
     *
     * @param \tool_muprog\event\allocation_deleted $event
     */
    public static function observe_allocation_deleted(\tool_muprog\event\allocation_deleted $event): void {
        global $DB;

        if (!$DB->record_exists('customfield_field', ['type' => 'mutrain'])) {
            return;
        }

        $allocation = $event->get_record_snapshot('tool_muprog_allocation', $event->objectid);
        $program = $event->get_record_snapshot('tool_muprog_program', $allocation->programid);

        $params = [
            'programid' => $program->id,
            'userid' => $allocation->userid,
        ];

        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE instanceid = :programid AND userid = :userid AND EXISTS (

                    SELECT 'x'
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.component = 'tool_muprog' AND cat.area = 'program'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id
                           AND cf.type = 'mutrain'

                 )";
        $DB->execute($sql, $params);
    }
}
