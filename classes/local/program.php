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

namespace tool_muprog\local;

use stdClass;

/**
 * Program helper.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program {
    /**
     * Options for editing of program descriptions.
     *
     * @param int $contextid
     * @return array
     */
    public static function get_description_editor_options(int $contextid): array {
        global $CFG;
        require_once($CFG->dirroot . '/lib/formslib.php');

        $context = \context::instance_by_id($contextid);
        return ['maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => get_site()->maxbytes, 'context' => $context];
    }

    /**
     * Options for editing of program image.
     *
     * @return array
     */
    public static function get_image_filemanager_options(): array {
        global $CFG;
        return ['maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'subdirs' => 0, 'accepted_types' => ['.jpg', '.jpeg', '.jpe', '.png']];
    }

    /**
     * Called before course category is deleted.
     *
     * @param stdClass $category
     * @return void
     */
    public static function pre_course_category_delete(stdClass $category): void {
        global $DB;

        $catcontext = \context_coursecat::instance($category->id, MUST_EXIST);
        $parentcontext = $catcontext->get_parent_context();

        $programs = $DB->get_records('tool_muprog_program', ['contextid' => $catcontext->id]);
        foreach ($programs as $program) {
            $data = (object)[
                'id' => $program->id,
                'contextid' => $parentcontext->id,
            ];
            self::update_general($data);
        }
    }

    /**
     * Add new program.
     *
     * NOTE: no access control done, includes hacks for form submission.
     *
     * @param stdClass $data
     * @return stdClass program record
     */
    public static function create(stdClass $data): stdClass {
        global $DB, $CFG;
        $data = clone($data);

        $trans = $DB->start_delegated_transaction();

        $context = \context::instance_by_id($data->contextid);
        if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
            throw new \coding_exception('program contextid must be a system or course category');
        }

        if (strlen($data->fullname) === 0) {
            throw new \coding_exception('program fullname is required');
        }

        if (strlen($data->idnumber) === 0) {
            throw new \coding_exception('program idnumber is required');
        }

        $editorused = false;
        if (isset($data->description_editor)) {
            $rawdescription = $data->description_editor['text'];
            $data->description = $rawdescription;
            $data->descriptionformat = $data->description_editor['format'];
            $editorused = true;
        } else if (!isset($data->description)) {
            $data->description = '';
        }
        if (!isset($data->descriptionformat)) {
            $data->descriptionformat = FORMAT_HTML;
        }

        $data->presentationjson = util::json_encode([]);
        unset($data->presentation);

        $data->publicaccess = isset($data->publicaccess) ? (int)(bool)$data->publicaccess : 0;
        $data->archived = isset($data->archived) ? (int)(bool)$data->archived : 0;
        $data->creategroups = isset($data->creategroups) ? (int)(bool)$data->creategroups : 0;
        if (empty($data->timeallocationstart)) {
            $data->timeallocationstart = null;
        }
        if (empty($data->timeallocationend)) {
            $data->timeallocationend = null;
        }

        if (isset($data->startdate)) {
            $data->startdate = (array)$data->startdate;
            $types = self::get_program_startdate_types();
            if (!isset($types[$data->startdate['type']])) {
                throw new \invalid_parameter_exception('Invalid start type');
            }
            $json = ['type' => $data->startdate['type']];
            if ($data->startdate['type'] === 'date') {
                if (empty($data->startdate['date']) || !is_number($data->startdate['date'])) {
                    throw new \invalid_parameter_exception('invalid start date');
                }
                $json['date'] = (int)$data->startdate['date'];
            } else if ($data->startdate['type'] === 'delay') {
                if (!self::validate_delay_value($data->startdate['delay'] ?? '')) {
                    throw new \invalid_parameter_exception('invalid start delay');
                }
                $json['delay'] = $data->startdate['delay'];
            }
        } else {
            $json = ['type' => 'allocation'];
        }
        $data->startdatejson = util::json_encode($json);

        if (isset($data->duedate)) {
            $data->duedate = (array)$data->duedate;
            $types = self::get_program_duedate_types();
            if (!isset($types[$data->duedate['type']])) {
                throw new \invalid_parameter_exception('Invalid due type');
            }
            $json = ['type' => $data->duedate['type']];
            if ($data->duedate['type'] === 'date') {
                if (empty($data->duedate['date']) || !is_number($data->duedate['date'])) {
                    throw new \invalid_parameter_exception('invalid due date');
                }
                $json['date'] = (int)$data->duedate['date'];
            } else if ($data->duedate['type'] === 'delay') {
                if (!self::validate_delay_value($data->duedate['delay'] ?? '')) {
                    throw new \invalid_parameter_exception('invalid due delay');
                }
                $json['delay'] = $data->duedate['delay'];
            }
        } else {
            $json = ['type' => 'notset'];
        }
        $data->duedatejson = util::json_encode($json);

        if (isset($data->enddate)) {
            $data->enddate = (array)$data->enddate;
            $types = self::get_program_enddate_types();
            if (!isset($types[$data->enddate['type']])) {
                throw new \invalid_parameter_exception('Invalid end type');
            }
            $json = ['type' => $data->enddate['type']];
            if ($data->enddate['type'] === 'date') {
                if (empty($data->enddate['date']) || !is_number($data->enddate['date'])) {
                    throw new \invalid_parameter_exception('invalid end date');
                }
                $json['date'] = (int)$data->enddate['date'];
            } else if ($data->enddate['type'] === 'delay') {
                if (!self::validate_delay_value($data->enddate['delay'] ?? '')) {
                    throw new \invalid_parameter_exception('invalid end delay');
                }
                $json['delay'] = $data->enddate['delay'];
            }
        } else {
            $json = ['type' => 'notset'];
        }
        $data->enddatejson = util::json_encode($json);

        $data->timecreated = time();
        $data->id = $DB->insert_record('tool_muprog_program', $data);

        $program = self::update_image($data);

        if ($CFG->usetags && isset($data->tags)) {
            \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $data->id, $context, $data->tags);
        }

        if ($editorused) {
            $editoroptions = self::get_description_editor_options($data->contextid);
            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $editoroptions,
                $editoroptions['context'],
                'tool_muprog',
                'description',
                $data->id
            );
            if ($rawdescription !== $data->description) {
                $DB->set_field('tool_muprog_program', 'description', $data->description, ['id' => $data->id]);
            }
        }

        $sequence = [
            'children' => [],
            'type' => content\set::SEQUENCE_TYPE_ALLINANYORDER,
        ];

        $item = new \stdClass();
        $item->programid = $data->id;
        $item->topitem = 1;
        $item->courseid = null;
        $item->fullname = $data->fullname;
        $item->sequencejson = util::json_encode($sequence);
        $item->minprerequisites = 1; // Prevent completion.
        $item->points = 1;
        $item->minpoints = null;
        $DB->insert_record('tool_muprog_item', $item);

        $program = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);

        // Save custom fields if there are any of them in the form.
        $handler = \tool_muprog\customfield\program_handler::create();
        $data->id = $program->id;
        $handler->instance_form_save($data);

        \tool_muprog\event\program_created::create_from_program($program)->trigger();

        $trans->allow_commit();

        util::fix_muprog_active();

        allocation::fix_allocation_sources($program->id, null);
        allocation::fix_enrol_instances($program->id);
        allocation::fix_user_enrolments($program->id, null);

        return $program;
    }

    /**
     * Update general program settings.
     *
     * @param stdClass $data
     * @return stdClass program record
     */
    public static function update_general(stdClass $data): stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $data = clone($data);

        $trans = $DB->start_delegated_transaction();

        $oldprogram = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);

        $record = new stdClass();
        $record->id = $oldprogram->id;

        if (isset($data->contextid) && $data->contextid != $oldprogram->contextid) {
            // Cohort was moved to another context.
            $context = \context::instance_by_id($data->contextid);
            if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
                throw new \coding_exception('program contextid must be a system or course category');
            }
            // The category pre-delete hook should be called before the category delete,
            // so the $oldcontext should be still here.
            $oldcontext = \context::instance_by_id($oldprogram->contextid, IGNORE_MISSING);
            if ($oldcontext) {
                get_file_storage()->move_area_files_to_new_context(
                    $oldprogram->contextid,
                    $context->id,
                    'tool_muprog',
                    'description',
                    $data->id
                );
                // Delete tags even if they are not enabled before move,
                // tags API is not designed to deal with this,
                // we cannot create instance of deleted context.
                \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $data->id, $oldcontext, null);
            }
            $record->contextid = $context->id;
        } else {
            $record->contextid = $oldprogram->contextid;
            $context = \context::instance_by_id($record->contextid);
        }

        if (isset($data->fullname)) {
            if (strlen($data->fullname) === 0) {
                throw new \coding_exception('program fullname is required');
            }
            $record->fullname = $data->fullname;
        }
        if (isset($data->idnumber)) {
            if (strlen($data->idnumber) === 0) {
                throw new \coding_exception('program idnumber is required');
            }
            $record->idnumber = $data->idnumber;
        }

        if (isset($data->description_editor)) {
            $data->description = $data->description_editor['text'];
            $data->descriptionformat = $data->description_editor['format'];
            $editoroptions = self::get_description_editor_options($data->contextid);
            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $editoroptions,
                $editoroptions['context'],
                'tool_muprog',
                'description',
                $data->id
            );
        }
        if (isset($data->description)) {
            $record->description = $data->description;
        }
        if (isset($data->descriptionformat)) {
            $record->descriptionformat = $data->descriptionformat;
        }
        // Do not change archived flag here!
        if (isset($data->archived) && $data->archived != $oldprogram->archived) {
            debugging('Use program::archive() and program::restore() to change archived flag', DEBUG_DEVELOPER);
        }
        if (isset($data->creategroups)) {
            $record->creategroups = (int)(bool)$data->creategroups;
        }

        $invalidatecalendarevents = false;
        if (isset($record->fullname) && $record->fullname != $oldprogram->fullname) {
            $invalidatecalendarevents = true;
        } else if (isset($record->description) && $record->description != $oldprogram->description) {
            $invalidatecalendarevents = true;
        }

        $DB->update_record('tool_muprog_program', $record);

        if ($CFG->usetags && isset($data->tags)) {
            \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $data->id, $context, $data->tags);
        }

        $program = self::update_image($data);

        // Save custom fields if there are any of them in the form.
        $handler = \tool_muprog\customfield\program_handler::create();
        $handler->instance_form_save($data);

        $item = $DB->get_record('tool_muprog_item', ['programid' => $program->id, 'topitem' => 1], '*', MUST_EXIST);
        if ($item->fullname !== $program->fullname) {
            $item->fullname = $program->fullname;
            $DB->update_record('tool_muprog_item', $item);
        }

        // Update group names only if program name changed.
        if ($oldprogram->fullname !== $program->fullname) {
            $sql = "SELECT g.*
                      FROM {groups} g
                      JOIN {tool_muprog_group} pg ON pg.groupid = g.id
                     WHERE pg.programid = :programid
                  ORDER BY g.id ASC";
            $params = ['programid' => $program->id];
            $groups = $DB->get_records_sql($sql, $params);
            foreach ($groups as $group) {
                if ($group->name !== $program->fullname) {
                    $group->name = $program->fullname;
                    groups_update_group($group);
                }
            }
        }

        if ($invalidatecalendarevents) {
            calendar::invalidate_program_events($program->id);
        }

        $program = $DB->get_record('tool_muprog_program', ['id' => $program->id], '*', MUST_EXIST);

        \tool_muprog\event\program_updated::create_from_program($program)->trigger();

        $trans->allow_commit();

        util::fix_muprog_active();

        allocation::fix_allocation_sources($program->id, null);
        allocation::fix_enrol_instances($program->id);
        allocation::fix_user_enrolments($program->id, null);
        calendar::fix_program_events($program);

        return $program;
    }

    /**
     * Update program image changed via file manager.
     *
     * @param stdClass $data
     * @return stdClass
     */
    private static function update_image(stdClass $data): stdClass {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);

        if (isset($data->image)) {
            file_save_draft_area_files($data->image, $context->id, 'tool_muprog', 'image', $data->id, ['subdirs' => 0, 'maxfiles' => 1]);
            $files = get_file_storage()->get_area_files($context->id, 'tool_muprog', 'image', $data->id, '', false);
            $presenation = (array)json_decode($program->presentationjson);
            if ($files) {
                $file = reset($files);
                $presenation['image'] = $file->get_filename();
            } else {
                unset($presenation['image']);
            }
            $DB->set_field('tool_muprog_program', 'presentationjson', util::json_encode($presenation), ['id' => $program->id]);
            $program = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);
        }

        return $program;
    }

    /**
     * Archive program.
     *
     * @param int $programid
     * @return stdClass
     */
    public static function archive(int $programid): stdClass {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);

        if ($program->archived) {
            return $program;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_muprog_program', 'archived', '1', ['id' => $program->id]);
        $program = $DB->get_record('tool_muprog_program', ['id' => $program->id], '*', MUST_EXIST);

        \tool_muprog\event\program_archived::create_from_program($program)->trigger();

        $trans->allow_commit();

        util::fix_muprog_active();

        allocation::fix_allocation_sources($program->id, null);
        allocation::fix_enrol_instances($program->id);
        allocation::fix_user_enrolments($program->id, null);
        calendar::fix_program_events($program);

        return $program;
    }

    /**
     * Restore program.
     *
     * @param int $programid
     * @return stdClass
     */
    public static function restore(int $programid): stdClass {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);

        if (!$program->archived) {
            return $program;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_muprog_program', 'archived', '0', ['id' => $program->id]);
        $program = $DB->get_record('tool_muprog_program', ['id' => $program->id], '*', MUST_EXIST);

        \tool_muprog\event\program_restored::create_from_program($program)->trigger();

        $trans->allow_commit();

        util::fix_muprog_active();

        allocation::fix_allocation_sources($program->id, null);
        allocation::fix_enrol_instances($program->id);
        allocation::fix_user_enrolments($program->id, null);
        calendar::fix_program_events($program);

        return $program;
    }

    /**
     * Update program visibility.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update_visibility(stdClass $data): stdClass {
        global $DB;

        if (
            (isset($data->cohortids) && !is_array($data->cohortids))
            || empty($data->id) || !isset($data->publicaccess)
        ) {
            throw new \coding_exception('Invalid data');
        }

        if (isset($data->cohorts)) {
            debugging('use cohortids key instead of cohorts', DEBUG_DEVELOPER);
        }

        $trans = $DB->start_delegated_transaction();

        $oldprogram = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);

        if ($oldprogram->publicaccess != $data->publicaccess) {
            $DB->set_field('tool_muprog_program', 'publicaccess', (int)(bool)$data->publicaccess, ['id' => $data->id]);
        }

        if (isset($data->cohortids)) {
            $oldcohorts = management::fetch_current_cohorts_menu($data->id);
            $oldcohorts = array_keys($oldcohorts);
            $oldcohorts = array_flip($oldcohorts);
            foreach ($data->cohortids as $cid) {
                if (isset($oldcohorts[$cid])) {
                    unset($oldcohorts[$cid]);
                    continue;
                }
                $record = (object)['programid' => $data->id, 'cohortid' => $cid];
                $DB->insert_record('tool_muprog_cohort', $record);
            }
            foreach ($oldcohorts as $cid => $unused) {
                $DB->delete_records('tool_muprog_cohort', ['programid' => $data->id, 'cohortid' => $cid]);
            }
        }

        $program = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);

        \tool_muprog\event\program_updated::create_from_program($program)->trigger();

        $trans->allow_commit();

        allocation::fix_allocation_sources($program->id, null);
        allocation::fix_enrol_instances($program->id);
        allocation::fix_user_enrolments($program->id, null);

        return $program;
    }

    /**
     * Update program allocation settings.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update_allocation(stdClass $data): stdClass {
        global $DB;

        if (!isset($data->id)) {
            throw new \coding_exception('Invalid data');
        }

        $oldprogram = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);

        $updated = false;
        $record = new \stdClass();
        $record->id = $data->id;
        if (property_exists($data, 'timeallocationstart')) {
            $record->timeallocationstart = $data->timeallocationstart;
            if (!$record->timeallocationstart) {
                $record->timeallocationstart = null;
            }
            if ($record->timeallocationstart !== $oldprogram->timeallocationstart) {
                $updated = true;
            }
        } else {
            $record->timeallocationstart = $oldprogram->timeallocationstart;
        }
        if (property_exists($data, 'timeallocationend')) {
            $record->timeallocationend = $data->timeallocationend;
            if (!$record->timeallocationend) {
                $record->timeallocationend = null;
            }
            if ($record->timeallocationend !== $oldprogram->timeallocationend) {
                $updated = true;
            }
        } else {
            $record->timeallocationend = $oldprogram->timeallocationend;
        }
        if (
            $record->timeallocationstart && $record->timeallocationend
            && $record->timeallocationstart >= $record->timeallocationend
        ) {
            throw new \coding_exception('Allocation start must be earlier than end');
        }

        if ($updated) {
            $trans = $DB->start_delegated_transaction();

            $DB->update_record('tool_muprog_program', $record);
            $program = $DB->get_record('tool_muprog_program', ['id' => $record->id], '*', MUST_EXIST);

            \tool_muprog\event\program_updated::create_from_program($program)->trigger();

            $trans->allow_commit();
        } else {
            $program = $oldprogram;
        }

        allocation::fix_allocation_sources($program->id, null);
        allocation::fix_enrol_instances($program->id);
        allocation::fix_user_enrolments($program->id, null);

        if ($updated) {
            calendar::fix_program_events($program);
        }

        return $program;
    }

    /**
     * Import allocation settings, scheduling and sources from another program.
     *
     * NOTE: this does not trigger fixing of enrolments and allocations.
     *
     * @param stdClass $data data from \tool_muprog\local\form\program_allocation_import_confirmation
     * @return stdClass updated program record
     */
    public static function import_allocation(stdClass $data): stdClass {
        global $DB;

        $targetprogram = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);
        $fromprogram = $DB->get_record('tool_muprog_program', ['id' => $data->fromprogram], '*', MUST_EXIST);

        $record = [];

        if (!empty($data->importallocationstart)) {
            $record['timeallocationstart'] = $fromprogram->timeallocationstart;
        }
        if (!empty($data->importallocationend)) {
            $record['timeallocationend'] = $fromprogram->timeallocationend;
        }
        if (!empty($data->importprogramstart)) {
            $record['startdatejson'] = $fromprogram->startdatejson;
        }
        if (!empty($data->importprogramdue)) {
            $record['duedatejson'] = $fromprogram->duedatejson;
        }
        if (!empty($data->importprogramend)) {
            $record['enddatejson'] = $fromprogram->enddatejson;
        }

        $trans = $DB->start_delegated_transaction();

        $updated = false;
        if ($record) {
            $record = (object)$record;
            $record->id = $targetprogram->id;
            if (!property_exists($record, 'timeallocationstart')) {
                $record->timeallocationstart = $targetprogram->timeallocationstart;
            }
            if (!property_exists($record, 'timeallocationend')) {
                $record->timeallocationend = $targetprogram->timeallocationend;
            }
            if (
                $record->timeallocationstart && $record->timeallocationend
                && $record->timeallocationstart >= $record->timeallocationend
            ) {
                throw new \coding_exception('Allocation start must be earlier than end');
            }
            $updated = true;
            $DB->update_record('tool_muprog_program', $record);
            $targetprogram = $DB->get_record('tool_muprog_program', ['id' => $record->id], '*', MUST_EXIST);
        }

        /** @var \tool_muprog\local\source\base[] $sourceclasses */
        $sourceclasses = \tool_muprog\local\allocation::get_source_classes();
        foreach ($sourceclasses as $sourcetype => $sourceclass) {
            if (empty($data->{'importsource' . $sourcetype})) {
                continue;
            }
            if (!$sourceclass::is_import_allowed($fromprogram, $targetprogram)) {
                throw new \coding_exception('Cannot import source ' . $sourcetype);
            }
            $sourceclass::import_source_data($data->fromprogram, $data->id);

            $targetprogram = $DB->get_record('tool_muprog_program', ['id' => $targetprogram->id], '*', MUST_EXIST);
            \tool_muprog\event\program_updated::create_from_program($targetprogram)->trigger();

            $updated = true;
        }

        $trans->allow_commit();

        allocation::fix_allocation_sources($targetprogram->id, null);
        allocation::fix_enrol_instances($targetprogram->id);
        allocation::fix_user_enrolments($targetprogram->id, null);

        if ($updated) {
            calendar::fix_program_events($targetprogram);
        }

        return $targetprogram;
    }

    /**
     * Returns all types of program start date.
     * @return array
     */
    public static function get_program_startdate_types(): array {
        return [
            'allocation' => get_string('programstart_allocation', 'tool_muprog'),
            'date' => get_string('fixeddate', 'tool_muprog'),
            'delay' => get_string('programstart_delay', 'tool_muprog'),
        ];
    }

    /**
     * Returns all types of program due date.
     * @return array
     */
    public static function get_program_duedate_types(): array {
        return [
            'notset' => get_string('notset', 'tool_muprog'),
            'date' => get_string('fixeddate', 'tool_muprog'),
            'delay' => get_string('programdue_delay', 'tool_muprog'),
        ];
    }

    /**
     * Returns all types of program end date.
     * @return array
     */
    public static function get_program_enddate_types(): array {
        return [
            'notset' => get_string('notset', 'tool_muprog'),
            'date' => get_string('fixeddate', 'tool_muprog'),
            'delay' => get_string('programend_delay', 'tool_muprog'),
        ];
    }

    /**
     * Parse form data for scheduling settings.
     *
     * @param string $name
     * @param stdClass $data
     */
    protected static function process_submitted_program_allocation_delay(string $name, stdClass $data): string {
        $type = $data->{'program' . $name . '_delay'}['type'];
        $value = (int)$data->{'program' . $name . '_delay'}['value'];
        unset($data->{'program' . $name . '_delay'});

        if ($value <= 0) {
            throw new \coding_exception('Invalid delay value');
        }
        if ($type === 'months') {
            return 'P' . $value . 'M';
        } else if ($type === 'days') {
            return 'P' . $value . 'D';
        } else if ($type === 'hours') {
            return 'PT' . $value . 'H';
        }
        throw new \coding_exception('Invalid delay type');
    }

    /**
     * Validate value of delay setting .
     * @param string $string
     * @return bool
     */
    protected static function validate_delay_value(string $string): bool {
        if (preg_match('/^P[1-9][0-9]*M$/', $string)) {
            return true;
        } else if (preg_match('/^P[1-9][0-9]*D$/', $string)) {
            return true;
        } else if (preg_match('/^PT[1-9][0-9]*H$/', $string)) {
            return true;
        }
        return false;
    }

    /**
     * Update program scheduling.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update_scheduling(stdClass $data): stdClass {
        global $DB;

        if (!isset($data->id) || !isset($data->programstart_type) || !isset($data->programdue_type) || !isset($data->programend_type)) {
            throw new \coding_exception('Invalid data');
        }

        $trans = $DB->start_delegated_transaction();

        $oldprogram = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);

        $record = new \stdClass();
        $record->id = $data->id;

        $types = self::get_program_startdate_types();
        if (!isset($types[$data->programstart_type])) {
            throw new \coding_exception('Invalid date type');
        }
        $json = ['type' => $data->programstart_type];
        if ($data->programstart_type === 'date') {
            $json['date'] = $data->programstart_date;
        } else if ($data->programstart_type === 'delay') {
            $json['delay'] = self::process_submitted_program_allocation_delay('start', $data);
        }
        $record->startdatejson = util::json_encode($json);

        $types = self::get_program_duedate_types();
        if (!isset($types[$data->programdue_type])) {
            throw new \coding_exception('Invalid date type');
        }
        $json = ['type' => $data->programdue_type];
        if ($data->programdue_type === 'date') {
            $json['date'] = $data->programdue_date;
        } else if ($data->programdue_type === 'delay') {
            $json['delay'] = self::process_submitted_program_allocation_delay('due', $data);
        }
        $record->duedatejson = util::json_encode($json);

        $types = self::get_program_enddate_types();
        if (!isset($types[$data->programend_type])) {
            throw new \coding_exception('Invalid date type');
        }
        $json = ['type' => $data->programend_type];
        if ($data->programend_type === 'date') {
            $json['date'] = $data->programend_date;
        } else if ($data->programend_type === 'delay') {
            $json['delay'] = self::process_submitted_program_allocation_delay('end', $data);
        }
        $record->enddatejson = util::json_encode($json);

        $DB->update_record('tool_muprog_program', $record);

        $program = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);

        \tool_muprog\event\program_updated::create_from_program($program)->trigger();

        $trans->allow_commit();

        allocation::fix_allocation_sources($program->id, null);
        allocation::fix_enrol_instances($program->id);
        allocation::fix_user_enrolments($program->id, null);
        calendar::fix_program_events($program);

        return $program;
    }

    /**
     * Delete program.
     *
     * @param int $id
     * @return void
     */
    public static function delete(int $id): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $trans = $DB->start_delegated_transaction();

        $program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);

        $pgs = $DB->get_records('tool_muprog_group', ['programid' => $program->id]);
        foreach ($pgs as $pg) {
            groups_delete_group($pg->groupid);
        }

        // Delete notifications configuration and data.
        notification_manager::delete_program_notifications($program);

        $items = $DB->get_records('tool_muprog_item', ['programid' => $program->id]);
        foreach ($items as $item) {
            $DB->delete_records('tool_muprog_evidence', ['itemid' => $item->id]);
            $DB->delete_records('tool_muprog_completion', ['itemid' => $item->id]);
            $DB->delete_records('tool_muprog_prerequisite', ['itemid' => $item->id]);
            $DB->delete_records('tool_muprog_prerequisite', ['prerequisiteitemid' => $item->id]);
        }
        unset($items);
        $DB->delete_records('tool_muprog_allocation', ['programid' => $program->id]);
        $sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id]);
        foreach ($sources as $source) {
            $DB->delete_records('tool_muprog_request', ['sourceid' => $source->id]);
            $DB->delete_records('tool_muprog_src_cohort', ['sourceid' => $source->id]);
        }
        unset($sources);
        $DB->delete_records('tool_muprog_source', ['programid' => $program->id]);
        $DB->delete_records('tool_muprog_cohort', ['programid' => $program->id]);
        $DB->delete_records('tool_muprog_item', ['programid' => $program->id]);

        $DB->delete_records('tool_muprog_cert_issue', ['programid' => $program->id]);
        $DB->delete_records('tool_muprog_cert', ['programid' => $program->id]);

        // Program details last.
        \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $program->id, $context, null);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'tool_muprog', 'description', $program->id);
        $fs->delete_area_files($context->id, 'tool_muprog', 'image', $program->id);

        $DB->delete_records('tool_muprog_program', ['id' => $program->id]);

        $handler = \tool_muprog\customfield\program_handler::create();
        $handler->delete_instance($program->id);

        \tool_muprog\event\program_deleted::create_from_program($program)->trigger();

        $trans->allow_commit();

        util::fix_muprog_active();

        // Delete enrolment instances.
        allocation::fix_enrol_instances($program->id);

        calendar::delete_program_events($program->id);
    }

    /**
     * Load program content.
     *
     * @param int $programid
     * @return content\top
     */
    public static function load_content(int $programid): content\top {
        return content\top::load($programid);
    }
}
