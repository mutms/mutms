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
// phpcs:disable moodle.Files.LineLength.MaxExceeded

namespace tool_mulib\phpunit\local;

use tool_mulib\local\sql;
use core\exception\moodle_exception;
use core\exception\coding_exception;
use core\exception\invalid_parameter_exception;
use dml_exception;

/**
 * SQL fragment tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mulib\local\sql
 */
final class sql_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Constructor test.
     *
     * @covers \tool_mulib\local\sql::normalise_parameters
     */
    public function test_constructor(): void {
        $sql = new sql('');
        $this->assertSame('', $sql->sql);
        $this->assertSame([], $sql->params);

        $sql = new sql('a=b', []);
        $this->assertSame('a=b', $sql->sql);
        $this->assertSame([], $sql->params);

        $sql = new sql('a=b /* test */', []);
        $this->assertSame('a=b /* test */', $sql->sql);
        $this->assertSame([], $sql->params);

        $sql = new sql('a=b', [1, 2, 3]);
        $this->assertSame('a=b', $sql->sql);
        $this->assertSame([], $sql->params);

        $sql = new sql('a = :param', ['param' => 11]);
        $this->assertSame('a = :param', $sql->sql);
        $this->assertSame(['param' => 11], $sql->params);

        $sql = new sql('a = :param', ['xx' => 2, 'param' => 11]);
        $this->assertSame('a = :param', $sql->sql);
        $this->assertSame(['param' => 11], $sql->params);

        $sql = new sql('a = ? AND b = ?', [11, false]);
        $this->assertSame('a = :param1 AND b = :param2', $sql->sql);
        $this->assertSame(['param1' => 11, 'param2' => 0], $sql->params);

        $sql = new sql('a = ? AND b = ?', [11, false, 99]);
        $this->assertSame('a = :param1 AND b = :param2', $sql->sql);
        $this->assertSame(['param1' => 11, 'param2' => 0], $sql->params);

        try {
            new sql('a = ? AND b = ?', [11, new \stdClass()]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Invalid database query parameter value (Objects are are not allowed: stdClass)', $ex->getMessage());
        }

        try {
            new sql('a = ? AND b = ?', [11]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_exception::class, $ex);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 1.', $ex->getMessage());
        }

        try {
            new sql('a = :a AND b = :b', ['a' => 1, 'c' => 2]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_exception::class, $ex);
            $this->assertSame('ERROR: missing param "b" in query', $ex->getMessage());
        }

        try {
            new sql('a = :a AND b = :a', ['a' => 1]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_exception::class, $ex);
            $this->assertSame('ERROR: Incorrect number of query parameters. Expected 2, got 1.', $ex->getMessage());
        }

        try {
            new sql('a = :a AND b = ?', ['a' => 1, 'c' => 2]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_exception::class, $ex);
            $this->assertSame('ERROR: Mixed types of sql query parameters!!', $ex->getMessage());
        }

        try {
            new sql('a = $1', [11]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Dollar placeholders are not supported in SQL fragments', $ex->getMessage());
        }
    }

    /**
     * Test replacing of comments with SQL fragments.
     *
     * @covers \tool_mulib\local\sql::replace_comment
     */
    public function test_replace_comment(): void {
        global $DB;

        $sql = new sql("SELECT u.* FROM {user} u WHERE u.deleted = 0 /* confirmed */ AND u.auth = :auth", ['auth' => 'manual']);

        // Make sure comments are supported by all database.
        $DB->get_records_sql($sql->sql, $sql->params);

        $result = $sql->replace_comment('confirmed', 'AND u.confirmed = 1');
        $this->assertSame($result, $sql);
        $this->assertSame('SELECT u.* FROM {user} u WHERE u.deleted = 0 AND u.confirmed = 1 AND u.auth = :auth', $sql->sql);
        $this->assertSame(['auth' => 'manual'], $sql->params);

        $sql = new sql("SELECT u.* FROM {user} u WHERE u.deleted = 0 /* confirmed */ AND u.auth = :auth", ['auth' => 'manual']);
        $result = $sql->replace_comment('confirmed', 'AND u.confirmed = ?', [1]);
        $this->assertSame($result, $sql);
        $this->assertSame('SELECT u.* FROM {user} u WHERE u.deleted = 0 AND u.confirmed = :param1 AND u.auth = :auth', $sql->sql);
        $this->assertSame(['param1' => 1, 'auth' => 'manual'], $sql->params);

        $sql = new sql("SELECT u.* FROM {user} u WHERE u.deleted = 0 /* confirmed */ AND u.auth = :auth", ['auth' => 'manual']);
        $sql->replace_comment('confirmed', new sql('AND u.confirmed = :auth', ['auth' => 1]));
        $this->assertSame('SELECT u.* FROM {user} u WHERE u.deleted = 0 AND u.confirmed = :param1 AND u.auth = :auth', $sql->sql);
        $this->assertSame(['param1' => 1, 'auth' => 'manual'], $sql->params);

        $sql = new sql("SELECT u.* FROM {user} u WHERE u.deleted = 0 /* confirmed */ AND u.auth = :auth", ['auth' => 'manual']);
        try {
            $sql->replace_comment('xconfirmed', 'AND u.confirmed = 1');
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL comment not found', $ex->getMessage());
        }

        $sql = new sql("SELECT u.* FROM {user} u WHERE u.deleted = 0 /* confirmed */ AND u.auth = :auth /* confirmed */", ['auth' => 'manual']);
        try {
            $sql->replace_comment('confirmed', 'AND u.confirmed = 1');
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Multiple SQL comments found', $ex->getMessage());
        }
    }

    /**
     * Test wrapping.
     *
     * @covers \tool_mulib\local\sql::wrap
     */
    public function test_wrap(): void {
        $sql = new sql('');
        $result = $sql->wrap('(', ')');
        $this->assertSame($sql, $result);
        $this->assertSame('', $sql->sql);
        $this->assertSame([], $sql->params);

        $sql = new sql('a = :param1', ['param1' => 2]);
        $sql->wrap('(', ')');
        $this->assertSame('(a = :param1)', $sql->sql);
        $this->assertSame(['param1' => 2], $sql->params);

        $sql = new sql('a = :param1', ['param1' => 2]);
        $sql->wrap('', '');
        $this->assertSame('a = :param1', $sql->sql);
        $this->assertSame(['param1' => 2], $sql->params);
    }

    /**
     * Test joining os sqls.
     *
     * @covers \tool_mulib\local\sql::join
     */
    public function test_append(): void {
        $sql0 = new sql('');
        $sql1 = new sql('a = :param1', ['param1' => 2]);
        $sql2 = new sql('a = :param1', ['param1' => 1]);

        $sql = sql::join('', [$sql0]);
        $this->assertSame('', $sql->sql);
        $this->assertSame([], $sql->params);

        $sql = sql::join('', [$sql0, $sql1]);
        $this->assertSame('a = :param1', $sql->sql);
        $this->assertSame(['param1' => 2], $sql->params);

        $sql = sql::join(' AND ', [$sql0, $sql1, 'b = 2', $sql2]);
        $this->assertSame('a = :param1 AND b = 2 AND a = :param2', $sql->sql);
        $this->assertSame(['param1' => 2, 'param2' => 1], $sql->params);

        try {
            sql::join('', [[], $sql1]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (sql instance or string expected)', $ex->getMessage());
        }
    }

    /**
     * Test query debugging.
     *
     * @covers \tool_mulib\local\sql::export_debug_query
     */
    public function test_export_debug_query(): void {
        global $DB;

        $prefix = $DB->get_prefix();
        $sql = new sql(
            'SELECT u.* FROM {user} u WHERE u.deleted = 0 AND u.confirmed = :confirmed AND u.auth = :auth',
            ['confirmed' => 1, 'auth' => 'manual']
        );

        $expected = "SELECT u.* FROM \"{$prefix}user\" u WHERE u.deleted = 0 AND u.confirmed = :confirmed AND u.auth = :auth


--confirmed = 1
--auth = manual
";
        $this->assertSame($expected, $sql->export_debug_query());
    }

    /**
     * Test magic property access.
     *
     * @covers \tool_mulib\local\sql::__get
     * @covers \tool_mulib\local\sql::__set
     * @covers \tool_mulib\local\sql::__isset
     */
    public function test_magic(): void {
        $sql = new sql('a = :param', ['param' => 11]);

        $this->assertSame('a = :param', $sql->sql);
        $this->assertSame(['param' => 11], $sql->params);

        $this->assertTrue(isset($sql->sql));
        $this->assertTrue(isset($sql->params));
        $this->assertFalse(isset($sql->xxx));

        $this->assertDebuggingNotCalled();

        $this->assertSame(null, $sql->xxx);
        $this->assertDebuggingCalled('Invalid sql property');

        try {
            $sql->sql = 'abc'; // Ignore this error in IDEs!
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            $sql->params = []; // Ignore this error in IDEs!
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            $sql->xxx = [];
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            unset($sql->sql);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            unset($sql->params);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }
    }

    /**
     * Test array access.
     *
     * @covers \tool_mulib\local\sql::offsetExists
     * @covers \tool_mulib\local\sql::offsetGet
     * @covers \tool_mulib\local\sql::offsetSet
     * @covers \tool_mulib\local\sql::offsetUnset
     */
    public function test_array_access(): void {
        $sql = new sql('a = :param', ['param' => 11]);

        $this->assertSame('a = :param', $sql['sql']);
        $this->assertSame('a = :param', $sql[0]);
        $this->assertSame(['param' => 11], $sql['params']);
        $this->assertSame(['param' => 11], $sql[1]);
        $this->assertSame(SQL_PARAMS_NAMED, $sql[2]);

        $this->assertTrue(isset($sql['sql']));
        $this->assertTrue(isset($sql[0]));
        $this->assertTrue(isset($sql['params']));
        $this->assertTrue(isset($sql[1]));
        $this->assertTrue(isset($sql[2]));
        $this->assertFalse(isset($sql[3]));
        $this->assertFalse(isset($sql['xx']));

        $this->assertDebuggingNotCalled();

        $this->assertSame(null, $sql[3]);
        $this->assertDebuggingCalled('Invalid offset');

        try {
            $sql['sql'] = 'xyz';
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            $sql[0] = 'xyz';
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            $sql['params'] = [];
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            $sql[1] = [];
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            unset($sql['sql']);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            unset($sql[0]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            unset($sql['params']);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }

        try {
            unset($sql[1]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: SQL fragment properties cannot be changed directly', $ex->getMessage());
        }
    }
}
