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

use tool_muprog\local\source\extdb;
use stdClass;

/**
 * External database allocation task.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class extdb_sync extends \core\task\adhoc_task {
    /**
     * Constructor.
     *
     * @param stdClass $source
     */
    public static function create_from_source(stdClass $source): self {
        global $DB;

        $task = new self();

        $source->auxint3 = (string)time();
        $DB->set_field('tool_muprog_source', 'auxint3', $source->auxint3, ['id' => $source->id]);

        $task->set_component('tool_muprog');
        $task->set_custom_data($source);

        return $task;
    }

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_extdb_sync', 'tool_muprog');
    }

    /**
     * Allocate users using external database query.
     */
    public function execute(): void {
        global $DB;

        $oldsource = (object)$this->get_custom_data();

        $source = $DB->get_record('tool_muprog_source', ['id' => $oldsource->id]);
        if (!$source || !$source->auxint3) {
            // No need for sync.
            return;
        }

        if ($source->auxint3 != $oldsource->auxint3) {
            // Some other task was scheduled later, ignore this one.
            return;
        }

        // Reset the flag for requesting of sync.
        $DB->set_field('tool_muprog_source', 'auxint3', null, ['id' => $source->id]);

        if ($source->auxint3 + DAYSECS < time()) {
            // The request is a day old, ignore it.
            return;
        }

        if ($source->auxint4 + extdb::ADHOC_SYNC_TIMEOUT > time()) {
            // Let's wait for another task to complete.
            return;
        }

        try {
            $DB->set_field('tool_muprog_source', 'auxint4', time(), ['id' => $source->id]);
            $success = extdb::sync($source);
            if ($success) {
                $DB->set_field('tool_muprog_source', 'auxint5', time(), ['id' => $source->id]);
            }
        } finally {
            $DB->set_field('tool_muprog_source', 'auxint4', null, ['id' => $source->id]);
        }
    }

    #[\Override]
    public function retry_until_success(): bool {
        // No need to retry automatically, they can do it manually from UI.
        return false;
    }
}
