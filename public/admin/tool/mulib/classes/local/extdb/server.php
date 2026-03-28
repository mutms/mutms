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

use core\exception\invalid_parameter_exception;
use stdClass;

/**
 * Server helper class.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class server {
    /**
     * Create external db server.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $data->name = trim($data->name);
        if ($data->name === '') {
            throw new invalid_parameter_exception('server name is required');
        }
        if ($DB->record_exists('tool_mulib_extdb_server', ['name' => $data->name])) {
            throw new invalid_parameter_exception('server name must be unique');
        }

        $data->id = $DB->insert_record('tool_mulib_extdb_server', $data);

        return $DB->get_record('tool_mulib_extdb_server', ['id' => $data->id], '*', MUST_EXIST);
    }

    /**
     * Update external db server.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;

        $oldserver = $DB->get_record('tool_mulib_extdb_server', ['id' => $data->id], '*', MUST_EXIST);

        if (!empty($data->changedbpass)) {
            $data->dbpass = $data->newdbpass;
        }

        if (property_exists($data, 'name')) {
            $data->name = trim($data->name);
            if ($data->name === '') {
                throw new invalid_parameter_exception('server name is required');
            }
            if ($data->name !== $oldserver->name) {
                if ($DB->record_exists('tool_mulib_extdb_server', ['name' => $data->name])) {
                    throw new invalid_parameter_exception('server name must be unique');
                }
            }
        }

        $DB->update_record('tool_mulib_extdb_server', $data);

        return $DB->get_record('tool_mulib_extdb_server', ['id' => $data->id], '*', MUST_EXIST);
    }

    /**
     * Delete external db server.
     *
     * @param int $id
     * @return void
     */
    public static function delete(int $id): void {
        global $DB;

        if (!$DB->record_exists('tool_mulib_extdb_server', ['id' => $id])) {
            return;
        }

        if ($DB->record_exists('tool_mulib_extdb_query', ['serverid' => $id])) {
            throw new \core\exception\invalid_parameter_exception('Server is used by a query');
        }

        $DB->delete_records('tool_mulib_extdb_server', ['id' => $id]);
    }
}
