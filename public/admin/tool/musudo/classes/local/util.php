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

namespace tool_musudo\local;

/**
 * Utility class for sudo.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class util {
    /**
     * Cache existence of sudo users.
     */
    public static function fix_musudo_active(): void {
        global $DB;

        $active = (int)$DB->record_exists('tool_musudo_sudoer', []);
        set_config('active', $active, 'tool_musudo');
    }

    /**
     * Are any sudo users present?
     *
     * @return bool
     */
    public static function is_musudo_active(): bool {
        return (bool)get_config('tool_musudo', 'active');
    }

    /**
     * Block access if user not real administrator.
     * @return void
     */
    public static function require_admin(): void {
        require_admin();
        if (!is_siteadmin()) {
            throw new \core\exception\moodle_exception('invalidrole');
        }
    }

    /**
     * Is multi-tenancy available?
     *
     * @return bool
     */
    public static function is_mutenancy_available(): bool {
        return class_exists(\tool_mutenancy\local\tenancy::class);
    }

    /**
     * Is multi-tenancy active?
     *
     * @return bool
     */
    public static function is_mutenancy_active(): bool {
        if (!self::is_mutenancy_available()) {
            return false;
        }
        return \tool_mutenancy\local\tenancy::is_active();
    }
}
