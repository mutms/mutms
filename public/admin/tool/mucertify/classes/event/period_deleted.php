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

namespace tool_mucertify\event;

/**
 * Certification period deleted event.
 *
 * @package    tool_mucertify
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class period_deleted extends \core\event\base {
    /**
     * Helper for event creation.
     *
     * @param \stdClass $certification
     * @param \stdClass|null $assignment
     * @param \stdClass $period
     *
     * @return static
     */
    public static function create_from_period(\stdClass $certification, ?\stdClass $assignment, \stdClass $period): static {
        $context = \context::instance_by_id($certification->contextid);
        $data = [
            'context' => $context,
            'objectid' => $period->id,
            'relateduserid' => $period->userid,
            'other' => [
                'certificationid' => $certification->id,
                'assignmentid' => $assignment->id ?? null,
            ],
        ];
        /** @var static $event */
        $event = self::create($data);
        $event->add_record_snapshot('tool_mucertify_period', $period);
        if ($assignment) {
            $event->add_record_snapshot('tool_mucertify_assignment', $assignment);
        }
        $event->add_record_snapshot('tool_mucertify_certification', $certification);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Period with id '$this->objectid' for user with id '$this->relateduserid' was deleted";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_period_deleted', 'tool_mucertify');
    }

    /**
     * Get URL related to the action.
     *
     * @return \core\url
     */
    public function get_url() {
        return new \core\url('/admin/tool/mucertify/management/assignment.php', ['id' => $this->other['assignmentid']]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'tool_mucertify_period';
    }
}
