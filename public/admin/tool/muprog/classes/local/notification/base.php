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

namespace tool_muprog\local\notification;

use stdClass;
use core\exception\coding_exception;

/**
 * Program notification base.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base extends \tool_mulib\local\notification\notificationtype {
    /** @var int "soon" means in 3 days from now */
    public const TIME_SOON = (60 * 60 * 24 * 3);

    /** @var int any due notification that was missed by more than 2 days is ignored */
    public const TIME_CUTOFF = (60 * 60 * 24 * 2);

    /**
     * Returns message provider name.
     *
     * @return string
     */
    public static function get_provider(): string {
        return static::get_notificationtype() . '_notification';
    }

    /**
     * Returns sender of notifications.
     *
     * @param \stdClass $program
     * @param \stdClass $allocation
     * @return \stdClass
     */
    public static function get_notifier(\stdClass $program, \stdClass $allocation): \stdClass {
        return \core_user::get_noreply_user();
    }

    /**
     * Returns standard program allocation placeholders.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @param stdClass $user
     * @param stdClass|null $supervisoruser
     * @return array
     */
    public static function get_allocation_placeholders(
        stdClass $program,
        stdClass $source,
        stdClass $allocation,
        stdClass $user,
        ?stdClass $supervisoruser = null
    ): array {
        /** @var \tool_muprog\local\source\base[] $sourceclasses */
        $sourceclasses = \tool_muprog\local\allocation::get_source_classes();
        if (isset($sourceclasses[$source->type])) {
            $classname = $sourceclasses[$source->type];
            $sourcename = $classname::get_name();
        } else {
            $sourcename = get_string('error');
        }

        if ($program->id != $source->programid || $source->id != $allocation->sourceid || $user->id != $allocation->userid) {
            throw new coding_exception('invalid parameter mix');
        }

        if ($supervisoruser) {
            $timezone = $supervisoruser->timezone;
        } else {
            $timezone = $user->timezone;
        }

        $strnotset = get_string('notset', 'tool_muprog');

        $a = [];
        $a['user_fullname'] = s(fullname($user));
        $a['user_firstname'] = s($user->firstname);
        $a['user_lastname'] = s($user->lastname);
        $a['program_fullname'] = format_string($program->fullname);
        $a['program_idnumber'] = s($program->idnumber);
        $a['program_url'] = (new \core\url('/admin/tool/muprog/my/program.php', ['id' => $program->id]))->out(false);
        $a['program_sourcename'] = $sourcename;
        $a['program_status'] = \tool_muprog\local\allocation::get_completion_status_plain($program, $allocation);
        $a['program_allocationdate'] = userdate($allocation->timeallocated, '', $timezone);
        $a['program_startdate'] = userdate($allocation->timestart, '', $timezone);
        $a['program_duedate'] = (isset($allocation->timedue) ? userdate($allocation->timedue, '', $timezone) : $strnotset);
        $a['program_enddate'] = (isset($allocation->timeend) ? userdate($allocation->timeend, '', $timezone) : $strnotset);
        $a['allocation_completeddate'] = (isset($allocation->timecompleted) ? userdate($allocation->timecompleted, '', $timezone) : $strnotset);

        if ($supervisoruser) {
            $context = \context::instance_by_id($program->contextid);
            $a['supervisor_fullname'] = s(fullname($supervisoruser));
            $a['supervisor_firstname'] = s($supervisoruser->firstname);
            $a['supervisor_lastname'] = s($supervisoruser->lastname);
            if (has_capability('tool/muprog:view', $context, $supervisoruser)) {
                $a['program_url'] = (new \core\url('/admin/tool/muprog/management/allocation.php', ['id' => $allocation->id]))->out(false);
            } else {
                $a['program_url'] = (new \core\url('/admin/tool/muprog/catalogue/program.php', ['id' => $program->id]))->out(false);
            }
            if (isset($supervisoruser->supervisortitle)) {
                $a['supervisor_title'] = format_string($supervisoruser->supervisortitle);
            } else {
                $a['supervisor_title'] = get_string('supervisor', 'tool_murelation');
            }
            if (isset($supervisoruser->subordinatetitle)) {
                $a['subordinate_title'] = format_string($supervisoruser->subordinatetitle);
            } else {
                $a['subordinate_title'] = get_string('subordinate', 'tool_murelation');
            }
        }

        return $a;
    }

    /**
     * Send notification to allocated user.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @param stdClass $user
     * @param bool $alowmultiple
     * @return void
     */
    protected static function notify_allocated_user(stdClass $program, stdClass $source, stdClass $allocation, stdClass $user, bool $alowmultiple = false): void {
        global $DB;

        $notificationtype = static::get_notificationtype();

        if ($program->archived) {
            // Never send notifications for archived program.
            return;
        }

        if ($allocation->archived && $notificationtype !== 'deallocation') {
            // Notification for deallocation is different because we require archiving before deallocation.
            return;
        }

        if ($user->deleted || $user->suspended) {
            // Skip also suspended users in case they are unsuspended in the next few days
            // then they would get at least some missed notifications.
            return;
        }

        $notification = $DB->get_record('tool_mulib_notification', [
            'instanceid' => $program->id,
            'component' => static::get_component(),
            'notificationtype' => $notificationtype,
        ]);
        if (!$notification || !$notification->enabled) {
            return;
        }

        $supervisoruser = static::get_supervisor_user($allocation->userid, $notification->supervisorframeworkid);

        try {
            self::force_language($user->lang);

            $a = static::get_allocation_placeholders($program, $source, $allocation, $user);
            $subject = static::get_subject($notification, $a);
            $body = static::get_body($notification, $a);

            $message = new \core\message\message();
            $message->notification = '1';
            $message->component = static::get_component();
            $message->name = static::get_provider();
            $message->userfrom = static::get_notifier($program, $allocation);
            $message->userto = $user;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml = $body;
            $message->smallmessage = $subject;
            $message->contexturlname = $a['program_fullname'];
            $message->contexturl = $a['program_url'];

            $success = self::message_send($message, $notification->id, $user->id, $allocation->id, null, $alowmultiple);

            if ($success && $supervisoruser) {
                self::revert_language();
                self::force_language($supervisoruser->lang);

                $a = static::get_allocation_placeholders($program, $source, $allocation, $user, $supervisoruser);
                $a['notification_name'] = static::get_name();

                $ccsubject = self::format_subject(get_string('notification_cc_supervisor_subject', 'tool_muprog'), $a);
                $ccbody = self::format_body(get_string('notification_cc_supervisor_body', 'tool_muprog'), FORMAT_MARKDOWN, $a);
                $ccbody .= '<blockquote style="background: #f9f9f9;margin: 0;padding: 10px;border-left: 12px solid #ccc;">' . $body . '</blockquote>';

                $ccmessage = new \core\message\message();
                $ccmessage->notification = '1';
                $ccmessage->component = static::get_component();
                $ccmessage->name = 'cc_supervisor_notification';
                $ccmessage->userfrom = static::get_notifier($program, $allocation);
                $ccmessage->userto = $supervisoruser;
                $ccmessage->subject = $ccsubject;
                $ccmessage->fullmessage = $ccbody;
                $ccmessage->fullmessageformat = FORMAT_HTML;
                $ccmessage->fullmessagehtml = $ccbody;
                $ccmessage->smallmessage = $ccsubject;
                $ccmessage->contexturlname = $a['program_fullname'];
                $ccmessage->contexturl = $a['program_url'];

                message_send($ccmessage);
            }
        } finally {
            self::revert_language();
        }
    }

    /**
     * Send notifications.
     *
     * @param stdClass|null $program
     * @param stdClass|null $user
     * @return void
     */
    abstract public static function notify_users(?stdClass $program, ?stdClass $user): void;

    /**
     * Delete sent notifications tracking for given allocation.
     *
     * @param \stdClass $allocation
     * @return void
     */
    public static function delete_allocation_notifications(\stdClass $allocation) {
        global $DB;

        $notification = $DB->get_record('tool_mulib_notification', [
            'component' => 'tool_muprog',
            'instanceid' => $allocation->programid,
            'notificationtype' => static::get_notificationtype(),
        ]);
        if (!$notification) {
            return;
        }
        $DB->delete_records('tool_mulib_notification_user', [
            'notificationid' => $notification->id,
            'otherid1' => $allocation->id,
        ]);
    }

    /**
     * Returns notification description text.
     *
     * @return string HTML text converted from Markdown lang string value
     */
    public static function get_description(): string {
        $description = get_string('notification_' . static::get_notificationtype() . '_description', 'tool_muprog');
        $description = markdown_to_html($description);
        return $description;
    }

    /**
     * Returns default notification message subject (and small message) from lang pack
     * with original placeholders.
     *
     * @return string as plain text
     */
    public static function get_default_subject(): string {
        return get_string('notification_' . static::get_notificationtype() . '_subject', 'tool_muprog');
    }
}
