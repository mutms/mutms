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

namespace mod_mubook\event;

use stdClass;

/**
 * Interactive book module TOC viewed event class.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Create instance of event.
     *
     * @param stdClass $mubook
     * @param \context_module $context
     * @return self
     */
    public static function create_from_mubook(stdClass $mubook, \context_module $context): self {
        $data = [
            'context' => $context,
            'objectid' => $mubook->id,
        ];
        /** @var self $event */
        $event = self::create($data);
        $event->add_record_snapshot('mubook', $mubook);
        return $event;
    }

    #[\Override]
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'mubook';
    }

    #[\Override]
    public static function get_objectid_mapping() {
        return self::NOT_MAPPED;
    }
}
