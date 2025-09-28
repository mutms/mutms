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

namespace tool_muprog\local\source;

use tool_mulib\output\header_actions;
use stdClass;

/**
 * Manual program allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manual extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'manual';
    }

    /**
     * Manual allocation source cannot be completely prevented.
     *
     * @param stdClass $program
     * @return bool
     */
    public static function is_new_allowed(\stdClass $program): bool {
        return true;
    }

    /**
     * Can settings of this source be imported to other program?
     *
    /**
     * Can settings of this source be imported to other program?
     *
     * @param stdClass $fromprogram
     * @param stdClass $targetprogram
     * @return bool
     */
    public static function is_import_allowed(stdClass $fromprogram, stdClass $targetprogram): bool {
        global $DB;

        if (!$DB->record_exists('tool_muprog_source', ['type' => static::get_type(), 'programid' => $fromprogram->id])) {
            return false;
        }

        if (!$DB->record_exists('tool_muprog_source', ['type' => static::get_type(), 'programid' => $targetprogram->id])) {
            if (!static::is_new_allowed($targetprogram)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is it possible to manually archive and unarchive user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function is_allocation_archive_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    /**
     * Is it possible to manually delete user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function is_allocation_delete_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    /**
     * Is it possible to manually allocate users to this program?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @return bool
     */
    public static function is_allocation_possible(\stdClass $program, \stdClass $source): bool {
        if ($program->archived) {
            return false;
        }
        if ($program->timeallocationstart && $program->timeallocationstart > time()) {
            return false;
        } else if ($program->timeallocationend && $program->timeallocationend < time()) {
            return false;
        }
        return true;
    }

    #[\Override]
    public static function add_management_program_users_actions(header_actions $actions, stdClass $program, stdClass $source): void {
        if ($program->id != $source->programid || $source->type !== self::get_type()) {
            throw new \coding_exception('Parameter mismatch detected');
        }

        if (!self::is_allocation_possible($program, $source)) {
            return;
        }

        $context = \context::instance_by_id($program->contextid);
        if (has_capability('tool/muprog:allocate', $context)) {
            $url = new \moodle_url('/admin/tool/muprog/management/source_manual_allocate.php', ['sourceid' => $source->id]);
            $button = new \tool_mulib\output\ajax_form\button($url, get_string('source_manual_allocateusers', 'tool_muprog'));
            $actions->add_button($button);

            $url = new \moodle_url('/admin/tool/muprog/management/source_manual_upload.php', ['sourceid' => $source->id]);
            $link = new \tool_mulib\output\ajax_form\link($url, get_string('source_manual_uploadusers', 'tool_muprog'));
            $link->set_form_size('xl');
            $actions->get_dropdown()->add_ajax_form($link);
        }
    }

    /**
     * Returns the user who is responsible for allocation.
     *
     * Override if plugin knows anybody better than admin.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return stdClass user record
     */
    public static function get_allocator(stdClass $program, stdClass $source, stdClass $allocation): stdClass {
        global $USER;

        if (!isloggedin()) {
            // This should not happen, probably some customisation doing manual allocations.
            return parent::get_allocator($program, $source, $allocation);
        }

        return $USER;
    }

    /**
     * Allocate users manually.
     *
     * @param int $programid
     * @param int $sourceid
     * @param array $userids
     * @param array $dateoverrides
     * @return array list of allocation ides
     */
    public static function allocate_users(int $programid, int $sourceid, array $userids, array $dateoverrides = []): array {
        global $DB;

        $result = [];

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record(
            'tool_muprog_source',
            ['id' => $sourceid, 'type' => static::get_type(), 'programid' => $program->id],
            '*',
            MUST_EXIST
        );

        if (count($userids) === 0) {
            return $result;
        }

        foreach ($userids as $userid) {
            $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
            if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id])) {
                // One allocation per program only.
                continue;
            }
            $allocation = self::allocation_create($program, $source, $user->id, [], $dateoverrides);
            $result[] = $allocation->id;
        }

        if (count($userids) === 1) {
            $userid = reset($userids);
        } else {
            $userid = null;
        }
        \tool_muprog\local\allocation::fix_user_enrolments($programid, $userid);
        \tool_muprog\local\notification_manager::trigger_notifications($programid, $userid);

        return $result;
    }

    /**
     * Returns preprocessed user allocation upload file contents.
     *
     * NOTE: data.json file is deleted.
     *
     * @param stdClass $data form submission data
     * @param array $filedata decoded data.json file
     * @return array with keys 'assigned', 'skipped' and 'errors'
     */
    public static function process_uploaded_data(stdClass $data, array $filedata): array {
        global $DB, $USER;

        if (
            $data->usermapping !== 'username'
            && $data->usermapping !== 'email'
            && $data->usermapping !== 'idnumber'
        ) {
            // We need to prevent SQL injections in get_record later!
            throw new \coding_exception('Invalid usermapping value');
        }

        $result = [
            'assigned' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $source = $DB->get_record('tool_muprog_source', ['id' => $data->sourceid, 'type' => 'manual'], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);

        if ($data->hasheaders) {
            unset($filedata[0]);
        }

        $datefields = ['timestartcolumn' => 'timestart', 'timeduecolumn' => 'timedue', 'timeendcolumn' => 'timeend'];
        $datecolumns = [];
        foreach ($datefields as $key => $value) {
            if (isset($data->{$key}) && $data->{$key} != -1) {
                $datecolumns[$value] = $data->{$key};
            }
        }

        $userids = [];
        foreach ($filedata as $i => $row) {
            $userident = $row[$data->usercolumn];
            if (!$userident) {
                $result['errors']++;
                continue;
            }
            $users = $DB->get_records('user', [$data->usermapping => $userident, 'deleted' => 0, 'confirmed' => 1]);
            if (count($users) !== 1) {
                $result['errors']++;
                continue;
            }
            $user = reset($users);
            if (isguestuser($user->id)) {
                $result['errors']++;
                continue;
            }
            if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id])) {
                $result['skipped']++;
                continue;
            }

            $dateoverrides = [];
            foreach ($datecolumns as $key => $value) {
                if (!empty($row[$value])) {
                    $dateoverrides[$key] = strtotime($row[$value]);
                    if ($dateoverrides[$key] === false) {
                        $result['errors']++;
                        continue 2;
                    }
                }
            }
            if (!base::is_valid_dateoverrides($program, $dateoverrides)) {
                $result['errors']++;
                continue;
            }
            self::allocation_create($program, $source, $user->id, [], $dateoverrides);
            \tool_muprog\local\allocation::fix_user_enrolments($program->id, $user->id);
            \tool_muprog\local\notification_manager::trigger_notifications($program->id, $user->id);
            $userids[] = $user->id;
        }

        $result['assigned'] = count($userids);

        if (!empty($data->csvfile)) {
            $fs = get_file_storage();
            $context = \context_user::instance($USER->id);
            $fs->delete_area_files($context->id, 'user', 'draft', $data->csvfile);
            $fs->delete_area_files($context->id, 'tool_muprog', 'upload', $data->csvfile);
        }

        return $result;
    }

    /**
     * Called from \tool_uploaduser\process::process_line()
     *
     * @param stdClass $user
     * @param string $column
     * @param \uu_progress_tracker $upt
     * @return void
     */
    public static function tool_uploaduser_process(stdClass $user, string $column, \uu_progress_tracker $upt): void {
        global $DB;

        if (!preg_match('/^program(?:id)?\d+$/', $column)) {
            return;
        }
        // Extract the program number from the column name.
        $number = strpos($column, 'id') !== false ? substr($column, 9) : substr($column, 7);
        if (empty($user->{$column})) {
            return;
        }
        $isidcolumn = strpos($column, 'id') !== false;
        if (empty($user->{$column})) {
            return;
        }

        $programid = $user->{$column};
        $program = null;
        if ($isidcolumn) {
            if (is_number($programid)) {
                $program = $DB->get_record('tool_muprog_program', ['id' => $programid]);
            }
        } else {
            $program = $DB->get_record('tool_muprog_program', ['idnumber' => $programid]);
        }
        if (!$program) {
            $upt->track('enrolments', get_string('source_manual_userupload_invalidprogram', 'tool_muprog', s($programid)), 'error');
            return;
        }
        $programname = format_string($program->fullname);

        $context = \context::instance_by_id($program->contextid, IGNORE_MISSING);
        if (!$context || !has_capability('tool/muprog:allocate', $context)) {
            $upt->track('enrolments', get_string('source_manual_userupload_invalidprogram', 'tool_muprog', $programname), 'error');
            return;
        }
        $source = $DB->get_record('tool_muprog_source', ['type' => 'manual', 'programid' => $program->id]);
        if (!$source || !self::is_allocation_possible($program, $source)) {
            $upt->track('enrolments', get_string('source_manual_userupload_invalidprogram', 'tool_muprog', $programname), 'error');
            return;
        }

        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id])) {
            $upt->track('enrolments', get_string('source_manual_userupload_alreadyallocated', 'tool_muprog', $programname), 'info');
            return;
        }

        // This only works if the user is not already allocated in the program.
        $dateoverrides = [];
        $datefields = ['timestart' => 'pstartdate' . $number, 'timedue' => 'pduedate' . $number, 'timeend' => 'penddate' . $number];

        foreach ($datefields as $key => $datefield) {
            if (!empty($user->{$datefield})) {
                $dateoverrides[$key] = strtotime($user->{$datefield});
                if ($dateoverrides[$key] === false) {
                    $upt->track('enrolments', get_string('invalidallocationdates', 'tool_muprog', $programname), 'error');
                    return;
                }
            }
        }

        if (!base::is_valid_dateoverrides($program, $dateoverrides)) {
            $upt->track('enrolments', get_string('invalidallocationdates', 'tool_muprog', $programname), 'error');
            return;
        }

        self::allocation_create($program, $source, $user->id, [], $dateoverrides);
        \tool_muprog\local\allocation::fix_user_enrolments($program->id, $user->id);
        \tool_muprog\local\notification_manager::trigger_notifications($program->id, $user->id);

        $upt->track('enrolments', get_string('source_manual_userupload_allocated', 'tool_muprog', $programname), 'info');
    }
}
