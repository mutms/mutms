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

namespace tool_mucertify\local\source;

use tool_mucertify\local\util;
use stdClass;

/**
 * Certification assignment with approval source.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
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
     * Can the user request new assignment?
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param int $userid
     * @param string|null $failurereason optional failure reason
     * @return bool
     */
    public static function can_user_request(\stdClass $certification, \stdClass $source, int $userid, ?string &$failurereason = null): bool {
        global $DB;

        if ($source->type !== 'approval') {
            throw new \coding_exception('invalid source parameter');
        }

        if ($certification->archived) {
            return false;
        }

        if ($userid <= 0 || isguestuser($userid)) {
            return false;
        }

        if (!\tool_mucertify\local\catalogue::is_certification_visible($certification, $userid)) {
            return false;
        }

        if ($DB->record_exists('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $userid])) {
            return false;
        }

        $request = $DB->get_record('tool_mucertify_request', ['sourceid' => $source->id, 'userid' => $userid]);
        if ($request) {
            if ($request->timerejected) {
                $info = get_string('source_approval_requestrejected', 'tool_mucertify');
            } else {
                $info = get_string('source_approval_requestpending', 'tool_mucertify');
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
     * Returns list of actions available in certification catalogue.
     *
     * NOTE: This is intended mainly for students.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @return string[]
     */
    public static function get_catalogue_actions(\stdClass $certification, \stdClass $source): array {
        global $USER, $OUTPUT, $PAGE;

        $failurereason = null;
        if (!self::can_user_request($certification, $source, (int)$USER->id, $failurereason)) {
            if ($failurereason !== null) {
                return [$failurereason];
            } else {
                return [];
            }
        }

        $url = new \moodle_url('/admin/tool/mucertify/catalogue/source_approval_request.php', ['sourceid' => $source->id]);
        $button = new \tool_mulib\output\ajax_form\button($url, get_string('source_approval_makerequest', 'tool_mucertify'));

        $button = $OUTPUT->render($button);

        return [$button];
    }

    /**
     * Return extra tab for managing the source data in program.
     *
     * @param \tool_mucertify\navigation\views\certification_secondary $secondary
     * @param stdClass $certification
     */
    public static function add_certification_secondary_tabs(\tool_mucertify\navigation\views\certification_secondary $secondary, stdClass $certification): void {
        global $DB;

        if ($DB->record_exists('tool_mucertify_source', ['certificationid' => $certification->id, 'type' => 'approval'])) {
            $url = new \moodle_url('/admin/tool/mucertify/management/source_approval_requests.php', ['id' => $certification->id]);
            $secondary->add(get_string('source_approval_requests', 'tool_mucertify'), $url, \navigation_node::TYPE_SETTING, null, 'certification_approval_requests');
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
        return \tool_mucertify\local\util::json_encode($data);
    }

    /**
     * Process user request for assignment to certification.
     *
     * @param int $certificationid
     * @param int $sourceid
     * @return ?stdClass
     */
    public static function request(int $certificationid, int $sourceid): ?stdClass {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['id' => $sourceid, 'type' => static::get_type(), 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $user = $DB->get_record('user', ['id' => $USER->id, 'deleted' => 0], '*', MUST_EXIST);
        if ($DB->record_exists('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id])) {
            // One assignment per certification only.
            return null;
        }

        if ($DB->record_exists('tool_mucertify_request', ['sourceid' => $source->id, 'userid' => $user->id])) {
            // Cannot request repeatedly.
            return null;
        }

        $record = new stdClass();
        $record->sourceid = $source->id;
        $record->userid = $user->id;
        $record->timerequested = time();
        $record->datajson = util::json_encode([]);
        $record->id = $DB->insert_record('tool_mucertify_request', $record);

        // Send notification.
        $context = \context::instance_by_id($certification->contextid);
        $targets = get_users_by_capability($context, 'tool/mucertify:assign');
        foreach ($targets as $target) {
            $oldforcelang = force_current_language($target->lang);

            $a = new stdClass();
            $a->user_fullname = s(fullname($user));
            $a->user_firstname = s($user->firstname);
            $a->user_lastname = s($user->lastname);
            $a->certification_fullname = format_string($certification->fullname);
            $a->certification_idnumber = s($certification->idnumber);
            $a->certification_url = (new \moodle_url('/admin/tool/mucertify/catalogue/certification.php', ['id' => $certification->id]))->out(false);
            $a->requests_url = (new \moodle_url('/admin/tool/mucertify/management/source_approval_requests.php', ['id' => $certification->id]))->out(false);

            $subject = get_string('source_approval_notification_approval_request_subject', 'tool_mucertify', $a);
            $body = get_string('source_approval_notification_approval_request_body', 'tool_mucertify', $a);

            $message = new \core\message\message();
            $message->notification = 1;
            $message->component = 'tool_mucertify';
            $message->name = 'approval_request_notification';
            $message->userfrom = $user;
            $message->userto = $target;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_MARKDOWN;
            $message->fullmessagehtml = markdown_to_html($body);
            $message->smallmessage = $subject;
            $message->contexturlname = $a->certification_fullname;
            $message->contexturl = $a->requests_url;
            message_send($message);

            force_current_language($oldforcelang);
        }

        return $DB->get_record('tool_mucertify_request', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Approve student assignment request.
     *
     * @param int $requestid
     * @return ?stdClass user assignment record
     */
    public static function approve_request(int $requestid): ?stdClass {
        global $DB;

        $request = $DB->get_record('tool_mucertify_request', ['id' => $requestid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);
        $source = $DB->get_record('tool_mucertify_source', ['id' => $request->sourceid], '*', MUST_EXIST);
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $source->certificationid], '*', MUST_EXIST);

        if ($DB->record_exists('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id])) {
            return null;
        }

        $trans = $DB->start_delegated_transaction();
        $assignment = self::assignment_create($certification, $source, $user->id, []);
        $DB->delete_records('tool_mucertify_request', ['id' => $request->id]);
        $trans->allow_commit();

        \tool_muprog\local\source\mucertify::sync_certifications($certification->id, $user->id);
        \tool_mucertify\local\notification_manager::trigger_notifications($certification->id, $user->id);

        return $assignment;
    }

    /**
     * Returns the user who is responsible for assignment.
     *
     * Override if plugin knows anybody better than admin.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @return stdClass user record
     */
    public static function get_allocator(stdClass $certification, stdClass $source, stdClass $assignment): stdClass {
        global $USER;

        if (!isloggedin()) {
            // This should not happen, probably some customisation doing manual assignments.
            return parent::get_assigner($certification, $source, $assignment);
        }

        return $USER;
    }

    /**
     * Reject student assignment request.
     *
     * @param int $requestid
     * @param string $reason
     * @return void
     */
    public static function reject_request(int $requestid, string $reason): void {
        global $DB, $USER;

        $request = $DB->get_record('tool_mucertify_request', ['id' => $requestid], '*', MUST_EXIST);
        if ($request->timerejected) {
            return;
        }
        $request->timerejected = time();
        $request->rejectedby = $USER->id;
        $DB->update_record('tool_mucertify_request', $request);

        $source = $DB->get_record('tool_mucertify_source', ['id' => $request->sourceid], '*', MUST_EXIST);
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $source->certificationid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);

        $oldforcelang = force_current_language($user->lang);

        $a = new stdClass();
        $a->user_fullname = s(fullname($user));
        $a->user_firstname = s($user->firstname);
        $a->user_lastname = s($user->lastname);
        $a->certification_fullname = format_string($certification->fullname);
        $a->certification_idnumber = s($certification->idnumber);
        $a->certification_url = (new \moodle_url('/admin/tool/mucertify/catalogue/certification.php', ['id' => $certification->id]))->out(false);
        $a->reason = $reason;

        $subject = get_string('source_approval_notification_approval_reject_subject', 'tool_mucertify', $a);
        $body = get_string('source_approval_notification_approval_reject_body', 'tool_mucertify', $a);

        $message = new \core\message\message();
        $message->notification = 1;
        $message->component = 'tool_mucertify';
        $message->name = 'approval_reject_notification';
        $message->userfrom = $USER;
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = markdown_to_html($body);
        $message->smallmessage = $subject;
        $message->contexturlname = $a->certification_fullname;
        $message->contexturl = $a->certification_url;
        message_send($message);

        force_current_language($oldforcelang);
    }

    /**
     * Delete student assignment request.
     *
     * @param int $requestid
     * @return void
     */
    public static function delete_request(int $requestid): void {
        global $DB;

        $request = $DB->get_record('tool_mucertify_request', ['id' => $requestid]);
        if (!$request) {
            return;
        }

        $DB->delete_records('tool_mucertify_request', ['id' => $request->id]);
    }

    /**
     * Render details about this enabled source in a certification management ui.
     *
     * @param stdClass $certification
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $certification, ?stdClass $source): string {
        global $DB;

        $result = parent::render_status_details($certification, $source);

        if ($source) {
            $data = (object)json_decode($source->datajson);
            if (!isset($data->allowrequest) || $data->allowrequest) {
                $result .= '; ' . get_string('source_approval_requestallowed', 'tool_mucertify');
            } else {
                $result .= '; ' . get_string('source_approval_requestnotallowed', 'tool_mucertify');
            }
        }

        return $result;
    }
}
