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

namespace tool_muprog\task;

/**
 * Program cron.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron extends \core\task\scheduled_task {
    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcron', 'tool_muprog');
    }

    /**
     * Run task for all program cron stuff.
     */
    public function execute() {
        global $DB;

        // Do no use util::is_muprog_active() here, this is used as final cleanup code.
        if (!$DB->record_exists('tool_muprog_program', [])) {
            return;
        }

        $trace = new \text_progress_trace();

        $trace->output('allocation::fix_allocation_sources');
        \tool_muprog\local\allocation::fix_allocation_sources(null, null);

        $trace->output('allocation::fix_enrol_instances');
        \tool_muprog\local\allocation::fix_enrol_instances(null);

        $trace->output('allocation::fix_user_enrolments');
        \tool_muprog\local\allocation::fix_user_enrolments(null, null, $trace);

        $trace->output('calendar::fix_program_events');
        \tool_muprog\local\calendar::fix_program_events(null, $trace);

        $trace->output('notification_manager::trigger_notifications');
        \tool_muprog\local\notification_manager::trigger_notifications(null, null);

        $trace->output('util::cleanup_uploaded_data');
        \tool_muprog\local\util::cleanup_uploaded_data();

        $trace->finished();
    }
}
