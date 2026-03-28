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

use tool_mulib\local\extdb\server;
use tool_mulib\local\extdb\query;

/**
 * MuTMS additional tools generators.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_mulib_generator extends component_generator_base {
    /** @var int extdb server count */
    private $servercount = 0;

    /** @var int extdb query count */
    private $querycount = 0;

    #[\Override]
    public function reset() {
        $this->servercount = 0;
        $this->querycount = 0;
    }

    /**
     * Create new external database server.
     *
     * NOTE: it defaults to current test database.
     *
     * @param stdClass|array $record
     * @return stdClass framework record
     */
    public function create_extdb_server($record): stdClass {
        $this->servercount++;

        $server = \tool_mulib\local\extdb\pdb::get_test_server_config();
        $record = (object)((array)$record + (array)$server);

        if (!isset($record->name)) {
            $record->name = 'External server ' . $this->servercount;
        } else {
            $record->name = $record->name;
        }

        return server::create($record);
    }

    /**
     * Create new external database query.
     *
     * @param stdClass|array $record
     * @return stdClass framework record
     */
    public function create_extdb_query($record): stdClass {
        global $DB;

        $this->querycount++;

        $record = (object)(array)$record;

        if (!empty($record->context)) {
            $record->contextid = $record->context->id;
            unset($record->context);
        }

        if (!isset($record->name)) {
            $record->name = 'External query ' . $this->querycount;
        }
        if (empty($record->serverid)) {
            $server = $DB->get_record('tool_mulib_extdb_server', ['name' => $record->server], '*', MUST_EXIST);
            $record->serverid = $server->id;
        }

        return query::create($record);
    }
}
