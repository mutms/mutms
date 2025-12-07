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

use mod_mubook\local\chapter;
use stdClass;

/**
 * Interactive book chapter updated event.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_updated extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param chapter $chapter
     * @return self
     */
    public static function create_from_chapter(chapter $chapter): self {
        $mubook = $chapter->get_mubook();
        $context = $chapter->get_context();

        $data = [
            'context' => $context,
            'objectid' => $chapter->id,
        ];
        /** @var self $event */
        $event = self::create($data);
        $event->add_record_snapshot('mubook', $mubook);
        $event->add_record_snapshot('mubook_chapter', $chapter->get_record());
        return $event;
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' updated the chapter with id '$this->objectid'";
    }

    #[\Override]
    public static function get_name() {
        return get_string('event_chapter_updated', 'mod_mubook');
    }

    #[\Override]
    public function get_url() {
        return new \core\url('/mod/mubook/viewchapter.php', ['id' => $this->objectid]);
    }

    #[\Override]
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'mubook_chapter';
    }

    #[\Override]
    public static function get_objectid_mapping() {
        return self::NOT_MAPPED;
    }
}
