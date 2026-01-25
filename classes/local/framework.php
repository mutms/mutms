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
// phpcs:disable moodle.Commenting.ValidTags.Invalid
// phpcs:disable moodle.Commenting.InlineComment.DocBlock

namespace tool_mutrain\local;

use stdClass;
use tool_mulib\local\sql;
use core\exception\invalid_parameter_exception;

/**
 * Credit framework helper class.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework {
    /**
     * Create new training framework.
     *
     * @param array $data
     * @return stdClass
     */
    public static function create(array $data): stdClass {
        global $DB;

        $data = (object)$data;

        $record = new stdClass();

        $context = \context::instance_by_id($data->contextid);
        if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
            throw new \coding_exception('framework contextid must be a system or course category');
        }
        $record->contextid = $context->id;

        $record->name = trim($data->name ?? '');
        if ($record->name === '') {
            throw new invalid_parameter_exception('framework name cannot be empty');
        }
        $record->idnumber = trim($data->idnumber ?? '');
        if ($record->idnumber === '') {
            $record->idnumber = null;
        } else {
            if ($DB->record_exists_select('tool_mutrain_framework', "LOWER(idnumber) = LOWER(?)", [$record->idnumber])) {
                throw new invalid_parameter_exception('framework idnumber must be unique');
            }
        }
        if (isset($data->description_editor)) {
            $record->description = $data->description_editor['text'];
            $record->descriptionformat = $data->description_editor['format'];
        } else {
            $record->description = $data->description ?? '';
            $record->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
        }
        $record->publicaccess = (int)($data->publicaccess ?? 0);
        if ($record->publicaccess !== 0 && $record->publicaccess !== 1) {
            throw new invalid_parameter_exception('framework public must be 1 or 0');
        }

        $data->requiredcredits = str_replace(',', '.', $data->requiredcredits ?? '0');
        if (!is_numeric($data->requiredcredits) || $data->requiredcredits <= 0) {
            throw new invalid_parameter_exception('framework requiredcredits must be positive number');
        }
        $record->requiredcredits = format_float($data->requiredcredits, 2, false);

        $record->restrictafter = $data->restrictafter ?? null;
        if ($record->restrictafter <= 0) {
            $record->restrictafter = null;
        }

        if ($context instanceof \context_system) {
            $record->restrictcontext = 0;
        } else {
            $record->restrictcontext = (int)($data->restrictcontext ?? 0);
            if ($record->restrictcontext !== 0 && $record->restrictcontext !== 1) {
                throw new invalid_parameter_exception('framework restrictcontext must be 1 or 0');
            }
        }

        $record->archived = (int)($data->archived ?? 0); // New frameworks should not be archived unless testing.
        if ($record->archived !== 0 && $record->archived !== 1) {
            throw new invalid_parameter_exception('framework archived must be 1 or 0');
        }

        $record->timecreated = time();

        $trans = $DB->start_delegated_transaction();

        $id = $DB->insert_record('tool_mutrain_framework', $record);
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $id]);

        $trans->allow_commit();

        util::fix_active_flag();

        self::sync_credits(null, $framework->id);

        return $framework;
    }

    /**
     * Update framework.
     *
     * @param array $data
     * @return stdClass
     */
    public static function update(array $data): stdClass {
        global $DB;

        $data = (object)$data;
        $oldrecord = $DB->get_record('tool_mutrain_framework', ['id' => $data->id], '*', MUST_EXIST);
        $context = \context::instance_by_id($oldrecord->contextid);

        $record = clone($oldrecord);

        if (property_exists($data, 'contextid')) {
            if ($data->contextid != $oldrecord->contextid) {
                throw new \core\exception\coding_exception('framework::update() cannot change contextid, use framework::move() instead');
            }
        }

        if (property_exists($data, 'name')) {
            $record->name = trim($data->name ?? '');
            if ($record->name === '') {
                throw new invalid_parameter_exception('framework name cannot be empty');
            }
        }
        if (property_exists($data, 'idnumber')) {
            $record->idnumber = trim($data->idnumber ?? '');
            if ($record->idnumber === '') {
                $record->idnumber = null;
            } else {
                $select = "id <> ? AND LOWER(idnumber) = LOWER(?)";
                if ($DB->record_exists_select('tool_mutrain_framework', $select, [$record->id, $record->idnumber])) {
                    throw new invalid_parameter_exception('framework idnumber must be unique');
                }
            }
        }
        if (property_exists($data, 'description_editor')) {
            $data->description = $data->description_editor['text'];
            $data->descriptionformat = $data->description_editor['format'];
            $editoroptions = self::get_description_editor_options();
            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $editoroptions,
                $editoroptions['context'],
                'tool_mutrain',
                'description',
                $data->id
            );
        }
        if (property_exists($data, 'description')) {
            $record->description = (string)$data->description;
            $record->descriptionformat = $data->descriptionformat ?? $record->descriptionformat;
        }
        if (property_exists($data, 'publicaccess')) {
            $record->publicaccess = (int)$data->publicaccess;
            if ($record->publicaccess !== 0 && $record->publicaccess !== 1) {
                throw new invalid_parameter_exception('framework public must be 1 or 0');
            }
        }
        if (property_exists($data, 'requiredcredits')) {
            $data->requiredcredits = str_replace(',', '.', $data->requiredcredits);
            if (!is_numeric($data->requiredcredits) || $data->requiredcredits <= 0) {
                throw new invalid_parameter_exception('framework requiredcredits must be positive number');
            }
            $record->requiredcredits = format_float($data->requiredcredits, 2, false);
        }

        if (property_exists($data, 'restrictafter')) {
            $record->restrictafter = $data->restrictafter;
            if ($record->restrictafter <= 0) {
                $record->restrictafter = null;
            }
        }

        if ($context instanceof \context_system) {
            $record->restrictcontext = 0;
        } else {
            if (property_exists($data, 'restrictcontext')) {
                $record->restrictcontext = (int)($data->restrictcontext ?? 0);
                if ($record->restrictcontext !== 0 && $record->restrictcontext !== 1) {
                    throw new invalid_parameter_exception('framework restrictcontext must be 1 or 0');
                }
            }
        }

        // Do not change archived flag here!
        if (isset($data->archived) && $data->archived != $oldrecord->archived) {
            debugging('Use framework::archive() and framework::restore() to change archived flag', DEBUG_DEVELOPER);
        }

        $trans = $DB->start_delegated_transaction();

        $DB->update_record('tool_mutrain_framework', $record);
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        util::fix_active_flag();

        self::sync_credits(null, $framework->id);

        return $framework;
    }

    /**
     * Move framework to different context.
     *
     * @param int $id framework id
     * @param int $contextid new context
     * @param int|null $restrictcontext
     * @return stdClass framework record
     */
    public static function move(int $id, int $contextid, ?int $restrictcontext): stdClass {
        global $DB;

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $id], '*', MUST_EXIST);

        $context = \context::instance_by_id($contextid);
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            throw new invalid_parameter_exception('System or category context expected');
        }

        $trans = $DB->start_delegated_transaction();

        $record = (object)[
            'id' => $framework->id,
            'contextid' => $context->id,
        ];

        if ($context instanceof \context_system) {
            $record->restrictcontext = 0;
        } else if (isset($restrictcontext)) {
            $record->restrictcontext = $restrictcontext;
            if ($record->restrictcontext !== 0 && $record->restrictcontext !== 1) {
                throw new invalid_parameter_exception('framework restrictcontext must be 1 or 0');
            }
        }

        $DB->update_record('tool_mutrain_framework', $record);

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $framework->id], '*', MUST_EXIST);

        $trans->allow_commit();

        self::sync_credits(null, $framework->id);

        return $framework;
    }

    /**
     * Archive framework.
     *
     * @param int $frameworkid
     * @return stdClass
     */
    public static function archive(int $frameworkid): stdClass {
        global $DB;

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);

        if ($framework->archived) {
            return $framework;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_mutrain_framework', 'archived', '1', ['id' => $framework->id]);
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $framework->id], '*', MUST_EXIST);

        $trans->allow_commit();

        self::sync_credits(null, $framework->id);

        return $framework;
    }

    /**
     * Restore framework.
     *
     * @param int $frameworkid
     * @return stdClass
     */
    public static function restore(int $frameworkid): stdClass {
        global $DB;

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);

        if (!$framework->archived) {
            return $framework;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_mutrain_framework', 'archived', '0', ['id' => $framework->id]);
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $framework->id], '*', MUST_EXIST);

        $trans->allow_commit();

        self::sync_credits(null, $framework->id);

        return $framework;
    }

    /**
     * Called before course category is deleted.
     *
     * @param stdClass $category
     * @return void
     */
    public static function pre_course_category_delete(stdClass $category): void {
        global $DB;

        $catcontext = \context_coursecat::instance($category->id);
        $parentcontext = $catcontext->get_parent_context();

        $frameworks = $DB->get_records('tool_mutrain_framework', ['contextid' => $catcontext->id]);
        foreach ($frameworks as $framework) {
            if (!$framework->archived) {
                self::archive($framework->id);
            }
            self::move($framework->id, $parentcontext->id, null);
        }
    }

    /**
     * Get all available training fields.
     *
     * @return array array of field records with extra component and area property taken from category
     */
    public static function get_all_training_fields(): array {
        global $DB;

        $classnames = \tool_mutrain\local\area\base::get_area_classes();
        $select = [];
        foreach ($classnames as $classname) {
            $select[] = '(' . $classname::get_category_select('cc') . ')';
        }
        $select = '(' . implode(' OR ', $select) . ')';

        $sql = "SELECT cf.*, cc.component, cc.area
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cc ON cc.id = cf.categoryid
                 WHERE cf.type = 'mutrain' AND $select
              ORDER BY cf.name ASC, cc.component ASC, cc.area ASC";
        return $DB->get_records_sql($sql);
    }

    /**
     * Add field.
     *
     * @param int $frameworkid
     * @param int $fieldid
     * @return stdClass
     */
    public static function field_add(int $frameworkid, int $fieldid): stdClass {
        global $DB;

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);
        $allfields = self::get_all_training_fields();
        if (!isset($allfields[$fieldid])) {
            throw new invalid_parameter_exception('Invalid field: ' . $fieldid);
        }

        $record = $DB->get_record('tool_mutrain_field', ['frameworkid' => $framework->id, 'fieldid' => $fieldid]);
        if ($record) {
            return $record;
        }

        $record = (object)[
            'frameworkid' => $framework->id,
            'fieldid' => $fieldid,
        ];
        $record->id = $DB->insert_record('tool_mutrain_field', $record);
        $record = $DB->get_record('tool_mutrain_field', ['id' => $record->id], '*', MUST_EXIST);

        self::sync_credits(null, $framework->id);

        return $record;
    }

    /**
     * Remove field.
     *
     * @param int $frameworkid
     * @param int $fieldid
     */
    public static function field_remove(int $frameworkid, int $fieldid): void {
        global $DB;

        $DB->delete_records(
            'tool_mutrain_field',
            ['frameworkid' => $frameworkid, 'fieldid' => $fieldid]
        );

        self::sync_credits(null, $frameworkid);
    }

    /**
     * Can the framework be deleted?
     *
     * @param int $frameworkid
     * @return bool
     */
    public static function is_deletable(int $frameworkid): bool {
        global $DB;

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid]);
        if (!$framework) {
            return false;
        }
        if (!$framework->archived) {
            return false;
        }

        $hook = new \tool_mutrain\hook\framework_usage($framework->id);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        if ($hook->get_usage()) {
            return false;
        }

        return true;
    }

    /**
     * Delete framework.
     *
     * NOTE: this does not check self::is_deletable().
     *
     * @param int $frameworkid
     */
    public static function delete(int $frameworkid): void {
        global $DB;

        $record = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid]);
        if (!$record) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('tool_mutrain_credit', ['frameworkid' => $record->id]);
        $DB->delete_records('tool_mutrain_field', ['frameworkid' => $record->id]);
        $DB->delete_records('tool_mutrain_framework', ['id' => $record->id]);

        $trans->allow_commit();

        self::sync_credits(null, $frameworkid);

        util::fix_active_flag();
    }

    /**
     * Options for editing of framework descriptions.
     *
     * @return array
     */
    public static function get_description_editor_options(): array {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");
        $context = \context_system::instance();
        return ['maxfiles' => 0, 'context' => $context];
    }

    /**
     * Is area compatible with training?
     *
     * @param string $component
     * @param string $area
     * @return bool
     */
    public static function is_area_compatible(string $component, string $area): bool {
        $classname = area\base::get_area_class($component, $area);
        return ($classname !== null);
    }

    /**
     * Update values in tool_mutrain_credit table.
     *
     * @param int|null $userid
     * @param int|null $frameworkid
     * @param \progress_trace|null $trace
     * @return void
     */
    public static function sync_credits(?int $userid, ?int $frameworkid, ?\progress_trace $trace = null): void {
        global $DB;

        if (!$DB->record_exists('customfield_field', ['type' => 'mutrain'])) {
            // No need to do any other processing, there cannot be any completions.
            $DB->delete_records('tool_mutrain_credit', []);
            return;
        }

        if ($trace) {
            $trace->output(self::class . '::sync_credits');
        }

        // Add missing rows.
        $sql = new sql(
            "INSERT INTO {tool_mutrain_credit} (frameworkid, userid)

             SELECT tfr.id, ctc.userid
               FROM {tool_mutrain_completion} ctc
               JOIN {user} u ON u.id = ctc.userid AND u.deleted = 0
               JOIN {customfield_field} cf ON cf.id = ctc.fieldid
               JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = ctc.instanceid AND cd.decvalue IS NOT NULL
               JOIN {tool_mutrain_field} tf ON tf.fieldid = cf.id
               JOIN {tool_mutrain_framework} tfr ON tfr.id = tf.frameworkid AND tfr.archived = 0
          LEFT JOIN {tool_mulib_context_map} cm ON cm.contextid = ctc.contextid AND cm.relatedcontextid = tfr.contextid
          LEFT JOIN {tool_mutrain_credit} cmr ON cmr.frameworkid = tfr.id AND cmr.userid = ctc.userid
              WHERE cmr.id IS NULL
                    AND (tfr.restrictafter IS NULL OR ctc.timecompleted >= tfr.restrictafter)
                    AND (tfr.restrictcontext = 0 OR cm.id IS NOT NULL)
                    /* userwhere */ /* frameworkwhere */
           GROUP BY tfr.id, ctc.userid"
        );
        if ($userid) {
            $sql = $sql->replace_comment(
                'userwhere',
                "AND ctc.userid = :userid",
                ['userid' => $userid]
            );
        }
        if ($frameworkid) {
            $sql = $sql->replace_comment(
                'frameworkwhere',
                "AND tfr.id = :frameworkid",
                ['frameworkid' => $frameworkid]
            );
        }
        $DB->execute($sql->sql, $sql->params);

        // Set credits to NULL if there are no completions.
        $sql = new sql("
            UPDATE {tool_mutrain_credit}
               SET credits = null
             WHERE credits IS NOT NULL
                   /* userwhere */ /* frameworkwhere */
                   AND NOT EXISTS (

                          SELECT 'x'
                            FROM {tool_mutrain_completion} ctc
                            JOIN {user} u ON u.id = ctc.userid AND u.deleted = 0
                            JOIN {customfield_field} cf ON cf.id = ctc.fieldid
                            JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = ctc.instanceid AND cd.decvalue IS NOT NULL
                            JOIN {tool_mutrain_field} tf ON tf.fieldid = cf.id
                            JOIN {tool_mutrain_framework} tfr ON tfr.id = tf.frameworkid AND tfr.archived = 0
                       LEFT JOIN {tool_mulib_context_map} cm ON cm.contextid = ctc.contextid AND cm.relatedcontextid = tfr.contextid
                           WHERE (tfr.restrictafter IS NULL OR ctc.timecompleted >= tfr.restrictafter)
                                 AND (tfr.restrictcontext = 0 OR cm.id IS NOT NULL)
                                 AND tfr.id = {tool_mutrain_credit}.frameworkid AND ctc.userid = {tool_mutrain_credit}.userid

                  )");
        if ($userid) {
            $sql = $sql->replace_comment(
                'userwhere',
                "AND userid = :userid",
                ['userid' => $userid]
            );
        }
        if ($frameworkid) {
            $sql = $sql->replace_comment(
                'frameworkwhere',
                "AND frameworkid = :frameworkid",
                ['frameworkid' => $frameworkid]
            );
        }
        $DB->execute($sql->sql, $sql->params);

        // Update tool_mutrain_credit records.
        $subsql = new sql("
            SELECT tfr.id AS frameworkid, ctc.userid, SUM(cd.decvalue) AS newcredits
              FROM {tool_mutrain_completion} ctc
              JOIN {user} u ON u.id = ctc.userid AND u.deleted = 0
              JOIN {customfield_field} cf ON cf.id = ctc.fieldid
              JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = ctc.instanceid AND cd.decvalue IS NOT NULL
              JOIN {tool_mutrain_field} tf ON tf.fieldid = cf.id
              JOIN {tool_mutrain_framework} tfr ON tfr.id = tf.frameworkid AND tfr.archived = 0
         LEFT JOIN {tool_mulib_context_map} cm ON cm.contextid = ctc.contextid AND cm.relatedcontextid = tfr.contextid
             WHERE (tfr.restrictafter IS NULL OR ctc.timecompleted >= tfr.restrictafter)
                   AND (tfr.restrictcontext = 0 OR cm.id IS NOT NULL)
                   /* userwhere1 */ /* frameworkwhere1 */
          GROUP BY tfr.id, ctc.userid");

        if ($DB->get_dbfamily() === 'mysql') {
            $sql = new sql(/** @lang=MySQL */"
                UPDATE {tool_mutrain_credit} AS mcr, (/* subsql */) AS xc
                   SET mcr.credits = xc.newcredits
                WHERE xc.frameworkid = mcr.frameworkid AND xc.userid = mcr.userid
                      AND (mcr.credits IS NULL OR mcr.credits <> xc.newcredits)
                      /* userwhere2 */ /* frameworkwhere2 */");
            if ($userid) {
                $sql = $sql->replace_comment(
                    'userwhere2',
                    "AND mcr.userid = :userid",
                    ['userid' => $userid]
                );
            }
            if ($frameworkid) {
                $sql = $sql->replace_comment(
                    'frameworkwhere2',
                    "AND mcr.frameworkid = :frameworkid",
                    ['frameworkid' => $frameworkid]
                );
            }
        } else {
            $sql = new sql(/** @lang=PostgreSQL */"
                UPDATE {tool_mutrain_credit}
                   SET credits = xc.newcredits
                  FROM (/* subsql */) xc
                WHERE xc.frameworkid = {tool_mutrain_credit}.frameworkid AND xc.userid = {tool_mutrain_credit}.userid
                      AND ({tool_mutrain_credit}.credits IS NULL OR {tool_mutrain_credit}.credits <> xc.newcredits)
                      /* userwhere2 */ /* frameworkwhere2 */");
            if ($userid) {
                $sql = $sql->replace_comment(
                    'userwhere2',
                    "AND {tool_mutrain_credit}.userid = :userid",
                    ['userid' => $userid]
                );
            }
            if ($frameworkid) {
                $sql = $sql->replace_comment(
                    'frameworkwhere2',
                    "AND {tool_mutrain_credit}.frameworkid = :frameworkid",
                    ['frameworkid' => $frameworkid]
                );
            }
        }
        $sql = $sql->replace_comment('subsql', $subsql);
        if ($userid) {
            $sql = $sql->replace_comment(
                'userwhere1',
                "AND ctc.userid = :userid",
                ['userid' => $userid]
            );
        }
        if ($frameworkid) {
            $sql = $sql->replace_comment(
                'frameworkwhere1',
                "AND tfr.id = :frameworkid",
                ['frameworkid' => $frameworkid]
            );
        }
        $DB->execute($sql->sql, $sql->params);

        // Trigger unreached events.
        $sql = new sql("
            SELECT mcr.*
              FROM {tool_mutrain_credit} mcr
              JOIN {tool_mutrain_framework} tfr ON tfr.id = mcr.frameworkid
             WHERE (mcr.credits IS NULL OR mcr.credits < tfr.requiredcredits) AND timereached IS NOT NULL
                   /* userwhere */ /* frameworkwhere */
          ORDER BY frameworkid, userid");
        if ($userid) {
            $sql = $sql->replace_comment(
                'userwhere',
                "AND mcr.userid = :userid",
                ['userid' => $userid]
            );
        }
        if ($frameworkid) {
            $sql = $sql->replace_comment(
                'frameworkwhere',
                "AND mcr.frameworkid = :frameworkid",
                ['frameworkid' => $frameworkid]
            );
        }
        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);
        $framework = null;
        foreach ($rs as $credit) {
            if (!$framework || $framework->id != $credit->frameworkid) {
                $framework = $DB->get_record('tool_mutrain_framework', ['id' => $credit->frameworkid]);
                if (!$framework) {
                    continue;
                }
            }
            $DB->set_field('tool_mutrain_credit', 'timereached', null, ['id' => $credit->id]);
            if ($framework->archived) {
                continue;
            }
            \tool_mutrain\event\required_credits_unreached::create_from_credit($credit, $framework)->trigger();
        }
        $rs->close();

        // Delete entries with NULL credits.
        $sql = new sql("
            DELETE
              FROM {tool_mutrain_credit}
             WHERE credits IS NULL
                   /* userwhere */ /* frameworkwhere */");
        if ($userid) {
            $sql = $sql->replace_comment(
                'userwhere',
                "AND userid = :userid",
                ['userid' => $userid]
            );
        }
        if ($frameworkid) {
            $sql = $sql->replace_comment(
                'frameworkwhere',
                "AND frameworkid = :frameworkid",
                ['frameworkid' => $frameworkid]
            );
        }
        $DB->execute($sql->sql, $sql->params);

        // Trigger reached events.
        $sql = new sql("
            SELECT mcr.*
              FROM {tool_mutrain_credit} mcr
              JOIN {tool_mutrain_framework} tfr ON tfr.id = mcr.frameworkid
             WHERE mcr.credits >= tfr.requiredcredits AND mcr.timereached IS NULL
                   AND tfr.archived = 0
                   /* userwhere */ /* frameworkwhere */
          ORDER BY mcr.frameworkid, mcr.userid");
        if ($userid) {
            $sql = $sql->replace_comment(
                'userwhere',
                "AND mcr.userid = :userid",
                ['userid' => $userid]
            );
        }
        if ($frameworkid) {
            $sql = $sql->replace_comment(
                'frameworkwhere',
                "AND mcr.frameworkid = :frameworkid",
                ['frameworkid' => $frameworkid]
            );
        }
        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);
        $framework = null;
        foreach ($rs as $credit) {
            if (!$framework || $framework->id != $credit->frameworkid) {
                $framework = $DB->get_record('tool_mutrain_framework', ['id' => $credit->frameworkid]);
                if (!$framework) {
                    continue;
                }
            }
            $credit->timereached = (string)time();
            $DB->set_field('tool_mutrain_credit', 'timereached', $credit->timereached, ['id' => $credit->id]);
            \tool_mutrain\event\required_credits_reached::create_from_credit($credit, $framework)->trigger();
        }
        $rs->close();
    }
}
