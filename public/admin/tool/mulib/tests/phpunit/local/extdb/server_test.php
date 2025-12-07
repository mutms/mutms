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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing
// phpcs:disable moodle.Commenting.MissingDocblock.MissingTestcaseMethodDescription

namespace tool_mulib\phpunit\local\extdb;

use tool_mulib\local\extdb\server;

/**
 * External database server tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\extdb\server
 */
final class server_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        $server = server::create((object)[
            'name' => 'Test server',
            'dsn' => 'pgsql:host=127.0.01;dbname=moodledb',
            'dbuser' => 'root',
            'dbpass' => 'secret',
            'dboptions' => json_encode([3 => 2]),
            'note' => 'Some test',
        ]);
        $this->assertSame('Test server', $server->name);
        $this->assertSame('pgsql:host=127.0.01;dbname=moodledb', $server->dsn);
        $this->assertSame('root', $server->dbuser);
        $this->assertSame('secret', $server->dbpass);
        $this->assertSame('{"3":2}', $server->dboptions);
        $this->assertSame('Some test', $server->note);

        try {
            server::create((object)[
                'name' => 'Test server',
                'dsn' => 'pgsql:host=127.0.0.2;dbname=moodledb',
                'dbuser' => 'rootx',
                'dbpass' => 'secretx',
                'dboptions' => '',
                'note' => 'Some test 2',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid parameter value detected (server name must be unique)', $ex->getMessage());
        }
    }

    public function test_update(): void {
        $server1 = server::create((object)[
            'name' => 'Test server',
            'dsn' => 'pgsql:host=127.0.01;dbname=moodledb',
            'dbuser' => 'root',
            'dbpass' => 'secret',
            'dboptions' => json_encode([3 => 2]),
            'note' => 'Some test',
        ]);
        $server2 = server::create((object)[
            'name' => 'Test server 2',
            'dsn' => 'pgsql:host=127.0.0.2;dbname=moodledb',
            'dbuser' => 'root',
            'dbpass' => 'secret',
            'dboptions' => '',
            'note' => 'Some test',
        ]);

        $server1 = server::update((object)[
            'id' => $server1->id,
            'name' => 'Test server X',
            'dsn' => 'pgsql:host=127.0.0.1;dbname=moodledb',
            'dbuser' => 'rooty',
            'dbpass' => 'secrety',
            'dboptions' => json_encode([3 => 3]),
            'note' => 'Some test 2',
        ]);
        $this->assertSame('Test server X', $server1->name);
        $this->assertSame('pgsql:host=127.0.0.1;dbname=moodledb', $server1->dsn);
        $this->assertSame('rooty', $server1->dbuser);
        $this->assertSame('secrety', $server1->dbpass);
        $this->assertSame('{"3":3}', $server1->dboptions);
        $this->assertSame('Some test 2', $server1->note);

        try {
            server::update((object)[
                'id' => $server1->id,
                'name' => 'Test server 2',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid parameter value detected (server name must be unique)', $ex->getMessage());
        }
    }

    public function test_delete(): void {
        global $DB;

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');

        $server1 = server::create((object)[
            'name' => 'Test server 1',
            'dsn' => 'pgsql:host=127.0.0.1;dbname=moodledb',
            'dbuser' => 'root',
            'dbpass' => 'secret',
            'dboptions' => json_encode([3 => 2]),
            'note' => 'Some test',
        ]);
        $server2 = server::create((object)[
            'name' => 'Test server 2',
            'dsn' => 'pgsql:host=127.0.0.2;dbname=moodledb',
            'dbuser' => 'root',
            'dbpass' => 'secret',
            'dboptions' => '',
            'note' => 'Some test',
        ]);

        server::delete($server1->id);
        server::delete($server1->id);
        $this->assertFalse($DB->record_exists('tool_mulib_extdb_server', ['id' => $server1->id]));

        if (!get_config('tool_muprog', 'version')) {
            return;
        }

        $query = $generator->create_extdb_query([
            'serverid' => $server2->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user',
        ]);

        try {
            server::delete($server2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid parameter value detected (Server is used by a query)', $ex->getMessage());
        }
        $this->assertTrue($DB->record_exists('tool_mulib_extdb_server', ['id' => $server2->id]));
    }
}
