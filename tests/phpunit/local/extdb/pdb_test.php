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

use tool_mulib\local\extdb\pdb;

/**
 * PDO database helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\extdb\pdb
 */
final class pdb_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_constructor(): void {
        $pdb = $this->create_pdb();
        $this->assertInstanceOf(pdb::class, $pdb);
    }

    public function test_connect(): void {
        $ext = pdb::get_test_pdo_extension();
        if (!extension_loaded($ext)) {
            $this->markTestSkipped("PDO extension '$ext' is not available");
        }

        $pdb = $this->create_pdb();
        $pdb->connect();
        $pdb->close();
    }

    public function test_query(): void {
        global $CFG;

        $ext = pdb::get_test_pdo_extension();
        if (!extension_loaded($ext)) {
            $this->markTestSkipped("PDO extension '$ext' is not available");
        }

        $this->preventResetByRollback();

        $pdb = $this->create_pdb();
        $pdb->connect();

        $rs = $pdb->query("SELECT * FROM {$CFG->prefix}user ORDER BY username");
        $users = iterator_to_array($rs);
        $rs->close();
        $this->assertCount(2, $users);
        $this->assertSame('admin', $users[0]['username']);
        $this->assertSame('guest', $users[1]['username']);

        $rs = $pdb->query("SELECT * FROM {$CFG->prefix}user WHERE username = :xu", ['xu' => 'xyz']);
        $users = iterator_to_array($rs);
        $rs->close();
        $this->assertCount(0, $users);

        $rs = $pdb->query("SELECT * FROM {$CFG->prefix}user WHERE username = :xu", ['xu' => 'admin']);
        $users = iterator_to_array($rs);
        $rs->close();
        $this->assertCount(1, $users);
        $this->assertSame('admin', $users[0]['username']);

        $rs = $pdb->query("SELECT * FROM {$CFG->prefix}user WHERE username = :xu AND deleted = :deleted", ['xu' => 'admin', 'deleted' => '0']);
        $users = iterator_to_array($rs);
        $rs->close();
        $this->assertCount(1, $users);
        $this->assertSame('admin', $users[0]['username']);

        $rs = $pdb->query("SELECT * FROM {$CFG->prefix}user WHERE deleted = 0 ORDER BY username", ['xyz' => 1]);
        $users = iterator_to_array($rs);
        $rs->close();
        $this->assertCount(2, $users);
        $this->assertSame('admin', $users[0]['username']);
        $this->assertSame('guest', $users[1]['username']);

        $pdb->close();
    }

    /**
     * Returns pdb test instance.
     * @return pdb
     */
    protected function create_pdb(): pdb {
        $server = pdb::get_test_server_config();
        return new pdb($server);
    }
}
