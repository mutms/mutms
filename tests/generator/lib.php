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

use tool_muhome\local\page;

/**
 * Custom home pages test data generator.
 *
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_muhome_generator extends component_generator_base {
    /** @var int page count */
    private $pagecount = 0;

    #[\Override]
    public function reset() {
        $this->pagecount = 0;
    }

    /**
     * Create a new page.
     *
     * @param stdClass|array|null $record
     * @return stdClass page record
     */
    public function create_page($record = null): stdClass {
        $this->pagecount++;

        $syscontext = context_system::instance();

        $record = (array)$record;
        if (empty($record['priority'])) {
            unset($record['priority']);
        }
        $defaults = (array)page::get_defaults($record['contextid'] ?? $syscontext->id);
        $record = (object)array_merge($defaults, $record);

        if (!isset($record->name)) {
            $record->name = 'Custom page ' . $this->pagecount;
        }

        return page::create($record);
    }
}
