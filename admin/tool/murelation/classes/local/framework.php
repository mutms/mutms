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

namespace tool_murelation\local;

use stdClass;

/**
 * Framework helper class.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class framework {
    /** @var int Simple mode limited to 1:1 supervisor-subordinate relationship */
    public const UIMODE_SUPERVISORS = 1;
    /** @var int Advanced mode with support for teams */
    public const UIMODE_TEAMS = 2;

    /** @var int only admins and framework managers can see this in framework management UI */
    public const VISIBILITY_HIDDEN = 0;
    /** @var int users that can view or manage positions */
    public const VISIBILITY_MANAGERS = 1;
    /** @var int VISIBILITY_MANAGERS plus supervisors and course teachers */
    public const VISIBILITY_SUPERVISORS = 2;
    /** @var int VISIBILITY_SUPERVISORS plus subordinates */
    public const VISIBILITY_SUBORDINATES = 3;
    /** @var int everybody (with tenant restrictions) */
    public const VISIBILITY_EVERYBODY = 4;

    /**
     * Create new relation framework.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;

        $record = new stdClass();

        if (!is_number($data->uimode) || ($data->uimode != self::UIMODE_SUPERVISORS && $data->uimode != self::UIMODE_TEAMS)) {
            throw new \invalid_parameter_exception('invalid uimode');
        }
        $record->uimode = $data->uimode;

        $record->name = trim($data->name ?? '');
        if ($record->name === '') {
            throw new \invalid_parameter_exception('framework name cannot be empty');
        }
        if (trim($data->idnumber ?? '') === '') {
            $record->idnumber = null;
        } else {
            if ($DB->record_exists_select('tool_murelation_framework', "LOWER(idnumber) = LOWER(?)", [$data->idnumber])) {
                throw new \invalid_parameter_exception('frameword idnumber must be unique');
            }
            $record->idnumber = $data->idnumber;
        }
        $record->supervisortitle = $data->supervisortitle;
        if (trim($record->supervisortitle) === '') {
            throw new \invalid_parameter_exception('supervisortitle name cannot be empty');
        }
        $record->supervisorstitle = $data->supervisorstitle;
        if (trim($record->supervisorstitle) === '') {
            throw new \invalid_parameter_exception('supervisorstitle name cannot be empty');
        }
        $record->subordinatetitle = $data->subordinatetitle;
        if (trim($record->subordinatetitle) === '') {
            throw new \invalid_parameter_exception('subordinatetitle name cannot be empty');
        }
        $record->subordinatestitle = $data->subordinatestitle;
        if (trim($record->subordinatestitle) === '') {
            throw new \invalid_parameter_exception('subordinatestitle name cannot be empty');
        }
        if (isset($data->description_editor)) {
            $record->description = $data->description_editor['text'];
            $record->descriptionformat = $data->description_editor['format'];
        } else {
            $record->description = $data->description ?? '';
            $record->descriptionformat = $data->descriptionformat ?? FORMAT_HTML;
        }

        foreach (['managecohortid', 'supervisorcohortid', 'subordinatecohortid'] as $field) {
            if (!empty($data->$field)) {
                $cohortid = $data->$field;
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                $record->$field = $cohort->id;
            }
        }

        if (!empty($data->supervisorroleid)) {
            $role = $DB->get_record('role', ['id' => $data->supervisorroleid], '*', MUST_EXIST);
            $record->supervisorroleid = $role->id;
        }

        $options = self::get_visibility_options();
        $record->visibility = $data->visibility ?? self::VISIBILITY_SUBORDINATES;
        if (!isset($options[$record->visibility])) {
            throw new \core\exception\invalid_parameter_exception('Invalid visibility option: ' . $record->visibility);
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $record->alltenants = (int)(bool)($data->alltenants ?? 1);
        } else {
            $record->alltenants = 1;
        }

        $record->timecreated = time();

        $trans = $DB->start_delegated_transaction();

        $id = $DB->insert_record('tool_murelation_framework', $record);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $id]);

        if (!$framework->alltenants && property_exists($data, 'tenantids')) {
            foreach ($data->tenantids as $tenantid) {
                $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
                $DB->insert_record('tool_murelation_tenant_allow', [
                    'frameworkid' => $framework->id,
                    'tenantid' => $tenant->id,
                ]);
            }
        }

        $trans->allow_commit();

        util::fix_murelation_active();

        return $framework;
    }

    /**
     * Update relation framework.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $oldrecord = $DB->get_record('tool_murelation_framework', ['id' => $data->id], '*', MUST_EXIST);

        $record = clone($oldrecord);

        // No changes of UI mode!
        unset($data->uimode);

        if (property_exists($data, 'name')) {
            $record->name = trim($data->name ?? '');
            if ($record->name === '') {
                throw new \invalid_parameter_exception('framework name cannot be empty');
            }
        }
        if (property_exists($data, 'idnumber')) {
            if (trim($data->idnumber ?? '') === '') {
                $record->idnumber = null;
            } else {
                $select = "id <> ? AND LOWER(idnumber) = LOWER(?)";
                if ($DB->record_exists_select('tool_murelation_framework', $select, [$record->id, $data->idnumber])) {
                    throw new \invalid_parameter_exception('framework idnumber must be unique');
                }
                $record->idnumber = $data->idnumber;
            }
        }
        if (property_exists($data, 'supervisortitle')) {
            $record->supervisortitle = $data->supervisortitle;
            if (trim($record->supervisortitle) === '') {
                throw new \invalid_parameter_exception('supervisortitle name cannot be empty');
            }
        }
        if (property_exists($data, 'supervisorstitle')) {
            $record->supervisorstitle = $data->supervisorstitle;
            if (trim($record->supervisorstitle) === '') {
                throw new \invalid_parameter_exception('supervisorstitle name cannot be empty');
            }
        }
        if (property_exists($data, 'subordinatetitle')) {
            $record->subordinatetitle = $data->subordinatetitle;
            if (trim($record->subordinatetitle) === '') {
                throw new \invalid_parameter_exception('subordinatetitle name cannot be empty');
            }
        }
        if (property_exists($data, 'subordinatestitle')) {
            $record->subordinatestitle = $data->subordinatestitle;
            if (trim($record->subordinatestitle) === '') {
                throw new \invalid_parameter_exception('subordinatestitle name cannot be empty');
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
                'tool_murelation',
                'description',
                $data->id
            );
        }
        if (property_exists($data, 'description')) {
            $record->description = (string)$data->description;
            $record->descriptionformat = $data->descriptionformat ?? $record->descriptionformat;
        }

        foreach (['managecohortid', 'supervisorcohortid', 'subordinatecohortid'] as $field) {
            if (!property_exists($data, $field)) {
                continue;
            }
            $cohortid = $data->$field;
            if ($cohortid) {
                if ($oldrecord->$field != $cohortid) {
                    $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                    $record->$field = $cohort->id;
                }
            } else {
                $record->$field = null;
            }
        }

        if (property_exists($data, 'supervisorroleid')) {
            if (!$data->supervisorroleid) {
                $record->supervisorroleid = null;
            } else if ($oldrecord->supervisorroleid != $data->supervisorroleid) {
                $role = $DB->get_record('role', ['id' => $data->supervisorroleid], '*', MUST_EXIST);
                $record->supervisorroleid = $role->id;
            }
        }

        if (property_exists($data, 'visibility')) {
            $options = self::get_visibility_options();
            if (!isset($options[$data->visibility])) {
                throw new \core\exception\invalid_parameter_exception('Invalid visibility value: ' . $data->visibility);
            }
            $record->visibility = $data->visibility;
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if (property_exists($data, 'alltenants')) {
                $record->alltenants = (int)(bool)$data->alltenants;
            }
        } else {
            $record->alltenants = 1;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->update_record('tool_murelation_framework', $record);
        $framework = $DB->get_record('tool_murelation_framework', ['id' => $record->id], '*', MUST_EXIST);

        if ($framework->alltenants) {
            $DB->delete_records('tool_murelation_tenant_allow', ['frameworkid' => $framework->id]);
        } else if (property_exists($data, 'tenantids')) {
            $current = $DB->get_records_menu('tool_murelation_tenant_allow', ['frameworkid' => $framework->id], '', 'tenantid, 1');
            foreach ($data->tenantids as $tenantid) {
                if (isset($current[$tenantid])) {
                    unset($current[$tenantid]);
                    continue;
                }
                $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
                $DB->insert_record('tool_murelation_tenant_allow', [
                    'frameworkid' => $framework->id,
                    'tenantid' => $tenant->id,
                ]);
            }
            foreach ($current as $tenantid => $unused) {
                $DB->delete_records('tool_murelation_tenant_allow', ['tenantid' => $tenantid]);
            }
        }

        $trans->allow_commit();

        util::fix_murelation_active();

        supervisor::sync_roles();

        return $framework;
    }

    /**
     * Can the framework be deleted?
     *
     * Framework must be hidden if there are any supervisors or teams in order to delete them.
     *
     * @param int $frameworkid
     * @return bool
     */
    public static function is_deletable(int $frameworkid): bool {
        global $DB;

        $framework = $DB->get_record('tool_murelation_framework', ['id' => $frameworkid]);
        if (!$framework) {
            return false;
        }

        if ($framework->visibility != self::VISIBILITY_HIDDEN) {
            if ($DB->record_exists('tool_murelation_supervisor', ['frameworkid' => $framework->id])) {
                return false;
            }
        }

        // We can ignore orphaned subordinates here.

        return true;
    }

    /**
     * Delete relation framework.
     *
     * @param int $frameworkid
     */
    public static function delete(int $frameworkid): void {
        global $DB;

        $record = $DB->get_record('tool_murelation_framework', ['id' => $frameworkid]);
        if (!$record) {
            return;
        }

        $trans = $DB->start_delegated_transaction();

        $rs = $DB->get_recordset('tool_murelation_supervisor', ['frameworkid' => $record->id]);
        foreach ($rs as $supervisor) {
            if ($supervisor->teamcohortid) {
                supervisor::team_cohort_delete($supervisor->id);
            }
            role_unassign_all(['component' => 'tool_murelation', 'itemid' => $supervisor->id]);
        }
        $rs->close();

        $DB->delete_records('tool_murelation_supervisor', ['frameworkid' => $record->id]);
        $DB->delete_records('tool_murelation_subordinate', ['frameworkid' => $record->id]);
        $DB->delete_records('tool_murelation_tenant_allow', ['frameworkid' => $record->id]);
        $DB->delete_records('tool_murelation_framework', ['id' => $record->id]);

        $trans->allow_commit();

        util::fix_murelation_active();
    }

    /**
     * Options for UI modes.
     *
     * @return array
     */
    public static function get_uimodes(): array {
        return [
            self::UIMODE_SUPERVISORS => get_string('framework_uimode_supervisors', 'tool_murelation'),
            self::UIMODE_TEAMS => get_string('framework_uimode_teams', 'tool_murelation'),
        ];
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
     * Returns supervisor visibility options.
     *
     * Note: used primarily on user profile page.
     *
     * @return string[]
     */
    public static function get_visibility_options(): array {
        return [
            self::VISIBILITY_HIDDEN => get_string('framework_visibility_0', 'tool_murelation'),
            self::VISIBILITY_MANAGERS => get_string('framework_visibility_1', 'tool_murelation'),
            self::VISIBILITY_SUPERVISORS => get_string('framework_visibility_2', 'tool_murelation'),
            self::VISIBILITY_SUBORDINATES => get_string('framework_visibility_3', 'tool_murelation'),
            self::VISIBILITY_EVERYBODY => get_string('framework_visibility_4', 'tool_murelation'),
        ];
    }

    /**
     * Returns menu of role options.
     *
     * @param int|null $currentroleid current role if set.
     * @return array
     */
    public static function get_allowed_supervisor_roles(?int $currentroleid): array {
        global $DB;

        $allowed = get_config('tool_murelation', 'roles');

        if ($allowed) {
            $allowed = explode(',', $allowed);
            $roles = \tool_mulib\local\role_util::get_contextlevel_roles_menu(CONTEXT_USER);
            foreach ($roles as $roleid => $rolename) {
                if (!in_array($roleid, $allowed)) {
                    unset($roles[$roleid]);
                }
            }
        } else {
            $roles = [];
        }

        if ($currentroleid && !isset($roles[$currentroleid])) {
            $role = $DB->get_record('role', ['id' => $currentroleid]);
            if ($role) {
                $roles[$currentroleid] = role_get_name($role, null, ROLENAME_ORIGINAL);
            } else {
                $roles[$currentroleid] = get_string('error');
            }
        }

        return $roles;
    }
}
