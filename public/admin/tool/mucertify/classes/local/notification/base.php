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

namespace tool_mucertify\local\notification;

use stdClass;
use moodle_url;

/**
 * Certification notification base.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base extends \tool_mulib\local\notification\notificationtype {
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
     * @param \stdClass $certification
     * @param \stdClass $assignment
     * @return \stdClass
     */
    public static function get_notifier(\stdClass $certification, \stdClass $assignment): \stdClass {
        return \core_user::get_noreply_user();
    }

    /**
     * Returns standard certification assignment placeholders.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @param stdClass $user
     * @return array
     */
    public static function get_assignment_placeholders(
        stdClass $certification,
        stdClass $source,
        stdClass $assignment,
        stdClass $user
    ): array {
        /** @var \tool_mucertify\local\source\base[] $sourceclasses */
        $sourceclasses = \tool_mucertify\local\assignment::get_source_classes();
        if (isset($sourceclasses[$source->type])) {
            $classname = $sourceclasses[$source->type];
            $sourcename = $classname::get_name();
        } else {
            $sourcename = get_string('error');
        }

        if ($certification->id != $source->certificationid || $source->id != $assignment->sourceid || $user->id != $assignment->userid) {
            throw new \coding_exception('invalid parameter mix');
        }

        $a = [];
        $a['user_fullname'] = s(fullname($user));
        $a['user_firstname'] = s($user->firstname);
        $a['user_lastname'] = s($user->lastname);
        $a['certification_fullname'] = format_string($certification->fullname);
        $a['certification_idnumber'] = s($certification->idnumber);
        $a['certification_url'] = (new moodle_url('/admin/tool/mucertify/my/certification.php', ['id' => $certification->id]))->out(false);
        $a['certification_sourcename'] = $sourcename;
        $a['certification_status'] = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);

        return $a;
    }

    /**
     * Returns standard certification period placeholders.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @param stdClass $period
     * @param stdClass $user
     * @return array
     */
    public static function get_period_placeholders(
        stdClass $certification,
        stdClass $source,
        stdClass $assignment,
        stdClass $period,
        stdClass $user
    ): array {
        if ($period->certificationid != $assignment->certificationid || $period->userid != $assignment->userid) {
            throw new \coding_exception('invalid parameter mix');
        }

        $strnotset = get_string('notset', 'tool_mucertify');

        $a = static::get_assignment_placeholders($certification, $source, $assignment, $user);

        $a['period_status'] = \tool_mucertify\local\period::get_status_html($certification, $assignment, $period);
        $a['period_startdate'] = userdate($period->timewindowstart);
        $a['period_duedate'] = (isset($period->timewindowdue) ? userdate($period->timewindowdue) : $strnotset);
        $a['period_enddate'] = (isset($period->timewindowend) ? userdate($period->timewindowend) : $strnotset);
        $a['period_fromdate'] = (isset($period->timefrom) ? userdate($period->timefrom) : $strnotset);
        $a['period_untildate'] = (isset($period->timeuntil) ? userdate($period->timeuntil) : $strnotset);
        if ($period->timerevoked) {
            $a['period_certificationdate'] = $strnotset;
            $a['period_recertificationdate'] = $strnotset;
        } else {
            $a['period_certificationdate'] = (isset($period->timecertified) ? userdate($period->timecertified) : $strnotset);
            if (
                isset($certification->recertify) && $period->recertifiable && $period->timeuntil
                && !$certification->archived && !$assignment->archived
            ) {
                $a['period_recertificationdate'] = userdate($period->timeuntil - $certification->recertify);
            } else {
                $a['period_recertificationdate'] = $strnotset;
            }
        }

        return $a;
    }

    /**
     * Send notification to assigned user.
     *
     * @param stdClass $certification
     * @param stdClass $source
     * @param stdClass $assignment
     * @param ?stdClass $period
     * @param stdClass $user
     * @param bool $alowmultiple
     * @return void
     */
    protected static function notify_assigned_user(
        stdClass $certification,
        stdClass $source,
        stdClass $assignment,
        ?stdClass $period,
        stdClass $user,
        bool $alowmultiple = false
    ): void {
        global $DB;

        if ($certification->archived) {
            // Never send notifications for archived certification.
            return;
        }

        if ($assignment->archived && static::get_notificationtype() !== 'unassignment') {
            // Notification for unassigned is different because we require archiving before unassignment.
            return;
        }

        if ($user->deleted || $user->suspended) {
            // Skip also suspended users in case they are unsuspended in the next few days
            // then they would get at least some missed notifications.
            return;
        }

        $notification = $DB->get_record('tool_mulib_notification', [
            'instanceid' => $certification->id,
            'component' => static::get_component(),
            'notificationtype' => static::get_notificationtype(),
        ]);
        if (!$notification || !$notification->enabled) {
            return;
        }

        try {
            self::force_language($user->lang);

            if ($period) {
                $a = static::get_period_placeholders($certification, $source, $assignment, $period, $user);
                $periodid = $period->id;
            } else {
                $a = static::get_assignment_placeholders($certification, $source, $assignment, $user);
                $periodid = null;
            }
            $subject = static::get_subject($notification, $a);
            $body = static::get_body($notification, $a);

            $message = new \core\message\message();
            $message->notification = '1';
            $message->component = static::get_component();
            $message->name = static::get_provider();
            $message->userfrom = static::get_notifier($certification, $assignment);
            $message->userto = $user;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml = $body;
            $message->smallmessage = $subject;
            $message->contexturlname = $a['certification_fullname'];
            $message->contexturl = $a['certification_url'] ?? null;

            self::message_send($message, $notification->id, $user->id, $assignment->id, $periodid, $alowmultiple);
        } finally {
            self::revert_language();
        }
    }

    /**
     * Send notifications.
     *
     * @param stdClass|null $certification
     * @param stdClass|null $user
     * @return void
     */
    abstract public static function notify_users(?stdClass $certification, ?stdClass $user): void;

    /**
     * Delete sent notifications tracking for given assignment.
     *
     * @param \stdClass $assignment
     * @return void
     */
    public static function delete_assignment_notifications(\stdClass $assignment) {
        global $DB;

        $notification = $DB->get_record('tool_mulib_notification', [
            'component' => 'tool_mucertify',
            'instanceid' => $assignment->certificationid,
            'notificationtype' => static::get_notificationtype(),
        ]);
        if (!$notification) {
            return;
        }
        $DB->delete_records('tool_mulib_notification_user', [
            'notificationid' => $notification->id,
            'otherid1' => $assignment->id,
        ]);
    }

    /**
     * Delete sent notifications tracking for given period.
     *
     * @param \stdClass $period
     * @return void
     */
    public static function delete_period_notifications(\stdClass $period) {
        global $DB;

        $notification = $DB->get_record('tool_mulib_notification', [
            'component' => 'tool_mucertify',
            'instanceid' => $period->certificationid,
            'notificationtype' => static::get_notificationtype(),
        ]);
        if (!$notification) {
            return;
        }
        $DB->delete_records('tool_mulib_notification_user', [
            'notificationid' => $notification->id,
            'otherid2' => $period->id,
        ]);
    }

    /**
     * Returns notification description text.
     *
     * @return string HTML text converted from Markdown lang string value
     */
    public static function get_description(): string {
        $description = get_string('notification_' . static::get_notificationtype() . '_description', 'tool_mucertify');
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
        return get_string('notification_' . static::get_notificationtype() . '_subject', 'tool_mucertify');
    }
}
