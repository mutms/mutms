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

namespace tool_mulib\local\notification;

use stdClass;

/**
 * Base for classes implementing individual component notifications.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class notificationtype {
    /** @var null|string */
    private static $oldforceflang = null;

    /**
     * Returns relevant component.
     *
     * @return string
     */
    public static function get_component(): string {
        $parts = explode('\\', static::class);
        return $parts[0];
    }

    /**
     * Returns internal notification type name.
     *
     * @return string
     */
    public static function get_notificationtype(): string {
        $parts = explode('\\', static::class);
        $type = array_pop($parts);
        return $type;
    }

    /**
     * Returns message provider name.
     *
     * @return string
     */
    public static function get_provider(): string {
        return static::get_notificationtype();
    }

    /**
     * Returns localised name of notification.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('notification_' . static::get_notificationtype(), static::get_component());
    }

    /**
     * Returns notification description text.
     *
     * @return string HTML text converted from Markdown lang string value
     */
    public static function get_description(): string {
        $description = get_string('notification_' . static::get_notificationtype() . '_description', static::get_component());
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
        return get_string('notification_' . static::get_notificationtype() . '_subject', static::get_component());
    }

    /**
     * Format notification message subject (and small message) and
     * replace placeholders with given values.
     *
     * @param string $subject
     * @param array $a placeholder values
     * @return string plain text
     */
    final public static function format_subject(string $subject, array $a): string {
        $text = util::replace_placeholders($subject, $a);
        $text = util::filter_multilang($text);
        $text = trim($text);
        $text = s($text);
        return $text;
    }

    /**
     * Returns notification message subject (and small message).
     *
     * NOTE: there is a fallback to default subject if resulting custom text is empty.
     *
     * @param stdClass $notification
     * @param array $a
     * @return string plain text
     */
    final public static function get_subject(stdClass $notification, array $a): string {
        if ($notification->component !== static::get_component()) {
            throw new \coding_exception('Invalid component: ' . $notification->component);
        }
        if ($notification->notificationtype !== static::get_notificationtype()) {
            throw new \coding_exception('Invalid type: ' . $notification->notificationtype);
        }

        $subject = '';
        if ($notification->custom && $notification->customjson) {
            $custom = json_decode($notification->customjson, true);
            $subject = $custom['subject'] ?? '';
            $subject = static::format_subject($subject, $a);
        }
        if ($subject === '') {
            $subject = static::format_subject(static::get_default_subject(), $a);
        }

        return $subject;
    }

    /**
     * Returns default notification message body from lang pack with original placeholders.
     *
     * @return string body text in Markdown format
     */
    public static function get_default_body(): string {
        return get_string('notification_' . static::get_notificationtype() . '_body', static::get_component());
    }

    /**
     * Format notification message body in HTML format and
     * replace placeholders with given values.
     *
     * @param string $body
     * @param int $format FORMAT_HTML or FORMAT_MARKDOWN
     * @param array $a placeholder values
     * @return string HTML formatted text
     */
    final public static function format_body(string $body, int $format, array $a): string {
        if ($format != FORMAT_MARKDOWN && $format != FORMAT_HTML) {
            throw new \coding_exception('Unknown body format: ' . $format);
        }
        $text = util::replace_placeholders($body, $a);
        $text = util::filter_multilang($text);
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if ($format == FORMAT_MARKDOWN) {
            $text = markdown_to_html($text);
        }
        $text = clean_text($text);
        return $text;
    }

    /**
     * Returns notification message body.
     *
     * NOTE: there is a fallback to default body if resulting custom text is empty.
     *
     * @param stdClass $notification
     * @param array $a
     * @return string HTML text
     */
    final public static function get_body(stdClass $notification, array $a): string {
        if ($notification->component !== static::get_component()) {
            throw new \coding_exception('Invalid component: ' . $notification->component);
        }
        if ($notification->notificationtype !== static::get_notificationtype()) {
            throw new \coding_exception('Invalid type: ' . $notification->notificationtype);
        }

        $body = '';
        if ($notification->custom && $notification->customjson) {
            $custom = json_decode($notification->customjson, true);
            $body = $custom['body'] ?? '';
            $body = static::format_body($body, FORMAT_HTML, $a);
        }
        if ($body === '') {
            $body = static::format_body(static::get_default_body(), FORMAT_MARKDOWN, $a);
        }

        return $body;
    }

    /**
     * Temporarily force a different language for notification.
     *
     * NOTE: better not make this hack public to prevent abuse, it would not be testable anyway.
     *
     * @param string $lang
     * @return void
     */
    final protected static function force_language(string $lang): void {
        global $SESSION, $CFG;

        if (isset(self::$oldforceflang)) {
            debugging('Notification language was already forced', DEBUG_DEVELOPER);
        }

        if (!$lang || !get_string_manager()->translation_exists($lang, false)) {
            $lang = $CFG->lang;
        }

        if (current_language() === $lang) {
            return;
        }

        self::$oldforceflang = $SESSION->forcelang ?? null;
        $SESSION->forcelang = $lang;
        moodle_setlocale();
    }

    /**
     * Revert forcing of different language.
     *
     * @return void
     */
    final protected static function revert_language(): void {
        global $SESSION;

        if (!isset(self::$oldforceflang) && !isset($SESSION->forcelang)) {
            return;
        }

        if (isset(self::$oldforceflang) && self::$oldforceflang !== '') {
            $SESSION->forcelang = self::$oldforceflang;
        } else {
            unset($SESSION->forcelang);
        }
        self::$oldforceflang = null;
        moodle_setlocale();
    }

    /**
     * Send notification through the Moodle messaging API.
     *
     * @param \core\message\message $message
     * @param int $notificationid
     * @param int $userid
     * @param int|null $otherid1
     * @param int|null $otherid2
     * @param bool|null $allowmultiple true means multiple notifications are allowed
     * @return void
     */
    final protected static function message_send(
        \core\message\message $message,
        int $notificationid,
        int $userid,
        ?int $otherid1 = null,
        ?int $otherid2 = null,
        bool $allowmultiple = false
    ): void {
        global $DB;

        if (!$DB->record_exists('tool_mulib_notification', ['id' => $notificationid])) {
            // Likely cron running when notification was deleted.
            debugging('invalid notification id', DEBUG_DEVELOPER);
            return;
        }
        if (
            !$allowmultiple && $DB->record_exists(
                'tool_mulib_notification_user',
                ['notificationid' => $notificationid, 'userid' => $userid, 'otherid1' => $otherid1, 'otherid2' => $otherid2]
            )
        ) {
            // Likely caused by two concurrently running cron tasks.
            debugging('Duplicate notification prevented', DEBUG_DEVELOPER);
            return;
        }

        $messageid = message_send($message);
        if (!$messageid) {
            $messageid = null;
        }

        $record = new stdClass();
        $record->notificationid = $notificationid;
        $record->userid = $userid;
        $record->otherid1 = $otherid1;
        $record->otherid2 = $otherid2;
        $record->timenotified = time();
        $record->messageid = $messageid;
        $record->id = $DB->insert_record('tool_mulib_notification_user', $record);
    }
}
