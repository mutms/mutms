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

namespace tool_mulib\hook;

use tool_mulib\local\extdb\query;

/**
 * Hook for registering of external db query type classes.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('External database query types')]
#[\core\attribute\tags('tool_mulib')]
final class extdb_query_classes {
    /** @var array */
    private $classes = [];

    /**
     * Constructor.
     */
    public function __construct() {
        \core\di::get(\core\hook\manager::class)->dispatch($this);
    }

    /**
     * Register content class.
     *
     * @param string $component
     * @param string $type
     * @param string $classname
     * @return void
     */
    public function register(string $component, string $type, string $classname): void {
        if (isset($this->classes[$component][$type])) {
            debugging("Query class type '$type' is already registered for component '{$component}'");
            return;
        }
        if (!is_subclass_of($classname, query::class)) {
            debugging("Class '$classname' for '($component}/{$type}' is invalid");
            return;
        }
        $this->classes[$component][$type] = $classname;
    }

    /**
     * Returns all registered content classes.
     *
     * @return array
     */
    public function get_classes(): array {
        return $this->classes;
    }
}
