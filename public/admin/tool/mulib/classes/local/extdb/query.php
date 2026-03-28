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
 * Query helper class.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class query {
    /** @var pdb pdb|null */
    protected $pdb;
    /** @var stdClass query record */
    protected $record;
    /** @var array query parameters */
    protected $parameters = [];

    /**
     * Create query instance and connect external database.
     *
     * @param int $queryid
     */
    public function __construct(int $queryid) {
        global $DB;

        // Override if there are extra constructor parameters needed as query parameters.

        $this->record = $DB->get_record(
            'tool_mulib_extdb_query',
            ['id' => $queryid, 'component' => static::get_component(), 'type' => static::get_type()],
            '*',
            MUST_EXIST
        );
        $server = $DB->get_record('tool_mulib_extdb_server', ['id' => $this->record->serverid], '*', MUST_EXIST);
        $this->pdb = new pdb($server);
        $this->pdb->connect();
    }

    /**
     * Query the external database.
     *
     * @return rs
     */
    final public function query(): rs {
        return $this->pdb->query($this->record->sqlquery, $this->parameters);
    }

    /**
     * Close database connection.
     */
    final public function close(): void {
        $this->pdb = null;
    }

    /**
     * Create query.
     *
     * @param stdClass $data
     * @return stdClass
     */
    final public static function create(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;

        $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);
        $classname = $qman->get_class($data->component, $data->type);
        if (!$classname) {
            throw new invalid_parameter_exception('invalid query component or type');
        }

        if (!empty($data->contextid)) {
            $context = \context::instance_by_id($data->contextid);
            if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
                throw new invalid_parameter_exception('invalid query contextid');
            }
        } else {
            $data->contextid = \context_system::instance()->id;
        }

        $server = $DB->get_record('tool_mulib_extdb_server', ['id' => $data->serverid], '*', MUST_EXIST);
        $data->serverid = $server->id;

        $data->name = trim($data->name);
        if ($data->name === '') {
            throw new invalid_parameter_exception('query name is required');
        }
        if ($DB->record_exists('tool_mulib_extdb_query', ['name' => $data->name])) {
            throw new invalid_parameter_exception('query name must be unique');
        }

        $data->id = $DB->insert_record('tool_mulib_extdb_query', $data);

        return $DB->get_record('tool_mulib_extdb_query', ['id' => $data->id], '*', MUST_EXIST);
    }

    /**
     * Update query.
     *
     * @param stdClass $data
     * @return stdClass
     */
    final public static function update(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        unset($data->component);
        unset($data->type);

        $oldquery = $DB->get_record('tool_mulib_extdb_query', ['id' => $data->id], '*', MUST_EXIST);

        if (property_exists($data, 'contextid')) {
            if ($data->contextid) {
                $context = \context::instance_by_id($data->contextid);
                if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
                    throw new invalid_parameter_exception('invalid query contextid');
                }
            } else {
                $data->contextid = \context_system::instance()->id;
            }
        }

        if (property_exists($data, 'serverid')) {
            $server = $DB->get_record('tool_mulib_extdb_server', ['id' => $data->serverid], '*', MUST_EXIST);
            $data->serverid = $server->id;
        }

        if (property_exists($data, 'name')) {
            $data->name = trim($data->name);
            if ($data->name === '') {
                throw new invalid_parameter_exception('query name is required');
            }
            if ($data->name !== $oldquery->name) {
                if ($DB->record_exists('tool_mulib_extdb_query', ['name' => $data->name])) {
                    throw new invalid_parameter_exception('query name must be unique');
                }
            }
        }

        $DB->update_record('tool_mulib_extdb_query', $data);

        return $DB->get_record('tool_mulib_extdb_query', ['id' => $data->id], '*', MUST_EXIST);
    }

    /**
     * Delete query.
     *
     * @param int $id
     * @return void
     */
    final public static function delete(int $id): void {
        global $DB;

        $DB->delete_records('tool_mulib_extdb_query', ['id' => $id]);
    }

    /**
     * Returns component.
     *
     * @return string
     */
    abstract public static function get_component(): string;

    /**
     * Returns internal query type name.
     *
     * @return string
     */
    abstract public static function get_type(): string;

    /**
     * Returns human-readable query type name.
     *
     * @return string
     */
    abstract public static function get_name(): string;

    /**
     * Returns fake parameters for checking of external DB query.
     *
     * @return array
     */
    abstract public static function get_check_parameters(): array;

    /**
     * Returns help text describing available query parameters.
     *
     * @return string Markdown text
     */
    abstract public static function get_query_help(): string;

    /**
     * Is given query used by the component?
     *
     * @param int $queryid
     * @return bool
     */
    abstract public static function is_query_used(int $queryid): bool;
}
