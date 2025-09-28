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

namespace tool_mutrain\local;

use stdClass;

/**
 * Framework helper class.
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
            throw new \coding_exception('training framework contextid must be a system or course category');
        }
        $record->contextid = $context->id;

        $record->name = trim($data->name ?? '');
        if ($record->name === '') {
            throw new \invalid_parameter_exception('framework name cannot be empty');
        }
        $record->idnumber = trim($data->idnumber ?? '');
        if ($record->idnumber === '') {
            $record->idnumber = null;
        } else {
            if ($DB->record_exists_select('tool_mutrain_framework', "LOWER(idnumber) = LOWER(?)", [$record->idnumber])) {
                throw new \invalid_parameter_exception('framework idnumber must be unique');
            }
        }
        if (isset($data->description_editor)) {
            $record->description = $data->description_editor['text'];
            $record->descriptionformat = $data->description_editor['format'];
        } else {
            $record->description = $data->description ?? '';
            $record->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
        }

        $record->restrictedcompletion = (int)($data->restrictedcompletion ?? 0);
        if ($record->restrictedcompletion !== 0 && $record->restrictedcompletion !== 1) {
            throw new \invalid_parameter_exception('framework restrictedcompletion must be 1 or 0');
        }

        $record->publicaccess = (int)($data->publicaccess ?? 0);
        if ($record->publicaccess !== 0 && $record->publicaccess !== 1) {
            throw new \invalid_parameter_exception('framework public must be 1 or 0');
        }

        $record->requiredtraining = (int)$data->requiredtraining;
        if ($record->requiredtraining <= 0) {
            throw new \invalid_parameter_exception('framework requiredtraining must be positive integer');
        }

        $record->archived = (int)($data->archived ?? 0); // New frameworks should not be archived unless testing.
        if ($record->archived !== 0 && $record->archived !== 1) {
            throw new \invalid_parameter_exception('framework archived must be 1 or 0');
        }

        $record->timecreated = time();

        $trans = $DB->start_delegated_transaction();

        $id = $DB->insert_record('tool_mutrain_framework', $record);
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $id]);

        $trans->allow_commit();

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

        $record = clone($oldrecord);

        if (isset($data->contextid) && $data->contextid != $oldrecord->contextid) {
            // Cohort was moved to another context.
            $context = \context::instance_by_id($data->contextid);
            if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
                throw new \coding_exception('program contextid must be a system or course category');
            }
            $record->contextid = $context->id;
        } else {
            $context = \context::instance_by_id($record->contextid);
        }

        if (property_exists($data, 'name')) {
            $record->name = trim($data->name ?? '');
            if ($record->name === '') {
                throw new \invalid_parameter_exception('framework name cannot be empty');
            }
        }
        if (property_exists($data, 'idnumber')) {
            $record->idnumber = trim($data->idnumber ?? '');
            if ($record->idnumber === '') {
                $record->idnumber = null;
            } else {
                $select = "id <> ? AND LOWER(idnumber) = LOWER(?)";
                if ($DB->record_exists_select('tool_mutrain_framework', $select, [$record->id, $record->idnumber])) {
                    throw new \invalid_parameter_exception('framework idnumber must be unique');
                }
            }
        }
        if (property_exists($data, 'description_editor')) {
            $data->description = $data->description_editor['text'];
            $data->descriptionformat = $data->description_editor['format'];
            $editoroptions = self::get_description_editor_options($oldrecord->contextid);
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
        if (property_exists($data, 'restrictedcompletion')) {
            $record->restrictedcompletion = (int)$data->restrictedcompletion;
            if ($record->restrictedcompletion !== 0 && $record->restrictedcompletion !== 1) {
                throw new \invalid_parameter_exception('framework restrictedcompletion must be 1 or 0');
            }
        }
        if (property_exists($data, 'publicaccess')) {
            $record->publicaccess = (int)$data->publicaccess;
            if ($record->publicaccess !== 0 && $record->publicaccess !== 1) {
                throw new \invalid_parameter_exception('framework public must be 1 or 0');
            }
        }
        if (property_exists($data, 'requiredtraining')) {
            $record->requiredtraining = (int)$data->requiredtraining;
            if ($record->requiredtraining <= 0) {
                throw new \invalid_parameter_exception('framework requiredtraining must be positive integer');
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

        // NOTE: programs will be updated later via cron.

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

        return $framework;
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
            throw new \invalid_parameter_exception('Invalid field: ' . $fieldid);
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
        return $DB->get_record('tool_mutrain_field', ['id' => $record->id], '*', MUST_EXIST);
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

        $DB->delete_records('tool_mutrain_field', ['frameworkid' => $record->id]);
        $DB->delete_records('tool_mutrain_framework', ['id' => $record->id]);

        $trans->allow_commit();
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
}
