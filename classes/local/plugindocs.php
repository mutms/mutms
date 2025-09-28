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

namespace tool_mulib\local;

/**
 * Plugin documentation helper.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugindocs {
    /** @var string file argument validation regex */
    public const FILE_REGEX = '#^[a-z0-9_/]+\.(png|md)$#';

    /**
     * Add page documentation link.
     *
     * @param string $component
     * @param string $file
     * @return void
     */
    public static function set_path(string $component, string $file): void {
        global $PAGE, $CFG;

        if (str_contains($file, '-')) {
            debugging('plugin docs file name cannot contain "-" character', DEBUG_DEVELOPER);
            return;
        }
        $plugindir = \core_component::get_component_directory($component);
        if (!file_exists("$plugindir/docs/en/$file")) {
            debugging('plugin docs file does not exist: ' . $file, DEBUG_DEVELOPER);
        }

        $PAGE->set_docs_path("$CFG->wwwroot/admin/tool/mulib/plugindocs.php/$component/$file");
    }

    /**
     * Render markdown file content to make it look the same as on GitHub.
     *
     * @param string $text
     * @return string
     */
    public static function render_github_markdown(string $text): string {
        require_once(__DIR__ . '/../../vendor/autoload.php');

        $parsedown = new \Parsedown();
        return $parsedown->text($text);
    }
}
