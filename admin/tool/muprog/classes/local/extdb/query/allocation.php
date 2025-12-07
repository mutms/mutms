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

namespace tool_muprog\local\extdb\query;

use stdClass;

/**
 * Program allocation external database query type.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation extends \tool_mulib\local\extdb\query {
    /**
     * Create query instance and connect external database.
     *
     * @param int $queryid
     * @param stdClass $program
     */
    public function __construct(int $queryid, stdClass $program) {
        parent::__construct($queryid);
        $this->parameters['programid'] = $program->id;
        $this->parameters['programidnumber'] = $program->idnumber;
    }

    /**
     * Returns component.
     *
     * @return string
     */
    public static function get_component(): string {
        return 'tool_muprog';
    }

    /**
     * Returns internal query type name.
     *
     * @return string
     */
    public static function get_type(): string {
        return 'allocation';
    }

    /**
     * Returns human-readable query type name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('extdb_query_allocation', 'tool_muprog');
    }

    /**
     * Returns fake parameters for checking of external DB query.
     *
     * @return array
     */
    public static function get_check_parameters(): array {
        return [
            'programid' => 1,
            'programidnumber' => 'abcdef',
        ];
    }

    /**
     * Returns help text describing available query parameters
     * and required columns.
     *
     * @return string
     */
    public static function get_query_help(): string {
        // Do NOT localise!

        return <<<EOT
Program allocation queries may contain following named parameters to restrict rows to the relevant program:

* `:programid` - id of synchronised program
* `:programidnumber` - idnumber of synchronised program

Each returned row must identify exactly one user to be allocated by including on of columns:

* `userid` - id from user database table
* `username` - login username
* `useremail` - email address
* `useridnumber` - user idnumber

Optionally rows may contain custom program dates either as unix timestamp or in format compatible with strtotime() function:

* `timestart` - start of program, course access opens
* `timedue` - expected due date for program completion
* `timeend` - end of program, course access is suspended
EOT;
    }

    #[\Override]
    public static function is_query_used(int $queryid): bool {
        global $DB;
        return $DB->record_exists('tool_muprog_source', ['type' => 'extdb', 'auxint1' => $queryid]);
    }

    /**
     * Query type registration callback.
     *
     * @param \tool_mulib\hook\extdb_query_classes $hook
     */
    public static function query_manager_callback(\tool_mulib\hook\extdb_query_classes $hook): void {
        $hook->register(self::get_component(), self::get_type(), self::class);
    }
}
