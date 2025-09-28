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

namespace tool_muprog\event;

/**
 * User allocation restored event.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_restored extends \core\event\base {
    /**
     * Helper for event creation.
     *
     * @param \stdClass $allocation
     * @param \stdClass $program
     *
     * @return static
     */
    public static function create_from_allocation(\stdClass $allocation, \stdClass $program): static {
        $context = \context::instance_by_id($program->contextid);
        $data = [
            'context' => $context,
            'objectid' => $allocation->id,
            'relateduserid' => $allocation->userid,
            'other' => ['programid' => $program->id],
        ];
        /** @var static $event */
        $event = self::create($data);
        $event->add_record_snapshot('tool_muprog_allocation', $allocation);
        $event->add_record_snapshot('tool_muprog_program', $program);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The allocation of user with id '$this->relateduserid' to program with id '$this->objectid' was restored";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_allocation_restored', 'tool_muprog');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/muprog/management/allocation.php', ['id' => $this->objectid]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_muprog_allocation';
    }
}
