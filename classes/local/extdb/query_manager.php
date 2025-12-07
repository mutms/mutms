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

namespace tool_mulib\local\extdb;

/**
 * External database query type manager.
 *
 * Usage: $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class query_manager {
    /** @var array */
    private $classes;

    /**
     * Constructor.
     */
    public function __construct() {
        $contentclasses = new \tool_mulib\hook\extdb_query_classes();
        $this->classes = $contentclasses->get_classes();
    }

    /**
     * Returns all available query type classes.
     *
     * @return array
     */
    public function get_classes(): array {
        return $this->classes;
    }

    /**
     * Returns query type class.
     *
     * @param string $component
     * @param string $type
     * @return class-string<query>|null
     */
    public function get_class(string $component, string $type): ?string {
        return $this->classes[$component][$type] ?? null;
    }
}
