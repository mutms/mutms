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

namespace tool_mutrain\event;

/**
 * Required credits reached event.
 *
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class required_credits_reached extends \core\event\base {
    /**
     * Helper for event creation.
     *
     * @param \stdClass $credit
     * @param \stdClass $framework
     *
     * @return static
     */
    public static function create_from_credit(\stdClass $credit, \stdClass $framework): static {
        $context = \context::instance_by_id($framework->contextid);
        $data = [
            'context' => $context,
            'objectid' => $credit->id,
            'relateduserid' => $credit->userid,
            'other' => ['credits' => $credit->credits, 'frameworkid' => $credit->frameworkid],
        ];
        /** @var static $event */
        $event = self::create($data);
        $event->add_record_snapshot('tool_mutrain_credit', $credit);
        $event->add_record_snapshot('tool_mutrain_framework', $framework);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->relateduserid' reached required credits in framework '{$this->other['frameworkid']}'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_required_credits_reached', 'tool_mutrain');
    }

    /**
     * Get URL related to the action.
     *
     * @return \core\url
     */
    public function get_url() {
        return new \core\url('/admin/tool/mutrain/management/framework.php', ['id' => $this->other['frameworkid']]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'tool_mutrain_credit';
    }
}
