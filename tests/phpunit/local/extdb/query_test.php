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

use tool_mulib\local\extdb\query;

/**
 * External database query tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\extdb\query
 */
final class query_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();

        if (!get_config('tool_muprog', 'version')) {
            $this->markTestSkipped('extdb query tests require tool_muprog plugin');
        }

        $this->resetAfterTest();
    }

    public function test_create(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        $this->assertInstanceOf(\tool_mulib_generator::class, $generator);

        $server = $generator->create_extdb_server([]);

        $query1 = query::create((object)[
            'contextid' => $catcontext->id,
            'serverid' => $server->id,
            'name' => 'Query 1',
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user',
            'note' => 'some note',
        ]);
        $this->assertSame($server->id, $query1->serverid);
        $this->assertSame((string)$catcontext->id, $query1->contextid);
        $this->assertSame('Query 1', $query1->name);
        $this->assertSame('tool_muprog', $query1->component);
        $this->assertSame('allocation', $query1->type);
        $this->assertSame('SELECT * FROM m_user', $query1->sqlquery);
        $this->assertSame('some note', $query1->note);

        $query2 = query::create((object)[
            'contextid' => '',
            'serverid' => $server->id,
            'name' => 'Other query',
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user WHERE deleted = 0',
            'note' => 'other note',
        ]);
        $this->assertSame($server->id, $query2->serverid);
        $this->assertSame((string)$syscontext->id, $query2->contextid);
        $this->assertSame('Other query', $query2->name);
        $this->assertSame('tool_muprog', $query2->component);
        $this->assertSame('allocation', $query2->type);
        $this->assertSame('SELECT * FROM m_user WHERE deleted = 0', $query2->sqlquery);
        $this->assertSame('other note', $query2->note);

        try {
            query::create((object)[
                'contextid' => $syscontext->id,
                'serverid' => $server->id,
                'name' => 'Query 1',
                'component' => 'tool_muprog',
                'type' => 'allocation',
                'sqlquery' => 'SELECT * FROM m_user',
                'note' => 'some note',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid parameter value detected (query name must be unique)', $ex->getMessage());
        }
    }

    public function test_update(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        $this->assertInstanceOf(\tool_mulib_generator::class, $generator);

        $server1 = $generator->create_extdb_server([]);
        $server2 = $generator->create_extdb_server([]);

        $query1 = query::create((object)[
            'contextid' => $catcontext->id,
            'serverid' => $server1->id,
            'name' => 'Query 1',
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user',
            'note' => 'some note',
        ]);
        $query2 = query::create((object)[
            'contextid' => '',
            'serverid' => $server1->id,
            'name' => 'Other query',
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user WHERE deleted = 0',
            'note' => 'other note',
        ]);

        $query1 = query::update((object)[
            'id' => $query1->id,
            'contextid' => $syscontext->id,
            'serverid' => $server2->id,
            'name' => 'Query 1x',
            'component' => 'tool_muprogx',
            'type' => 'allocationx',
            'sqlquery' => 'SELECT * FROM m_course',
            'note' => 'X note',
        ]);
        $this->assertSame($server2->id, $query1->serverid);
        $this->assertSame((string)$syscontext->id, $query1->contextid);
        $this->assertSame('Query 1x', $query1->name);
        $this->assertSame('tool_muprog', $query1->component);
        $this->assertSame('allocation', $query1->type);
        $this->assertSame('SELECT * FROM m_course', $query1->sqlquery);
        $this->assertSame('X note', $query1->note);

        try {
            query::update((object)[
                'id' => $query1->id,
                'name' => 'Other query',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid parameter value detected (query name must be unique)', $ex->getMessage());
        }
    }

    public function test_delete(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        $this->assertInstanceOf(\tool_mulib_generator::class, $generator);

        $server1 = $generator->create_extdb_server([]);
        $server2 = $generator->create_extdb_server([]);

        $query1 = query::create((object)[
            'contextid' => $catcontext->id,
            'serverid' => $server1->id,
            'name' => 'Query 1',
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user',
            'note' => 'some note',
        ]);
        $query2 = query::create((object)[
            'contextid' => '',
            'serverid' => $server1->id,
            'name' => 'Other query',
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => 'SELECT * FROM m_user WHERE deleted = 0',
            'note' => 'other note',
        ]);

        query::delete($query1->id);
        query::delete($query1->id);
        $this->assertFalse($DB->record_exists('tool_mulib_extdb_query', ['id' => $query1->id]));
        $this->assertTrue($DB->record_exists('tool_mulib_extdb_query', ['id' => $query2->id]));
    }
}
