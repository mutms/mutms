<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This sudoer is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This sudoer is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this sudoer.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_musudo\event;

/**
 * Sudoer created event.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer_created extends \core\event\base {
    /**
     * Helper for event creation.
     *
     * @param \stdClass $sudoer
     *
     * @return static
     */
    public static function create_from_sudoer(\stdClass $sudoer): static {
        $context = \context_system::instance();
        $data = [
            'context' => $context,
            'objectid' => $sudoer->id,
            'relateduserid' => $sudoer->userid,
        ];
        /** @var static $event */
        $event = self::create($data);
        $event->add_record_snapshot('tool_musudo_sudoer', $sudoer);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' added privileged user with id '$this->relateduserid'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_sudoer_created', 'tool_musudo');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/musudo/index.php');
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_musudo_sudoer';
    }
}
