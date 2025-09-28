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

namespace tool_mulib\local;

use dml_exception;
use core\exception\coding_exception;
use core\exception\invalid_parameter_exception;

/**
 * SQL fragment.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read string $sql SQL fragment
 * @property-read array $params named query parameters
 */
final class sql implements \ArrayAccess {
    /** @var string named parameter regex */
    private const NAMED_REGEX = '/(?<!:):([a-z][a-z0-9_]*)/';
    /** @var string SQL code */
    private $sql;
    /** @var array named parameters */
    private $params;

    /**
     * Create a SQL fragment.
     *
     * @param string $sql
     * @param array $params
     */
    public function __construct(string $sql, array $params = []) {
        [$this->sql, $this->params] = self::normalise_params($sql, $params);
    }

    /**
     * Replace comment with SQL fragment.
     *
     * @param string $comment comment text (without comment '/*' delimiters and spaces)
     * @param sql|string $sql
     * @param array|null $params optional params when sql is string, must not be used with sql instance
     * @return sql $this
     */
    public function replace_comment(string $comment, sql|string $sql, ?array $params = null): self {
        $comment = '/* ' . $comment . ' */';
        $count = substr_count($this->sql, $comment);
        if ($count === 0) {
            throw new coding_exception('SQL comment not found');
        } else if ($count > 1) {
            throw new coding_exception('Multiple SQL comments found');
        }

        if (is_string($sql)) {
            $sql = new sql($sql, (array)$params);
        } else {
            if (isset($params)) {
                throw new coding_exception('params parameter cannot be used together with sql instance');
            }
        }

        $finalparams = $this->params;
        [$newsql, $finalparams] = self::merge_params($sql->sql, $sql->params, $finalparams);

        $finalsql = str_replace($comment, $newsql, $this->sql);

        // Fix order of parameters.
        [$this->sql, $this->params] = self::normalise_params($finalsql, $finalparams);

        return $this;
    }

    /**
     * Wrap SQL fragment in between two strings.
     *
     * NOTE: if query is empty string then nothing changes.
     *
     * @param string $pre
     * @param string $post
     * @return sql $this
     */
    public function wrap(string $pre, string $post): self {
        if ($this->sql === '') {
            return $this;
        }
        $parts = self::normalise_merge_sqls([$pre, $this, $post]);
        [$this->sql, $this->params] = self::merge_sqls($parts, '');
        return $this;
    }

    /**
     * Join sql parts to create a new sql instance.
     *
     * @param string $glue
     * @param array $sqls array of sql instances or strings
     * @return sql
     */
    public static function join(string $glue, array $sqls): sql {
        $sqls = self::normalise_merge_sqls($sqls);
        [$sql, $params] = self::merge_sqls($sqls, $glue);
        return new sql($sql, $params);
    }

    /**
     * Export query in a raw executable format suitable for database console in IDEs.
     *
     * NOTE: to be used for debugging only!
     *
     * @return string
     */
    public function export_debug_query(): string {
        global $DB;

        $sql = $this->sql;
        $prefix = $DB->get_prefix();
        $sql = preg_replace_callback(
            '/\{([a-z][a-z0-9_]*)}/',
            function ($match) use ($prefix) {
                return '"' . $prefix . $match[1] . '"';
            },
            $sql
        );

        $sql .= "\n\n\n";

        foreach ($this->params as $key => $value) {
            $sql .= "--$key = $value\n";
        }

        return $sql;
    }

    /**
     * Normalise parameters.
     *
     * NOTE: named parameters are used internally, unused parameters are removed.
     *
     * @param string $sql
     * @param array $params
     * @return array [$sql, $parameters]
     */
    protected static function normalise_params(string $sql, array $params): array {
        if (str_contains($sql, '$')) {
            throw new coding_exception('Dollar placeholders are not supported in SQL fragments');
        }

        foreach ($params as $key => $value) {
            if (is_object($value)) {
                throw new coding_exception('Invalid database query parameter value', 'Objects are are not allowed: ' . get_class($value));
            }
            if (is_bool($value)) {
                $params[$key] = (int)$value;
            }
        }

        $namedcount = preg_match_all(self::NAMED_REGEX, $sql, $namedmatches);
        $qcount = substr_count($sql, '?');

        if ($namedcount) {
            if ($qcount) {
                throw new dml_exception('mixedtypesqlparam');
            }
            if ($namedcount > count($params)) {
                $a = (object)[
                    'expected' => $namedcount,
                    'actual' => count($params),
                ];
                throw new dml_exception('invalidqueryparam', $a);
            }

            $finalparams = [];
            foreach ($namedmatches[1] as $key) {
                if (!array_key_exists($key, $params)) {
                    throw new dml_exception('missingkeyinsql', $key);
                }
                $finalparams[$key] = $params[$key];
            }
            if ($namedcount !== count($finalparams)) {
                throw new dml_exception('duplicateparaminsql');
            }
            return [$sql, $finalparams];
        } else if ($qcount) {
            $pcount = count($params);
            if ($qcount > $pcount) {
                $a = (object)[
                    'expected' => $qcount,
                    'actual' => count($params),
                ];
                throw new dml_exception('invalidqueryparam', $a);
            } else if ($qcount < $pcount) {
                $params = array_slice($params, 0, $qcount);
            }

            $finalparams = [];
            $i = 0;
            foreach ($params as $param) {
                $i++;
                $finalparams['param' . $i] = $param;
            }
            $i = 0;
            $callback = function () use (&$i) {
                $i++;
                return ':param' . $i;
            };
            $sql = preg_replace_callback('/\?/', $callback, $sql);
            return [$sql, $finalparams];
        } else {
            // Ignore all parameters if there are no placeholders.
            return [$sql, []];
        }
    }

