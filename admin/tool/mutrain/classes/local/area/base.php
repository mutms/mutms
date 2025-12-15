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

namespace tool_mutrain\local\area;

/**
 * Custom field area base.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * List all available area classes.
     *
     * @return array<string, class-string<base>>
     */
    final public static function get_area_classes(): array {
        return [
            'core_course_course' => \tool_mutrain\local\area\core_course_course::class,
            'tool_muprog_program' => \tool_mutrain\local\area\tool_muprog_program::class,
        ];
    }

    /**
     * Get area class.
     *
     * @param string $component
     * @param string $area
     * @return class-string<base>|null
     */
    final public static function get_area_class(string $component, string $area): ?string {
        $shortname = $component . '_' . $area;
        $classes = self::get_area_classes();
        if (isset($classes[$shortname])) {
            return $classes[$shortname];
        }
        return null;
    }

    /**
     * Sync all completions.
     *
     * @param \progress_trace|null $trace
     * @return void
     */
    final public static function sync_all_completions(?\progress_trace $trace = null): void {
        global $DB;

        // Remove completions for non-existent fields.
        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE NOT EXISTS (

                    SELECT 'x'
                      FROM {customfield_data} cd
                      JOIN {customfield_field} cf ON cf.id = cd.fieldid AND cf.type = 'mutrain'
                     WHERE {tool_mutrain_completion}.fieldid = cf.id AND cd.decvalue IS NOT NULl

                 )";
        $DB->execute($sql);

        if (!$DB->record_exists('customfield_field', ['type' => 'mutrain'])) {
            // No need to do any other processing, there cannot be any completions.
            return;
        }

        // Remove completions for non-existent users.
        $sql = "DELETE
                  FROM {tool_mutrain_completion}
                 WHERE NOT EXISTS (

                    SELECT 'x'
                      FROM {user} u
                     WHERE {tool_mutrain_completion}.userid = u.id AND u.deleted = 0 AND u.confirmed = 1

                 )";
        $DB->execute($sql);

        $classnames = self::get_area_classes();
        foreach ($classnames as $classname) {
            if ($trace) {
                $trace->output("$classname::sync_area_completions");
            }
            $classname::sync_area_completions();
        }
    }

    /**
     * SQL select for the area fields.
     *
     * @param string $alias custom field category table
     * @return string
     */
    abstract public static function get_category_select(string $alias): string;

    /**
     * Synchronise cached values of completions for each field area type.
     */
    abstract public static function sync_area_completions(): void;
}
