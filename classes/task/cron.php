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

namespace tool_mucertify\task;

/**
 * Certification cron task.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
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
        return get_string('taskcron', 'tool_mucertify');
    }

    /**
     * Run task for all program cron stuff.
     */
    public function execute() {
        global $DB;

        // Do no use util::is_mucertify_active() here, this is used as final cleanup code.
        if (!$DB->record_exists('tool_mucertify_certification', [])) {
            return;
        }

        $trace = new \text_progress_trace();

        $trace->output('assignment::fix_assignment_sources');
        \tool_mucertify\local\assignment::fix_assignment_sources(null, null);

        $trace->output('mucertify::sync_certifications');
        \tool_muprog\local\source\mucertify::sync_certifications(null, null);

        $trace->output('notification_manager::trigger_notifications');
        \tool_mucertify\local\notification_manager::trigger_notifications(null, null);

        $trace->output('period::process_recertifications');
        \tool_mucertify\local\period::process_recertifications(null, null);

        $trace->output('certificate::cron');
        \tool_mucertify\local\certificate::cron();

        $trace->output('util::cleanup_uploaded_data');
        \tool_mucertify\local\util::cleanup_uploaded_data();

        $trace->finished();
    }
}