    /**
     * Fix sqls parameter for sql merging.
     *
     * NOTE: empty string queries are removed
     *
     * @param string|sql|sql[] $sqls
     * @return sql[]
     */
    protected static function normalise_merge_sqls(string|sql|array $sqls): array {
        if (!is_array($sqls)) {
            $sqls = [$sqls];
        }
        $result = [];
        foreach ($sqls as $sql) {
            if (is_string($sql)) {
                if ($sql === '') {
                    continue;
                }
                $sql = new sql($sql);
            } else if ($sql instanceof sql) {
                if ($sql->sql === '') {
                    continue;
                }
            } else {
                throw new invalid_parameter_exception('sql instance or string expected');
            }
            $result[] = $sql;
        }
        return $result;
    }

    /**
     * Merge params.
     *
     * @param string $sql
     * @param array $params named parameters
     * @param array $finalparams
     * @return array [$newsql, $finalparams]
     */
    protected static function merge_params(string $sql, array $params, array $finalparams): array {
        $i = 0;
        $callback = function ($match) use (&$finalparams, &$i, $params) {
            $key = $match[1];
            if (!array_key_exists($key, $finalparams)) {
                $finalparams[$key] = $params[$key];
                return ':' . $key;
            }
            do {
                $i++;
                $newkey = 'param' . $i;
            } while (array_key_exists($newkey, $finalparams));
            $finalparams[$newkey] = $params[$key];
            return ':' . $newkey;
        };
        return [preg_replace_callback(self::NAMED_REGEX, $callback, $sql), $finalparams];
    }

    /**
     * Merge multiple SQL fragments.
     *
     * @param sql[] $sqls
     * @param string $glue
     * @return array
     */
    protected static function merge_sqls(array $sqls, string $glue): array {
        if (!$sqls) {
            return ['', []];
        }
        $multiparams = false;
        $allparams = null;
        foreach ($sqls as ['params' => $params]) {
            if ($params) {
                if ($allparams !== null) {
                    $multiparams = true;
                    break;
                }
                $allparams = $params;
            }
        }

        if (!$multiparams) {
            $allsqls = [];
            foreach ($sqls as ['sql' => $sql]) {
                $allsqls[] = $sql;
            }
            $allsqls = implode($glue, $allsqls);
            return [$allsqls, $allparams ?? []];
        }

        $finalparams = [];
        $finalsqls = [];
        foreach ($sqls as ['sql' => $newsql, 'params' => $newparams]) {
            [$newsql, $finalparams] = self::merge_params($newsql, $newparams, $finalparams);
            $finalsqls[] = $newsql;
        }

        return [implode($glue, $finalsqls), $finalparams];
    }

    /**
     * Magic getter method.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed {
        if ($name === 'sql') {
            return $this->sql;
        }
        if ($name === 'params') {
            return $this->params;
        }
        debugging('Invalid sql property', DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Magic setter method.
     *
     * @param string $name
     * @param mixed $value
     * @throws coding_exception
     */
    public function __set(string $name, mixed $value): void {
        throw new coding_exception('SQL fragment properties cannot be changed directly');
    }

    /**
     * Magic isset method.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool {
        return ($name === 'sql' || $name === 'params');
    }

    /**
     * Magic unset method.
     *
     * @param string $name
     * @throws coding_exception
     */
    public function __unset(string $name): void {
        throw new coding_exception('SQL fragment properties cannot be changed directly');
    }

    /**
     * Array access support.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool {
        return ($offset == 0 || $offset == 1 || $offset == 2 || $offset === 'sql' || $offset === 'params');
    }

    /**
     * Array access support.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed {
        if ($offset == 0 || $offset === 'sql') {
            return $this->sql;
        }
        if ($offset == 1 || $offset === 'params') {
            return $this->params;
        }
        if ($offset == 2) {
            return SQL_PARAMS_NAMED;
        }

        debugging('Invalid offset', DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Array access support.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws coding_exception
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        throw new coding_exception('SQL fragment properties cannot be changed directly');
    }

    /**
     * Array access support.
     *
     * @param mixed $offset
     * @return void
     * @throws coding_exception
     */
    public function offsetUnset(mixed $offset): void {
        throw new coding_exception('SQL fragment properties cannot be changed directly');
    }
}
