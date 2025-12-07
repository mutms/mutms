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

namespace tool_mulib\phpunit;

/**
 * MuTMS additional tools generator test.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create_extdb_server(): void {
        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        $this->assertInstanceOf(\tool_mulib_generator::class, $generator);

        $server = $generator->create_extdb_server([]);
        $this->assertSame('External server 1', $server->name);
        $this->assertNotEmpty($server->dsn);
        $this->assertSame(null, $server->note);

        $server = $generator->create_extdb_server([
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
    }

    public function test_create_extdb_query(): void {
        if (!get_config('tool_muprog', 'version')) {
            $this->markTestSkipped('extdb query tests require tool_muprog plugin');
        }
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        $this->assertInstanceOf(\tool_mulib_generator::class, $generator);

        $server = $generator->create_extdb_server([]);

        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user',
        ]);
        $this->assertSame($server->id, $query->serverid);
        $this->assertSame((string)$syscontext->id, $query->contextid);
        $this->assertSame('External query 1', $query->name);
        $this->assertSame('tool_muprog', $query->component);
        $this->assertSame('allocation', $query->type);
        $this->assertSame('SELECT * FROM m_user', $query->sqlquery);
        $this->assertSame(null, $query->note);

        $query = $generator->create_extdb_query([
            'contextid' => $catcontext->id,
            'server' => $server->name,
            'name' => 'Other query',
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user',
            'note' => 'some note',
        ]);
        $this->assertSame($server->id, $query->serverid);
        $this->assertSame((string)$catcontext->id, $query->contextid);
        $this->assertSame('Other query', $query->name);
        $this->assertSame('tool_muprog', $query->component);
        $this->assertSame('allocation', $query->type);
        $this->assertSame('SELECT * FROM m_user', $query->sqlquery);
        $this->assertSame('some note', $query->note);

        $query = $generator->create_extdb_query([
            'context' => $catcontext,
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user',
        ]);
        $this->assertSame($server->id, $query->serverid);
        $this->assertSame((string)$catcontext->id, $query->contextid);
    }
}
