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

namespace tool_mulib\external\form_autocomplete;

use stdClass;
use tool_mulib\local\sql;

/**
 * Base class for cohort auto-completion fields.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class cohort extends base {
    /** @var string|null user table */
    protected const ITEM_TABLE = 'cohort';
    /** @var string|null not used, there is custom format_label() method */
    protected const ITEM_FIELD = 'name';

    /**
     * Returns cohort query data.
     *
     * @param string $search
     * @param string $tablealias
     * @return sql
     */
    public static function get_cohort_search_query(string $search, string $tablealias = ''): sql {
        return static::get_search_query($search, ['name', 'idnumber', 'description'], $tablealias);
    }

    /**
     * Can the current user use the cohort.
     *
     * @param stdClass $cohort
     * @return bool
     */
    public static function is_cohort_visible(stdClass $cohort): bool {
        if ($cohort->visible) {
            return true;
        }
        $cohortcontext = \context::instance_by_id($cohort->contextid, IGNORE_MISSING);
        if (!$cohortcontext) {
            return false;
        }
        return has_capability('moodle/cohort:view', $cohortcontext);
    }
}
