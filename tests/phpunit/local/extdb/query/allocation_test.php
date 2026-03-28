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

namespace tool_muprog\phpunit\local\extdb\query;

use tool_muprog\local\extdb\query\allocation;
use tool_mulib\local\extdb\pdb;

/**
 * External database program allocation query test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\extdb\query\allocation
 */
final class allocation_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->preventResetByRollback();
    }

    public function test_query_manager(): void {
        $qman = \core\di::get(\tool_mulib\local\extdb\query_manager::class);
        $classname = $qman->get_class('tool_muprog', 'allocation');
        $this->assertSame(allocation::class, $classname);
    }

    public function test_query(): void {
        global $CFG;
        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $ext = pdb::get_test_pdo_extension();
        if (!extension_loaded($ext)) {
            $this->markTestSkipped("PDO extension '$ext' is not available");
        }

        $program = $programgenerator->create_program();

        $server = $generator->create_extdb_server([]);
        $query1 = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid, 11 AS timestart FROM {$CFG->prefix}user WHERE username='admin'",
        ]);
        $admin = get_admin();

        $aq = new allocation($query1->id, $program);

        $rs = $aq->query();
        $first = $rs->current();
        $this->assertSame(['userid', 'timestart'], array_keys($first));

        $users = iterator_to_array($rs);
        $rs->close();
        $aq->close();
        $this->assertCount(1, $users);
        $this->assertEquals($admin->id, $users[0]['userid']);
        $this->assertEquals('11', $users[0]['timestart']);

        $query2 = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid, 11 AS timestart FROM {$CFG->prefix}user WHERE username='xyz'",
        ]);
        $aq = new allocation($query2->id, $program);

        $rs = $aq->query();
        $first = $rs->current();
        $this->assertSame(null, $first);
    }
}
