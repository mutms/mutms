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

use PDO;
use PDOException;
use stdClass;

/**
 * A minimalistic external PDO database helper
 * intended for external database read/write access.
 *
 * There is no support for modifying of external database structures.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class pdb {
    /** @var string */
    private $dsn;
    /** @var string|null */
    private $dbuser;
    /** @var string|null */
    private $dbpass;
    /** @var array */
    private $dboptions;

    /** @var PDO|null|false */
    private $pdo = null;

    /**
     * Constructor.
     *
     * @param stdClass $server
     */
    public function __construct(stdClass $server) {
        $this->dsn = $server->dsn;
        $this->dbuser = $server->dbuser;
        $this->dbpass = $server->dbpass;
        if ($server->dboptions) {
            $this->dboptions = (array)json_decode($server->dboptions, flags:JSON_THROW_ON_ERROR);
        } else {
            $this->dboptions = [];
        }
    }

    /**
     * Is PDO extension available?
     *
     * @return bool
     */
    public static function is_pdo_available() {
        return class_exists(PDO::class);
    }

    /**
     * Connect to the external database.
     */
    public function connect(): void {
        if (!self::is_pdo_available()) {
            $this->pdo = false;
            throw new exception('PDO extension is not available');
        }

        if (isset($this->pdo)) {
            debugging('External database connection was already attempted', DEBUG_DEVELOPER);
            return;
        }

        // Force mandatory PDO options, do it here after we checked PDO extension is present.
        $this->dboptions[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $this->dboptions[PDO::ATTR_CASE] = PDO::CASE_LOWER;
        $this->dboptions[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;

        try {
            $this->pdo = new PDO($this->dsn, $this->dbuser, $this->dbpass, $this->dboptions);
            return;
        } catch (PDOException $ex) {
            $this->pdo = false;
            throw $ex;
        }
    }

    /**
     * Make sure the database is connected.
     *
     * @return void
     * @throws \core\exception\coding_exception if database not already connected.
     */
    private function require_connection(): void {
        if ($this->pdo === null || $this->pdo === false) {
            throw new \core\exception\coding_exception('External database is not connected');
        }
    }

    /**
     * Validate and normalise query parameters.
     *
     * @param string $sql
     * @param array $params
     * @return array validated parameters
     */
    private function fix_pdo_params(string $sql, array $params): array {
        $result = [];
        preg_match_all('/(?<!:):[a-z][a-z0-9_]*/', $sql, $matches);
        foreach ($matches[0] as $match) {
            $match = substr($match, 1);
            if (array_key_exists($match, $params)) {
                $result[':' . $match] = $params[$match];
                continue;
            }
            // Let PDO complain about missing parameter.
        }
        return $result;
    }

    /**
     * Query external database.
     *
     * @param string $sql SQL query
     * @param array $params names parameters
     * @return rs
     */
    public function query(string $sql, array $params = []): rs {
        $this->require_connection();
        $pdoparams = $this->fix_pdo_params($sql, $params);
        $statement = $this->pdo->prepare($sql, []);
        $statement->execute($pdoparams);
        return new rs($statement);
    }

    /**
     * Close database connection.
     */
    public function close(): void {
        $this->pdo = false;
    }

    /**
     * Returns PDO extension name for testing.
     *
     * @return string
     */
    public static function get_test_pdo_extension(): string {
        global $DB;

        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily === 'postgres') {
            return 'pdo_pgsql';
        } else if ($dbfamily === 'mysql') {
            return 'pdo_mysql';
        } else if ($dbfamily === 'mssql') {
            return 'pdo_sqlsrv';
        } else {
            throw new \core\exception\coding_exception('Unknown db driver family: ' . $dbfamily);
        }
    }

    /**
     * Get config for simulated external database from $DB settings.
     *
     * @return stdClass
     */
    public static function get_test_server_config(): stdClass {
        global $DB, $CFG;

        if (!PHPUNIT_TEST && (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING)) {
            throw new \core\exception\coding_exception('Simulated external database connection is for testing only!');
        }

        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily === 'postgres') {
            $dsn = "pgsql:host={$CFG->dbhost};dbname={$CFG->dbname}";
        } else if ($dbfamily === 'mysql') {
            $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
        } else if ($dbfamily === 'mssql') {
            $dsn = "sqlsrv:server={$CFG->dbhost};database={$CFG->dbname}";
        } else {
            throw new \core\exception\coding_exception('Unknown db driver family: ' . $dbfamily);
        }
        if (!empty($CFG->dboptions['dbport'])) {
            $dsn .= ';port=' . $CFG->dboptions['dbport'];
        }

        return (object)['dsn' => $dsn, 'dbuser' => $CFG->dbuser, 'dbpass' => $CFG->dbpass, 'dboptions' => json_encode([])];
    }
}
