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

namespace tool_mucertify\local;

use stdClass;
use tool_muprog\local\course_reset;

/**
 * Certification helper.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification {
    /** @var string relative date disabling flag */
    public const SINCE_NEVER = 'never';
    /** @var string relative to certified date */
    public const SINCE_CERTIFIED = 'certified';
    /** @var string relative to window start date */
    public const SINCE_WINDOWSTART = 'windowstart';
    /** @var string relative to window due date */
    public const SINCE_WINDOWDUE = 'windowdue';
    /** @var string relative to window end date */
    public const SINCE_WINDOWEND = 'windowend';

    /**
     * Options for editing of certification descriptions.
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
     * Options for editing of certification image.
     *
     * @return array
     */
    public static function get_image_filemanager_options(): array {
        global $CFG;
        require_once($CFG->dirroot . '/lib/formslib.php');

        return ['maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'subdirs' => 0, 'accepted_types' => ['.jpg', '.jpeg', '.jpe', '.png']];
    }

    /**
     * Add new certification.
     *
     * NOTE: no access control done, includes hacks for form submission.
     *
     * @param stdClass $data
     * @return stdClass certification record
     */
    public static function create(stdClass $data): stdClass {
        global $DB, $CFG;
        $data = clone($data);

        $trans = $DB->start_delegated_transaction();

        $context = \context::instance_by_id($data->contextid);
        if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
            throw new \coding_exception('certification contextid must be a system or course category');
        }

        if (trim($data->fullname ?? '') === '') {
            throw new \coding_exception('certification fullname is required');
        }

        if (trim($data->idnumber ?? '') === '') {
            throw new \coding_exception('certification idnumber is required');
        }

        $editorused = false;
        $rawdescription = null;
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

        $data->periodsjson = util::json_encode(self::get_periods_defaults());

        if (isset($data->programid1)) {
            if (!$DB->record_exists('tool_muprog_program', ['id' => $data->programid1])) {
                throw new \invalid_parameter_exception('Invalid programid1');
            }
            if (isset($data->recertify)) {
                if (isset($data->programid2)) {
                    if (!$DB->record_exists('tool_muprog_program', ['id' => $data->programid2])) {
                        throw new \invalid_parameter_exception('Invalid programid2');
                    }
                } else {
                    $data->programid2 = $data->programid1;
                }
            } else {
                $data->recertify = null;
                $data->programid2 = null;
            }
        } else {
            if (isset($data->programid2) && $DB->record_exists('tool_muprog_program', ['id' => $data->programid2])) {
                throw new \invalid_parameter_exception('Unexpected programid2');
            }
            $data->programid1 = null;
            $data->programid2 = null;
        }

        $data->timecreated = time();
        $data->id = $DB->insert_record('tool_mucertify_certification', $data);

        self::update_image($data);

        if ($CFG->usetags && isset($data->tags)) {
            \core_tag_tag::set_item_tags('tool_mucertify', 'tool_mucertify_certification', $data->id, $context, $data->tags);
        }

        if ($editorused) {
            $editoroptions = self::get_description_editor_options($data->contextid);
            $data = file_postupdate_standard_editor(
                $data,
                'description',
                $editoroptions,
                $editoroptions['context'],
                'tool_mucertify',
                'description',
                $data->id
            );
            if ($rawdescription !== $data->description) {
                $DB->set_field('tool_mucertify_certification', 'description', $data->description, ['id' => $data->id]);
            }
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $data->id], '*', MUST_EXIST);

        // Save custom fields if there are any of them in the form.
        $handler = \tool_mucertify\customfield\certification_handler::create();
        $data->id = $certification->id;
        $handler->instance_form_save($data);

        $trans->allow_commit();

        util::fix_mucertify_active();

        \tool_mucertify\event\certification_created::create_from_certification($certification)->trigger();

        return $certification;
    }

    /**
     * Update general certification settings.
     *
     * @param stdClass $data
     * @return stdClass certification record
     */
    public static function update_general(stdClass $data): stdClass {
        global $DB, $CFG;

        $data = clone($data);

        $trans = $DB->start_delegated_transaction();

        $oldcertification = $DB->get_record('tool_mucertify_certification', ['id' => $data->id], '*', MUST_EXIST);

        $record = new stdClass();
        $record->id = $oldcertification->id;

        if (isset($data->contextid) && $data->contextid != $oldcertification->contextid) {
            // Cohort was moved to another context.
            $context = \context::instance_by_id($data->contextid);
            if (!($context instanceof \context_system) && !($context instanceof \context_coursecat)) {
                throw new \coding_exception('certification contextid must be a system or course category');
            }
            // The category pre-delete hook should be called before the category delete,
            // so the $oldcontext should be still here.
            $oldcontext = \context::instance_by_id($oldcertification->contextid, IGNORE_MISSING);
            if ($oldcontext) {
                get_file_storage()->move_area_files_to_new_context(
                    $oldcertification->contextid,
                    $context->id,
                    'tool_mucertify',
                    'description',
                    $data->id
                );
                // Delete tags even if they are not enabled before move,
                // tags API is not designed to deal with this,
                // we cannot create instance of deleted context.
                \core_tag_tag::set_item_tags('tool_mucertify', 'tool_mucertify_certification', $data->id, $oldcontext, null);
            }
            $record->contextid = $context->id;
        } else {
            $record->contextid = $oldcertification->contextid;
            $context = \context::instance_by_id($record->contextid);
        }

        if (isset($data->fullname)) {
            if (trim($data->fullname) === '') {
                throw new \coding_exception('certification fullname is required');
            }
            $record->fullname = $data->fullname;
        }
        if (isset($data->idnumber)) {
            if (trim($data->idnumber) === '') {
                throw new \coding_exception('certification idnumber is required');
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
                'tool_mucertify',
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
        if (isset($data->archived) && $data->archived != $oldcertification->archived) {
            debugging('Use certification::archive() and certification::restore() to change archived flag', DEBUG_DEVELOPER);
        }

        $DB->update_record('tool_mucertify_certification', $record);

        if ($CFG->usetags && isset($data->tags)) {
            \core_tag_tag::set_item_tags('tool_mucertify', 'tool_mucertify_certification', $data->id, $context, $data->tags);
        }

        $certification = self::update_image($data);

        // Save custom fields if there are any of them in the form.
        $handler = \tool_mucertify\customfield\certification_handler::create();
        $handler->instance_form_save($data);

        $trans->allow_commit();

        util::fix_mucertify_active();

        \tool_mucertify\event\certification_updated::create_from_certification($certification)->trigger();

        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, null);
        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, null);

        return $certification;
    }

    /**
     * Update certification image changed via file manager.
     *
     * @param stdClass $data
     * @return stdClass certification record
     */
    private static function update_image(stdClass $data): stdClass {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $data->id], '*', MUST_EXIST);
        $context = \context::instance_by_id($certification->contextid);

        if (isset($data->image)) {
            file_save_draft_area_files($data->image, $context->id, 'tool_mucertify', 'image', $data->id, ['subdirs' => 0, 'maxfiles' => 1]);
            $files = get_file_storage()->get_area_files($context->id, 'tool_mucertify', 'image', $data->id, '', false);
            $presenation = (array)json_decode($certification->presentationjson);
            if ($files) {
                $file = reset($files);
                $presenation['image'] = $file->get_filename();
            } else {
                unset($presenation['image']);
            }
            $DB->set_field('tool_mucertify_certification', 'presentationjson', util::json_encode($presenation), ['id' => $certification->id]);
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $data->id], '*', MUST_EXIST);
        }

        return $certification;
    }

    /**
     * Archive certification.
     *
     * @param int $certificationid
     * @return stdClass
     */
    public static function archive(int $certificationid): stdClass {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);

        if ($certification->archived) {
            return $certification;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_mucertify_certification', 'archived', '1', ['id' => $certification->id]);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certification->id], '*', MUST_EXIST);

        $trans->allow_commit();

        util::fix_mucertify_active();

        \tool_mucertify\event\certification_archived::create_from_certification($certification)->trigger();

        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, null);
        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, null);

        return $certification;
    }

    /**
     * Restore certification.
     *
     * @param int $certificationid
     * @return stdClass
     */
    public static function restore(int $certificationid): stdClass {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);

        if (!$certification->archived) {
            return $certification;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_mucertify_certification', 'archived', '0', ['id' => $certification->id]);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certification->id], '*', MUST_EXIST);

        $trans->allow_commit();

        util::fix_mucertify_active();

        \tool_mucertify\event\certification_restored::create_from_certification($certification)->trigger();

        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, null);
        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, null);

        return $certification;
    }

    /**
     * Update certification visibility.
     *
     * @param stdClass $data
     * @return stdClass certification record
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

        $oldcertification = $DB->get_record('tool_mucertify_certification', ['id' => $data->id], '*', MUST_EXIST);

        if ($oldcertification->publicaccess != $data->publicaccess) {
            $DB->set_field('tool_mucertify_certification', 'publicaccess', (int)(bool)$data->publicaccess, ['id' => $data->id]);
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
                $record = (object)['certificationid' => $data->id, 'cohortid' => $cid];
                $DB->insert_record('tool_mucertify_cohort', $record);
            }
            foreach ($oldcohorts as $cid => $unused) {
                $DB->delete_records('tool_mucertify_cohort', ['certificationid' => $data->id, 'cohortid' => $cid]);
            }
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $oldcertification->id], '*', MUST_EXIST);

        $trans->allow_commit();

        \tool_mucertify\event\certification_updated::create_from_certification($certification)->trigger();

        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, null);
        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, null);

        return $certification;
    }

    /**
     * Update certification period settings.
     *
     * @param stdClass $data
     * @return stdClass certification record
     */
    public static function update_settings(stdClass $data): stdClass {
        global $DB;

        if (empty($data->id)) {
            throw new \coding_exception('Invalid data');
        }

        $trans = $DB->start_delegated_transaction();

        $oldcertification = $DB->get_record('tool_mucertify_certification', ['id' => $data->id], '*', MUST_EXIST);
        $periods = (object)json_decode($oldcertification->periodsjson, true);

        $record = new stdClass();
        $record->id = $oldcertification->id;

        if (property_exists($data, 'programid1') && $oldcertification->programid1 != $data->programid1) {
            if ($data->programid1) {
                $program = $DB->get_record('tool_muprog_program', ['id' => $data->programid1], '*', MUST_EXIST);
                $record->programid1 = $program->id;
            } else {
                $record->programid1 = null;
            }
        } else {
            $record->programid1 = $oldcertification->programid1;
        }
        if (property_exists($data, 'resettype1')) {
            if (!array_key_exists($data->resettype1, self::get_resettype_options())) {
                throw new \invalid_parameter_exception('invalid resettype1');
            }
            $periods->resettype1 = $data->resettype1;
        }
        if (property_exists($data, 'due1')) {
            $periods->due1 = $data->due1;
            if ($periods->due1 <= 0) {
                $periods->due1 = null;
            }
        }
        if (property_exists($data, 'valid1')) {
            if (!array_key_exists($data->valid1, self::get_valid_options())) {
                throw new \invalid_parameter_exception('invalid valid1');
            }
            $periods->valid1 = $data->valid1;
        }
        if (isset($data->windowend1)) {
            if (!array_key_exists($data->windowend1['since'], self::get_windowend_options())) {
                throw new \invalid_parameter_exception('invalid windowend1');
            }
            $periods->windowend1['since'] = $data->windowend1['since'];
            if ($periods->windowend1['since'] === self::SINCE_NEVER) {
                $periods->windowend1['delay'] = null;
            } else if (array_key_exists('delay', $data->windowend1)) {
                $periods->windowend1['delay'] = util::normalise_delay($data->windowend1['delay']);
            } else {
                $periods->windowend1['delay'] = util::get_submitted_delay('windowend1', $data);
            }
        }
        if (isset($data->expiration1)) {
            if (!array_key_exists($data->expiration1['since'], self::get_expiration_options())) {
                throw new \invalid_parameter_exception('invalid expiration1');
            }
            $periods->expiration1['since'] = $data->expiration1['since'];
            if ($periods->expiration1['since'] === self::SINCE_NEVER) {
                $periods->expiration1['delay'] = null;
            } else if (array_key_exists('delay', $data->expiration1)) {
                $periods->expiration1['delay'] = util::normalise_delay($data->expiration1['delay']);
            } else {
                $periods->expiration1['delay'] = util::get_submitted_delay('expiration1', $data);
            }
        }
        if (property_exists($data, 'recertify')) {
            $record->recertify = $data->recertify;
            if (!$record->recertify) {
                $record->recertify = null;
            }
            if (!isset($oldcertification->recertify) && isset($record->recertify)) {
                if (isset($periods->expiration1)) {
                    $periods->expiration2 = $periods->expiration1;
                }
                $record->programid2 = $record->programid1;
            }
        } else {
            $record->recertify = $oldcertification->recertify;
        }

        if ($record->recertify === null) {
            // Remove program reference to prevent security issues,
            // because we do not verify access for previous program values.
            $record->programid2 = null;
            // Keep only the window2 end and expiration2 in sync, not valid2 and resettype.
            $periods->windowend2 = $periods->windowend1;
            $periods->expiration2 = $periods->expiration1;
        } else {
            if (property_exists($data, 'programid2') && $oldcertification->programid2 != $data->programid2) {
                if ($data->programid2) {
                    $program = $DB->get_record('tool_muprog_program', ['id' => $data->programid2], '*', MUST_EXIST);
                    $record->programid2 = $program->id;
                } else {
                    $record->programid2 = null;
                }
            } else {
                $record->programid2 = $oldcertification->programid2;
            }
            if ($record->programid1 && !$record->programid2) {
                $record->programid2 = $record->programid1;
            }
            if (property_exists($data, 'grace2')) {
                if (!$data->grace2) {
                    $periods->grace2 = null;
                } else {
                    $periods->grace2 = (int)$data->grace2;
                }
            }
            if (property_exists($data, 'resettype2')) {
                if (!array_key_exists($data->resettype2, self::get_resettype_options())) {
                    throw new \invalid_parameter_exception('invalid resettype2');
                }
                $periods->resettype2 = $data->resettype2;
            }
            if (property_exists($data, 'valid2')) {
                if (!array_key_exists($data->valid2, self::get_valid_options())) {
                    throw new \invalid_parameter_exception('invalid valid2');
                }
                $periods->valid2 = $data->valid2;
            }
            if (isset($data->windowend2)) {
                if (!array_key_exists($data->windowend2['since'], self::get_windowend_options())) {
                    throw new \invalid_parameter_exception('invalid windowend2');
                }
                $periods->windowend2['since'] = $data->windowend2['since'];
                if ($periods->windowend2['since'] === self::SINCE_NEVER) {
                    $periods->windowend2['delay'] = null;
                } else if (array_key_exists('delay', $data->windowend2)) {
                    $periods->windowend2['delay'] = util::normalise_delay($data->windowend2['delay']);
                } else {
                    $periods->windowend2['delay'] = util::get_submitted_delay('windowend2', $data);
                }
            }
            if (isset($data->expiration2)) {
                if (!array_key_exists($data->expiration2['since'], self::get_expiration_options())) {
                    throw new \invalid_parameter_exception('invalid expiration2');
                }
                $periods->expiration2['since'] = $data->expiration2['since'];
                if ($periods->expiration2['since'] === self::SINCE_NEVER) {
                    $periods->expiration2['delay'] = null;
                } else if (array_key_exists('delay', $data->expiration2)) {
                    $periods->expiration2['delay'] = util::normalise_delay($data->expiration2['delay']);
                } else {
                    $periods->expiration2['delay'] = util::get_submitted_delay('expiration2', $data);
                }
            }
        }

        $record->periodsjson = util::json_encode($periods);

        $DB->update_record('tool_mucertify_certification', $record);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        \tool_mucertify\event\certification_updated::create_from_certification($certification)->trigger();

        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, null);
        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, null);

        return $certification;
    }

    /**
     * Update certificate template.
     *
     * @param int $certificationid
     * @param int|null $templateid
     * @return stdClass
     */
    public static function update_certificate(int $certificationid, ?int $templateid): stdClass {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);
        if ($templateid) {
            $template = $DB->get_record('tool_certificate_templates', ['id' => $templateid], '*', MUST_EXIST);
            $templateid = $template->id;
        } else {
            $templateid = null;
        }

        if ($templateid == $certification->templateid) {
            return $certification;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('tool_mucertify_certification', 'templateid', $templateid, ['id' => $certification->id]);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certification->id], '*', MUST_EXIST);

        $trans->allow_commit();

        \tool_mucertify\event\certification_updated::create_from_certification($certification)->trigger();

        return $certification;
    }

    /**
     * Delete certification.
     *
     * @param int $id
     * @return void
     */
    public static function delete(int $id): void {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $id], '*', MUST_EXIST);
        $context = \context::instance_by_id($certification->contextid);

        // Delete notifications configuration and data.
        notification_manager::delete_certification_notifications($certification);

        $DB->delete_records('tool_mucertify_assignment', ['certificationid' => $certification->id]);
        $sources = $DB->get_records('tool_mucertify_source', ['certificationid' => $certification->id]);
        foreach ($sources as $source) {
            $DB->delete_records('tool_mucertify_request', ['sourceid' => $source->id]);
            $DB->delete_records('tool_mucertify_src_cohort', ['sourceid' => $source->id]);
        }
        unset($sources);
        $DB->delete_records('tool_mucertify_source', ['certificationid' => $certification->id]);
        $DB->delete_records('tool_mucertify_cohort', ['certificationid' => $certification->id]);
        $DB->delete_records('tool_mucertify_period', ['certificationid' => $certification->id]);

        // Certification details last.
        \core_tag_tag::set_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification->id, $context, null);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'tool_mucertify', 'description', $certification->id);
        $fs->delete_area_files($context->id, 'tool_mucertify', 'image', $certification->id);

        $DB->delete_records('tool_mucertify_certification', ['id' => $certification->id]);

        $handler = \tool_mucertify\customfield\certification_handler::create();
        $handler->delete_instance($certification->id);

        $trans->allow_commit();

        util::fix_mucertify_active();

        \tool_mucertify\event\certification_deleted::create_from_certification($certification)->trigger();

        // Deal with leftover program allocations.
        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, null);
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

        $certifications = $DB->get_records('tool_mucertify_certification', ['contextid' => $catcontext->id]);
        foreach ($certifications as $certification) {
            $data = (object)[
                'id' => $certification->id,
                'contextid' => $parentcontext->id,
            ];
            self::update_general($data);
        }
    }

    /**
     * Returns defaults for period settings in new certifications.
     *
     * @return stdClass
     */
    public static function get_periods_defaults(): stdClass {
        // NOTE: we should probably add admin settings for defaults later...
        return (object)[
            'resettype1' => course_reset::RESETTYPE_STANDARD,
            'due1' => null,
            'valid1' => self::SINCE_CERTIFIED,
            'windowend1' => ['since' => self::SINCE_NEVER, 'delay' => null],
            'expiration1' => ['since' => self::SINCE_NEVER, 'delay' => null],
            'grace2' => null,
            'resettype2' => course_reset::RESETTYPE_STANDARD,
            'valid2' => self::SINCE_WINDOWDUE,
            'windowend2' => ['since' => self::SINCE_NEVER, 'delay' => null],
            'expiration2' => ['since' => self::SINCE_NEVER, 'delay' => null],
        ];
    }

    /**
     * Returns settings for periods.
     *
     * @param stdClass $certification
     * @return stdClass
     */
    public static function get_periods_settings(stdClass $certification): stdClass {
        $resettypes = self::get_resettype_options();
        $validoptions = self::get_valid_options();
        $windowendoptions = self::get_windowend_options();
        $expirationoptions = self::get_expiration_options();
        $defaults = self::get_periods_defaults();

        $settings = (object)json_decode($certification->periodsjson, true);
        $settings->programid1 = $certification->programid1;
        $settings->programid2 = ($certification->programid2 ?? $certification->programid1);
        $settings->recertify = $certification->recertify;

        if (!isset($settings->resettype1) || !isset($resettypes[$settings->resettype1])) {
            debugging("invalid resettype1 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->resettype1 = $defaults->resettype1;
        }

        if (!property_exists($settings, 'due1')) {
            debugging("invalid due1 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->due1 = $defaults->due1;
        }

        if (!isset($settings->valid1) || !isset($validoptions[$settings->valid1])) {
            debugging("invalid valid1 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->valid1 = $defaults->valid1;
        }

        if (!property_exists($settings, 'windowend1') || !isset($windowendoptions[$settings->windowend1['since']])) {
            debugging("invalid windowend1 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->windowend1 = $defaults->windowend1;
        }

        if (!property_exists($settings, 'expiration1') || !isset($expirationoptions[$settings->expiration1['since']])) {
            debugging("invalid expiration1 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->expiration1 = $defaults->expiration1;
        }

        if (!property_exists($settings, 'grace2')) {
            debugging("invalid grace2 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->grace2 = $defaults->grace2;
        }

        if (!isset($settings->resettype2) || !isset($resettypes[$settings->resettype2])) {
            debugging("invalid resettype2 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->resettype2 = $defaults->resettype2;
        }

        if (!isset($settings->valid2) || !isset($validoptions[$settings->valid2])) {
            debugging("invalid valid2 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->valid2 = $settings->valid1;
        }

        if (!property_exists($settings, 'windowend2')  || !isset($windowendoptions[$settings->windowend2['since']])) {
            debugging("invalid windowend2 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->windowend2 = $settings->windowend1;
        }

        if (!property_exists($settings, 'expiration2') || !isset($expirationoptions[$settings->expiration2['since']])) {
            debugging("invalid expiration2 detected in $certification->id certification", DEBUG_DEVELOPER);
            $settings->expiration2 = $settings->expiration1;
        }

        return $settings;
    }

    /**
     * Recertification reset types.
     *
     * NOTE: higher number means more data is purged.
     *
     * @return array
     */
    public static function get_resettype_options(): array {
        $result = [
            course_reset::RESETTYPE_NONE => new \lang_string('resettype_none', 'tool_muprog'),
            course_reset::RESETTYPE_DEALLOCATE => new \lang_string('resettype_deallocate', 'tool_muprog'),
            course_reset::RESETTYPE_STANDARD => new \lang_string('resettype_standard', 'tool_muprog'),
            course_reset::RESETTYPE_FULL => new \lang_string('resettype_full', 'tool_muprog'),
        ];
        return $result;
    }

    /**
     * Valid until relative date types.
     *
     * @return \lang_string[]
     */
    public static function get_valid_options(): array {
        return [
            self::SINCE_CERTIFIED => new \lang_string('certifieddate', 'tool_mucertify'),
            self::SINCE_WINDOWSTART => new \lang_string('windowstartdate', 'tool_mucertify'),
            self::SINCE_WINDOWDUE => new \lang_string('windowduedate', 'tool_mucertify'),
            self::SINCE_WINDOWEND => new \lang_string('windowenddate', 'tool_mucertify'),
        ];
    }

    /**
     * Window end relative date types.
     *
     * @return \lang_string[]
     */
    public static function get_windowend_options(): array {
        return [
            self::SINCE_NEVER => new \lang_string('never', 'tool_mucertify'),
            self::SINCE_WINDOWSTART => new \lang_string('windowstartdate', 'tool_mucertify'),
            self::SINCE_WINDOWDUE => new \lang_string('windowduedate', 'tool_mucertify'),
        ];
    }

    /**
     * Certification expiration relative date types.
     *
     * @return \lang_string[]
     */
    public static function get_expiration_options(): array {
        return [
            self::SINCE_NEVER => new \lang_string('never', 'tool_mucertify'),
            self::SINCE_CERTIFIED => new \lang_string('certifieddate', 'tool_mucertify'),
            self::SINCE_WINDOWSTART => new \lang_string('windowstartdate', 'tool_mucertify'),
            self::SINCE_WINDOWDUE => new \lang_string('windowduedate', 'tool_mucertify'),
            self::SINCE_WINDOWEND => new \lang_string('windowenddate', 'tool_mucertify'),
        ];
    }
}
