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

namespace tool_murelation\task;

/**
 * Multi-tenancy cron task.
 *
 * @package    tool_murelation
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
        return get_string('taskcron', 'tool_murelation');
    }

    /**
     * Run task for all program cron stuff.
     */
    public function execute() {
        if (!\tool_murelation\local\util::is_murelation_active()) {
            return;
        }

        $trace = new \text_progress_trace();

        $trace->output('supervisor::cron_cleanup');
        \tool_murelation\local\supervisor::cron_cleanup();

        $trace->output('subordinate::cron_cleanup');
        \tool_murelation\local\subordinate::cron_cleanup();

        $trace->output('uimode_supervisors::cron_cleanup');
        \tool_murelation\local\uimode_supervisors::cron_cleanup();

        $trace->output('uimode_teams::cron_cleanup');
        \tool_murelation\local\uimode_teams::cron_cleanup();

        $trace->finished();
    }
}
