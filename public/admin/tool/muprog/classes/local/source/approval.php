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

use tool_muprog\local\util;
use stdClass;

/**
 * Program allocation with approval source.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class approval extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'approval';
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
     * Can the user request new allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param int $userid
     * @param string|null $failurereason optional failure reason
     * @return bool
     */
    public static function can_user_request(\stdClass $program, \stdClass $source, int $userid, ?string &$failurereason = null): bool {
        global $DB;

        if ($source->type !== 'approval') {
            throw new \coding_exception('invalid source parameter');
        }

        if ($program->archived) {
            return false;
        }

        if ($userid <= 0 || isguestuser($userid)) {
            return false;
        }

        if ($program->timeallocationstart && $program->timeallocationstart > time()) {
            return false;
        }

        if ($program->timeallocationend && $program->timeallocationend < time()) {
            return false;
        }

        if (!\tool_muprog\local\catalogue::is_program_visible($program, $userid)) {
            return false;
        }

        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $userid])) {
            return false;
        }

        $request = $DB->get_record('tool_muprog_request', ['sourceid' => $source->id, 'userid' => $userid]);
        if ($request) {
            if ($request->timerejected) {
                $info = get_string('source_approval_requestrejected', 'tool_muprog');
            } else {
                $info = get_string('source_approval_requestpending', 'tool_muprog');
            }
            $failurereason = '<em><strong>' . $info . '</strong></em>';
            return false;
        }

        $data = (object)json_decode($source->datajson);
        if (isset($data->allowrequest) && !$data->allowrequest) {
            return false;
        }

        return true;
    }

    /**
     * Returns list of actions available in Program catalogue.
     *
     * NOTE: This is intended mainly for students.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @return string[]
     */
    public static function get_catalogue_actions(\stdClass $program, \stdClass $source): array {
        global $USER, $OUTPUT;

        $failurereason = null;
        if (!self::can_user_request($program, $source, (int)$USER->id, $failurereason)) {
            if ($failurereason !== null) {
                return [$failurereason];
            } else {
                return [];
            }
        }

        $url = new \moodle_url('/admin/tool/muprog/catalogue/source_approval_request.php', ['sourceid' => $source->id]);
        $button = new \tool_mulib\output\ajax_form\button($url, get_string('source_approval_makerequest', 'tool_muprog'));

        $button = $OUTPUT->render($button);

        return [$button];
    }

    /**
     * Return request approval tab link.
     *
    /**
     * Return extra tab for managing the source data in program.
     *
     * @param \tool_muprog\navigation\views\program_secondary $secondary
     * @param stdClass $program
     */
    public static function add_program_secondary_tabs(\tool_muprog\navigation\views\program_secondary $secondary, stdClass $program): void {
        global $DB;

        if ($DB->record_exists('tool_muprog_source', ['programid' => $program->id, 'type' => 'approval'])) {
            $url = new \moodle_url('/admin/tool/muprog/management/source_approval_requests.php', ['id' => $program->id]);
            $secondary->add(get_string('source_approval_requests', 'tool_muprog'), $url, \navigation_node::TYPE_SETTING, null, 'program_approval_requests');
        }
    }

    /**
     * Decode extra source settings.
     *
     * @param stdClass $source
     * @return stdClass
     */
    public static function decode_datajson(stdClass $source): stdClass {
        $source->approval_allowrequest = 1;

        if (isset($source->datajson)) {
            $data = (object)json_decode($source->datajson);
            if (isset($data->allowrequest)) {
                $source->approval_allowrequest = (int)(bool)$data->allowrequest;
            }
        }

        return $source;
    }

    /**
     * Encode extra source settings.
     *
     * @param stdClass $formdata
     * @return string
     */
    public static function encode_datajson(stdClass $formdata): string {
        $data = ['allowrequest' => 1];
        if (isset($formdata->approval_allowrequest)) {
            $data['allowrequest'] = (int)(bool)$formdata->approval_allowrequest;
        }
        return \tool_muprog\local\util::json_encode($data);
    }

    /**
     * Process user request for allocation to program.
     *
     * @param int $programid
     * @param int $sourceid
     * @return ?stdClass
     */
    public static function request(int $programid, int $sourceid): ?stdClass {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record(
            'tool_muprog_source',
            ['id' => $sourceid, 'type' => static::get_type(), 'programid' => $program->id],
            '*',
            MUST_EXIST
        );

        $user = $DB->get_record('user', ['id' => $USER->id, 'deleted' => 0], '*', MUST_EXIST);
        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id])) {
            // One allocation per program only.
            return null;
        }

        if ($DB->record_exists('tool_muprog_request', ['sourceid' => $source->id, 'userid' => $user->id])) {
            // Cannot request repeatedly.
            return null;
        }

        $record = new stdClass();
        $record->sourceid = $source->id;
        $record->userid = $user->id;
        $record->timerequested = time();
        $record->datajson = util::json_encode([]);
        $record->id = $DB->insert_record('tool_muprog_request', $record);

        // Send notification.
        $context = \context::instance_by_id($program->contextid);
        $targets = get_users_by_capability($context, 'tool/muprog:allocate');
        foreach ($targets as $target) {
            $oldforcelang = force_current_language($target->lang);

            $a = new stdClass();
            $a->user_fullname = s(fullname($user));
            $a->user_firstname = s($user->firstname);
            $a->user_lastname = s($user->lastname);
            $a->program_fullname = format_string($program->fullname);
            $a->program_idnumber = s($program->idnumber);
            $a->program_url = (new \moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $program->id]))->out(false);
            $a->requests_url = (new \moodle_url('/admin/tool/muprog/management/source_approval_requests.php', ['id' => $program->id]))->out(false);

            $subject = get_string('source_approval_notification_approval_request_subject', 'tool_muprog', $a);
            $body = get_string('source_approval_notification_approval_request_body', 'tool_muprog', $a);

            $message = new \core\message\message();
            $message->notification = 1;
            $message->component = 'tool_muprog';
            $message->name = 'approval_request_notification';
            $message->userfrom = $user;
            $message->userto = $target;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_MARKDOWN;
            $message->fullmessagehtml = markdown_to_html($body);
            $message->smallmessage = $subject;
            $message->contexturlname = $a->program_fullname;
            $message->contexturl = $a->requests_url;
            message_send($message);

            force_current_language($oldforcelang);
        }

        return $DB->get_record('tool_muprog_request', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Approve student allocation request.
     *
     * @param int $requestid
     * @return ?stdClass user allocation record
     */
    public static function approve_request(int $requestid): ?stdClass {
        global $DB;

        $request = $DB->get_record('tool_muprog_request', ['id' => $requestid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);
        $source = $DB->get_record('tool_muprog_source', ['id' => $request->sourceid], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);

        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id])) {
            return null;
        }

        $trans = $DB->start_delegated_transaction();
        $allocation = self::allocation_create($program, $source, $user->id, []);
        $DB->delete_records('tool_muprog_request', ['id' => $request->id]);
        $trans->allow_commit();

        \tool_muprog\local\allocation::fix_user_enrolments($program->id, $user->id);
        \tool_muprog\local\notification_manager::trigger_notifications($program->id, $user->id);

        return $allocation;
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
     * Reject student allocation request.
     *
     * @param int $requestid
     * @param string $reason
     * @return void
     */
    public static function reject_request(int $requestid, string $reason): void {
        global $DB, $USER;

        $request = $DB->get_record('tool_muprog_request', ['id' => $requestid], '*', MUST_EXIST);
        if ($request->timerejected) {
            return;
        }
        $request->timerejected = time();
        $request->rejectedby = $USER->id;
        $DB->update_record('tool_muprog_request', $request);

        $source = $DB->get_record('tool_muprog_source', ['id' => $request->sourceid], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);

        $oldforcelang = force_current_language($user->lang);

        $a = new stdClass();
        $a->user_fullname = s(fullname($user));
        $a->user_firstname = s($user->firstname);
        $a->user_lastname = s($user->lastname);
        $a->program_fullname = format_string($program->fullname);
        $a->program_idnumber = s($program->idnumber);
        $a->program_url = (new \moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $program->id]))->out(false);
        $a->reason = $reason;

        $subject = get_string('source_approval_notification_approval_reject_subject', 'tool_muprog', $a);
        $body = get_string('source_approval_notification_approval_reject_body', 'tool_muprog', $a);

        $message = new \core\message\message();
        $message->notification = 1;
        $message->component = 'tool_muprog';
        $message->name = 'approval_reject_notification';
        $message->userfrom = $USER;
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = markdown_to_html($body);
        $message->smallmessage = $subject;
        $message->contexturlname = $a->program_fullname;
        $message->contexturl = $a->program_url;
        message_send($message);

        force_current_language($oldforcelang);
    }

    /**
     * Delete student allocation request.
     *
     * @param int $requestid
     * @return void
     */
    public static function delete_request(int $requestid): void {
        global $DB;

        $request = $DB->get_record('tool_muprog_request', ['id' => $requestid]);
        if (!$request) {
            return;
        }

        $DB->delete_records('tool_muprog_request', ['id' => $request->id]);
    }

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
     * Render details about this enabled source in a programs management ui.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        global $DB;

        $result = parent::render_status_details($program, $source);

        if ($source) {
            $data = (object)json_decode($source->datajson);
            if (!isset($data->allowrequest) || $data->allowrequest) {
                $result .= '; ' . get_string('source_approval_requestallowed', 'tool_muprog');
            } else {
                $result .= '; ' . get_string('source_approval_requestnotallowed', 'tool_muprog');
            }
        }

        return $result;
    }
}
