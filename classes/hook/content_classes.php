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

namespace mod_mubook\hook;

use mod_mubook\local\content;

/**
 * Hook for registering of content type classes.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Interactive book content classes registration')]
#[\core\attribute\tags('mod_mubook')]
final class content_classes {
    /** @var class-string<content>[] */
    private $classes = [];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->register('disclosure', content\disclosure::class);
        $this->register('html', content\html::class);
        $this->register('markdown', content\markdown::class);
        $this->register('unsafehtml', content\unsafehtml::class);
        \core\di::get(\core\hook\manager::class)->dispatch($this);
    }

    /**
     * Register content class.
     *
     * @param string $type
     * @param string $classname
     * @return void
     */
    public function register(string $type, string $classname): void {
        if ($type === 'unknown') {
            // Reserved type.
            return;
        }
        if (isset($this->classes[$type])) {
            debugging("Class type '$type' is already registered to '{$this->classes[$type]}' class");
            return;
        }
        if (!is_subclass_of($classname, content::class)) {
            debugging("Class '$classname' for type '$type' is invalid");
            return;
        }
        $this->classes[$type] = $classname;
    }

    /**
     * Returns all registered content classes.
     *
     * @return \class-string[]
     */
    public function get_classes(): array {
        return $this->classes;
    }
}
