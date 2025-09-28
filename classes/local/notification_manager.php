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

namespace tool_muprog\local;

use stdClass;

/**
 * Programs notification manager.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_manager extends \tool_mulib\local\notification\manager {
    /**
     * Returns list of all notifications in plugin.
     *
     * @return array of PHP class names with notificationtype as keys
     */
    public static function get_all_types(): array {
        // Note: order here affects cron task execution.
        return [
            'allocation' => notification\allocation::class,
            'start' => notification\start::class,
            'completion' => notification\completion::class,
            'duesoon' => notification\duesoon::class,
            'due' => notification\due::class,
            'endsoon' => notification\endsoon::class,
            'endcompleted' => notification\endcompleted::class,
            'endfailed' => notification\endfailed::class,
            'deallocation' => notification\deallocation::class,
            'reset' => notification\reset::class,
        ];
    }

    /**
     * Returns list of candidate types for adding of new notifications.
     *
     * @param int $instanceid
     * @return array of type names with notificationtype as keys
     */
    public static function get_candidate_types(int $instanceid): array {
        global $DB;

        $types = self::get_all_types();

        $existing = $DB->get_records(
            'tool_mulib_notification',
            ['component' => 'tool_muprog', 'instanceid' => $instanceid]
        );
        foreach ($existing as $notification) {
            unset($types[$notification->notificationtype]);
        }

        // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingForeach
        /** @var class-string<notification\base> $classname */
        foreach ($types as $type => $classname) {
            $types[$type] = $classname::get_name();
        }

        return $types;
    }

    /**
     * Returns context of instance for notifications.
     *
     * @param int $instanceid
     * @return null|\context
     */
    public static function get_instance_context(int $instanceid): ?\context {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $instanceid]);
        if (!$program) {
            return null;
        }

        return \context::instance_by_id($program->contextid);
    }

    /**
     * Can the current user view instance notifications?
     *
     * @param int $instanceid
     * @return bool
     */
    public static function can_view(int $instanceid): bool {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $instanceid]);
        if (!$program) {
            return false;
        }

        $context = \context::instance_by_id($program->contextid);
        return has_capability('tool/muprog:view', $context);
    }

    /**
     * Can the current user add/update/delete instance notifications?
     *
     * @param int $instanceid
     * @return bool
     */
    public static function can_manage(int $instanceid): bool {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $instanceid]);
        if (!$program) {
            return false;
        }

        $context = \context::instance_by_id($program->contextid);
        return has_capability('tool/muprog:edit', $context);
    }

    /**
     * Returns name of instance for notifications.
     *
     * @param int $instanceid
     * @return string|null
     */
    public static function get_instance_name(int $instanceid): ?string {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $instanceid]);
        if (!$program) {
            return null;
        }
        return format_string($program->fullname);
    }

    /**
     * Returns url of UI that shows all plugin notifications for given instance id.
     *
     * @param int $instanceid
     * @return \moodle_url|null
     */
    public static function get_instance_management_url(int $instanceid): ?\moodle_url {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $instanceid]);
        if (!$program) {
            return null;
        }

        $context = \context::instance_by_id($program->contextid);
        if (!has_capability('tool/muprog:view', $context)) {
            return null;
        }

        return new \moodle_url('/admin/tool/muprog/management/program_notifications.php', ['id' => $program->id]);
    }

    /**
     * Set up notification/view.php page.
     *
     * @param \stdClass $notification
     * @return void
     */
    public static function setup_view_page(\stdClass $notification): void {
        global $PAGE, $DB, $OUTPUT;

        $program = $DB->get_record('tool_muprog_program', ['id' => $notification->instanceid]);
        if (!$program) {
            return;
        }

        $context = \context::instance_by_id($program->contextid);
        $manageurl = self::get_instance_management_url($notification->instanceid);

        management::setup_program_page($manageurl, $context, $program, 'program_notifications');
        $PAGE->set_url('/admin/tool/mulib/notification/view.php', ['id' => $notification->id]);

        echo $OUTPUT->header();
    }

    /**
     * Send notifications.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @return void
     */
    public static function trigger_notifications(?int $programid, ?int $userid): void {
        global $DB;

        $program = null;
        if ($programid) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
            if ($program->archived) {
                return;
            }
        }

        $user = null;
        if ($userid) {
            $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
            if ($user->deleted || $user->suspended) {
                return;
            }
        }

        $types = self::get_all_types();

        /** @var class-string<notification\base> $classname */
        foreach ($types as $classname) {
            $classname::notify_users($program, $user);
        }
    }

    /**
     * To be called when deleting program allocation.
     *
     * @param \stdClass $allocation
     * @return void
     */
    public static function delete_allocation_notifications(\stdClass $allocation) {
        global $DB;

        if (!property_exists($allocation, 'sourceid')) {
            debugging('Invalid allocation parameter', DEBUG_DEVELOPER);
            return;
        }

        $notifications = $DB->get_records(
            'tool_mulib_notification',
            ['component' => 'tool_muprog', 'instanceid' => $allocation->programid]
        );
        foreach ($notifications as $notification) {
            /** @var class-string<notification\base> $classname */
            $classname = self::get_classname($notification->notificationtype);
            if (!$classname) {
                continue;
            }
            $classname::delete_allocation_notifications($allocation);
        }
    }

    /**
     * To be called when deleting program.
     *
     * @param \stdClass $program
     * @return void
     */
    public static function delete_program_notifications(\stdClass $program) {
        global $DB;

        if (!property_exists($program, 'publicaccess')) {
            debugging('Invalid program parameter', DEBUG_DEVELOPER);
            return;
        }

        $notifications = $DB->get_records(
            'tool_mulib_notification',
            ['component' => 'tool_muprog', 'instanceid' => $program->id]
        );
        foreach ($notifications as $notification) {
            \tool_mulib\local\notification\util::notification_delete($notification->id);
        }
    }

    /**
     * Returns last notification time for given user in program.
     *
     * @param int $allocateduserid allocated user id
     * @param int $programid
     * @param string $notificationtype
     * @return int|null
     */
    public static function get_timenotified(int $allocateduserid, int $programid, string $notificationtype): ?int {
        global $DB;

        $params = ['programid' => $programid, 'allocateduserid' => $allocateduserid, 'type' => $notificationtype];
        $sql = "SELECT MAX(un.timenotified)
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_mulib_notification} n
                       ON n.component = 'tool_muprog' AND n.notificationtype = :type AND n.instanceid = p.id
                  JOIN {tool_mulib_notification_user} un
                       ON un.notificationid = n.id AND un.otherid1 = pa.id
                 WHERE p.id = :programid AND pa.userid = :allocateduserid";
        return $DB->get_field_sql($sql, $params);
    }

    /**
     * Whether import of notification is supported
     *
     * @return bool
     */
    public static function is_import_supported(): bool {
        return true;
    }

    /**
     * Adds the frominstance autocomplete element to import form.
     *
     * @param int $instanceid target instance
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public static function add_import_frominstance_element(int $instanceid, \MoodleQuickForm $mform): void {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $instanceid], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);
        $args = ['id' => $instanceid];
        \tool_muprog\external\form_autocomplete\notification_import_frominstance::add_element(
            $mform,
            $args,
            'frominstance',
            get_string('notification_import_from', 'tool_mulib'),
            $context
        );
        $mform->addRule('frominstance', null, 'required', null, 'client');
    }

    /**
     * Validates if the user can import from the specified instanceid.
     *
     * @param int $instanceid target instance
     * @param int $frominstanceid
     * @return bool true means value ok, false means value is invalid
     */
    public static function validate_import_frominstance(int $instanceid, int $frominstanceid): bool {
        global $DB;

        $targetprogram = $DB->get_record('tool_muprog_program', ['id' => $instanceid], '*', MUST_EXIST);
        $context = \context::instance_by_id($targetprogram->contextid);

        $error = \tool_muprog\external\form_autocomplete\notification_import_frominstance::validate_value(
            $frominstanceid,
            ['id' => $instanceid],
            $context
        );

        return $error === null;
    }
}
