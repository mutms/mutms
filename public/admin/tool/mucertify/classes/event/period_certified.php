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
 * User certified event.
 *
 * @package    tool_mucertify
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class period_certified extends \core\event\base {
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
        if (!$period->timecertified) {
            throw new \coding_exception('user must have already completed the certification');
        }
        $context = \context::instance_by_id($certification->contextid);
        $data = [
            'context' => $context,
            'objectid' => $period->id,
            'relateduserid' => $period->userid,
            'other' => [
                'certificationid' => $certification->id,
                'assignmentid' => $assignment->id ?? null,
                'timecertified' => $period->timecertified,
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
        return "The user with id '$this->relateduserid' was certified in period with id '$this->objectid'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_period_certified', 'tool_mucertify');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/mucertify/management/period.php', ['id' => $this->objectid]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'tool_mucertify_period';
    }
}
