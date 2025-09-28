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

/**
 * Program event observer.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_observer {
    /**
     * Course updated observer.
     *
     * @param \core\event\course_updated $event
     * @return void
     */
    public static function course_updated(\core\event\course_updated $event): void {
        global $DB;

        if (!get_config('tool_muprog')) {
            return;
        }

        $course = $event->get_record_snapshot('course', $event->objectid);
        if (!$course) {
            return;
        }

        $items = $DB->get_records('tool_muprog_item', ['courseid' => $course->id]);
        foreach ($items as $item) {
            if ($item->fullname !== $course->fullname) {
                // No need for event here, the course fullname is just a performance thing
                // and a fallback for deleted courses.
                $DB->set_field('tool_muprog_item', 'fullname', $course->fullname, ['id' => $item->id]);
            }
        }
    }

    /**
     * Course deleted observer.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        // Not sure what to do here...
    }

    /**
     * Category deleted observer.
     *
     * @param \core\event\course_category_deleted $event
     * @return void
     */
    public static function course_category_deleted(\core\event\course_category_deleted $event): void {
        global $DB;

        // Programs should have been already moved to the deleted category context,
        // let's move them to system as a fallback.
        $syscontext = \context_system::instance();
        $programs = $DB->get_records('tool_muprog_program', ['contextid' => $event->contextid]);
        foreach ($programs as $program) {
            $data = (object)[
                'id' => $program->id,
                'contextid' => $syscontext->id,
            ];
            program::update_general($data);
        }
    }

    /**
     * User deleted observer.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        allocation::deleted_user_cleanup($event->objectid);
    }

    /**
     * Cohort member removed observer.
     *
     * @param \core\event\cohort_member_added $event
     */
    public static function cohort_member_added(\core\event\cohort_member_added $event): void {
        $updated = \tool_muprog\local\source\cohort::fix_allocations(null, $event->relateduserid);
        if ($updated) {
            allocation::fix_user_enrolments(null, $event->relateduserid);
        }
    }

    /**
     * Cohort member added observer.
     *
     * @param \core\event\cohort_member_removed $event
     */
    public static function cohort_member_removed(\core\event\cohort_member_removed $event): void {
        $updated = \tool_muprog\local\source\cohort::fix_allocations(null, $event->relateduserid);
        if ($updated) {
            allocation::fix_user_enrolments(null, $event->relateduserid);
        }
    }

    /**
     * Course completed observer.
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event): void {
        allocation::fix_user_enrolments(null, $event->relateduserid);
    }

    /**
     * Group deleted observer.
     * @param \core\event\group_deleted $event
     */
    public static function group_deleted(\core\event\group_deleted $event): void {
        global $DB;
        // We cannot do much to prevent the deletion, the group will be recreated if necessary.
        $DB->delete_records('tool_muprog_group', ['groupid' => $event->objectid]);
    }
}
