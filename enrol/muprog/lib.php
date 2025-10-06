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

/**
 * Program enrolment plugin class.
 *
 * @package     enrol_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_muprog_plugin extends enrol_plugin {
    /**
     * Returns localised name of enrol instance
     *
     * @param stdClass|null $instance
     * @return string
     */
    public function get_instance_name($instance): string {
        global $DB;

        if (!class_exists(\tool_muprog\local\program::class)) {
            return get_string('error');
        }

        if (!isset($instance->customint1)) {
            // Most likely a sloppy test in rb.
            return get_string('error');
        }

        $program = $DB->get_record('tool_muprog_program', ['id' => $instance->customint1]);

        $name = get_string('program', 'tool_muprog');

        if ($program) {
            $name = $name . ' (' . format_string($program->fullname) . ')';
        }

        return $name;
    }

    /**
     * Do not allow manual adding of enrol instances, everything is managed via programs.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid): bool {
        return false;
    }

    /**
     * Do not allow manual deleting of enrol instances, everything is managed via programs.
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance): bool {
        return false;
    }

    /**
     * Do not allow manual hiding and showing of enrol instances, everything is managed via programs.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance): bool {
        return false;
    }

    /**
     * Do not allow manual unenrolling, everything is managed via programs.
     *
     * @param stdClass $instance course enrol instance
     * @return bool
     */
    public function allow_unenrol(stdClass $instance): bool {
        return false;
    }

    /**
     * Do not show any enrolment UI.
     *
     * @return bool
     */
    public function use_standard_editing_ui(): bool {
        return false;
    }

    /**
     * Ignore restoring of program enrol instances.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid): void {
        // Restoring is not necessary, programs will recreate enrolments automatically.
    }
}
