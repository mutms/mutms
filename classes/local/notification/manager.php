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

namespace tool_mulib\local\notification;

use tool_mulib\output\header_actions;

/**
 * Base for classes that describe notifications in a plugin.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class manager {
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
     * Returns list of all notifications in plugin.
     * @return array of PHP class names with notificationtype as keys
     */
    abstract public static function get_all_types(): array;

    /**
     * Returns list of candidate types for adding of new notifications.
     *
     * @param int $instanceid
     * @return array of type names with notificationtype as keys
     */
    abstract public static function get_candidate_types(int $instanceid): array;

    /**
     * Returns notification class for given type string.
     *
     * @param string $notificationtype
     * @return null|string PHP classname
     */
    final public static function get_classname(string $notificationtype): ?string {
        $types = static::get_all_types();
        if (isset($types[$notificationtype])) {
            return $types[$notificationtype];
        }
        return null;
    }

    /**
     * Returns name of instance for notifications.
     *
     * @param int $instanceid
     * @return string|null
     */
    abstract public static function get_instance_name(int $instanceid): ?string;

    /**
     * Returns context of instance for notifications.
     *
     * @param int $instanceid
     * @return null|\context
     */
    abstract public static function get_instance_context(int $instanceid): ?\context;

    /**
     * Returns url of UI that shows all plugin notifications for given instance id.
     *
     * @param int $instanceid
     * @return \moodle_url|null
     */
    abstract public static function get_instance_management_url(int $instanceid): ?\moodle_url;

    /**
     * Can the current user view instance notifications?
     *
     * @param int $instanceid
     * @return bool
     */
    abstract public static function can_view(int $instanceid): bool;

    /**
     * Can the current user manage instance notifications?
     *
     * @param int $instanceid
     * @return bool
     */
    abstract public static function can_manage(int $instanceid): bool;

    /**
     * Set up notification/view.php page,
     * such as navigation and page name.
     *
     * @param \stdClass $notification
     * @return void
     */
    abstract public static function setup_view_page(\stdClass $notification): void;

    /**
     * Whether import of notification is supported
     *
     * @return bool
     */
    public static function is_import_supported(): bool {
        return false;
    }

    /**
     * Adds the frominstance autocomplete element to import form.
     *
     * @param int $instanceid
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public static function add_import_frominstance_element(int $instanceid, \MoodleQuickForm $mform): void {
        if (self::is_import_supported()) {
            throw new \core\exception\coding_exception('managers that support notification import must override add_import_frominstance_element method');
        }
    }

    /**
     * Validates if the user can import from the specified instanceid.
     *
     * @param int $instanceid
     * @param int $frominstanceid
     * @return bool true means value ok
     */
    public static function validate_import_frominstance(int $instanceid, int $frominstanceid): bool {
        if (static::is_import_supported()) {
            debugging('\tool_mulib\local\notification\manager::validate_import_frominstance must be overridden if import supported', DEBUG_DEVELOPER);
        }
        return false;
    }

    /**
     * Render list of all instance notifications and management UI.
     *
     * @param int $instanceid
     * @param string|null $tableid
     * @return string
     */
    public static function render_notifications(int $instanceid, ?string $tableid = null): string {
        global $DB, $PAGE, $OUTPUT;

        $component = static::get_component();
        $notifications = $DB->get_records('tool_mulib_notification', ['instanceid' => $instanceid, 'component' => $component]);

        $canmanage = static::can_manage($instanceid);
        $canview = static::can_view($instanceid);
        $types = static::get_all_types();

        foreach ($notifications as $notification) {
            /** @var class-string<\tool_mulib\local\notification\notificationtype> $classname */
            $classname = $types[$notification->notificationtype] ?? null;
            if ($classname) {
                $notification->name = $classname::get_name();
            } else {
                $notification->name = $notification->notificationtype
                    . ' <span class="badge bg-danger">' . get_string('error') . '</span>';
            }
        }

        $rows = [];
        foreach ($notifications as $notification) {
            $row = [];
            /** @var class-string<\tool_mulib\local\notification\notificationtype> $classname */
            $classname = $types[$notification->notificationtype] ?? null;
            $name = $notification->name;
            if ($classname) {
                if (!$notification->enabled) {
                    $name = '<span class="dimmed_text">' . $name . '</span>';
                }
                $url = new \moodle_url('/admin/tool/mulib/notification/view.php', ['id' => $notification->id]);
                $name = \html_writer::link($url, $name);
                $row[] = $name;
            } else {
                $row[] = $name;
            }
            $row[] = $notification->custom ? get_string('yes') : get_string('no');
            if ($classname) {
                $row[] = $notification->enabled ? get_string('yes') : get_string('no');
            } else {
                $row[] = '';
            }
            if ($canmanage) {
                $actions = [];
                if (!$classname) {
                    // Do not show the delete link here if they can go the notification details page,
                    // we do not want to encourage users to randomly deleting notification and loosing
                    // track of who was already notified.
                    $url = new \moodle_url('/admin/tool/mulib/notification/delete.php', ['id' => $notification->id]);
                    $icon = new \tool_mulib\output\ajax_form\icon($url, get_string('notification_delete', 'tool_mulib'), 'i/delete');
                    $actions[] = $OUTPUT->render($icon);
                }
                if ($classname) {
                    $url = new \moodle_url('/admin/tool/mulib/notification/update.php', ['id' => $notification->id]);
                    $icon = new \tool_mulib\output\ajax_form\icon($url, get_string('notification_update', 'tool_mulib'), 'i/edit');
                    $actions[] = $OUTPUT->render($icon);
                }
                $row[] = implode('', $actions);
            }
            $rows[] = $row;
        }

        if (static::get_candidate_types($instanceid)) {
            $url = new \moodle_url('/admin/tool/mulib/notification/create.php', ['instanceid' => $instanceid, 'component' => $component]);
            $icon = new \tool_mulib\output\ajax_form\icon($url, get_string('notification_create', 'tool_mulib'), 'e/insert');
            $icon = $OUTPUT->render($icon);
            $cell = new \html_table_cell($icon);
            if ($canmanage) {
                $cell->colspan = 4;
            } else {
                $cell->colspan = 3;
            }
            $rows[] = [$cell];
        }

        $table = new \html_table();
        $table->id = ($tableid ?? static::get_component() . '_notifications');
        $table->head = [
            get_string('notification', 'tool_mulib'),
            get_string('notification_custom', 'tool_mulib'),
            get_string('notification_enabled', 'tool_mulib'),
        ];
        if ($canmanage) {
            $table->head[] = get_string('actions');
        }
        $table->data = $rows;
        $table->attributes['class'] = 'admintable generaltable';
        $result = \html_writer::table($table);

        return $result;
    }

    /**
     * Render list of all instance notifications and management UI.
     *
     * @param int $instanceid
     * @return header_actions
     */
    public static function get_header_actions(int $instanceid): header_actions {
        $actions = new header_actions(get_string('notification_extramenu', 'tool_mulib'));

        if (!static::can_manage($instanceid)) {
            return $actions;
        }
        if (!static::is_import_supported()) {
            return $actions;
        }

        $component = static::get_component();
        $url = new \moodle_url('/admin/tool/mulib/notification/import.php', ['instanceid' => $instanceid, 'component' => $component]);
        $link = new \tool_mulib\output\ajax_form\link($url, get_string('notification_import', 'tool_mulib'));
        $actions->get_dropdown()->add_ajax_form($link);

        return $actions;
    }
}
