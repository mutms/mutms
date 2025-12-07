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

namespace tool_muprog\phpunit\local\source;

use tool_muprog\local\program;
use tool_muprog\local\source\extdb;
use tool_muprog\local\extdb\query\allocation as query;

/**
 * External database query allocation source tests.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\extdb
 */
final class extdb_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('extdb', extdb::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->assertFalse(extdb::is_new_allowed($program));
        set_config('source_extdb_allownew', 1, 'tool_muprog');
        $this->assertTrue(extdb::is_new_allowed($program));
    }

    public function test_is_new_allowed_in_new(): void {
        $this->assertFalse(extdb::is_new_allowed_in_new());
        set_config('source_extdb_allownew', 1, 'tool_muprog');
        $this->assertFalse(extdb::is_new_allowed_in_new());
    }

    public function test_get_name(): void {
        $this->assertSame('External database allocation', extdb::get_name());
    }

    public function test_is_update_allowed(): void {
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program = $programgenerator->create_program();
        $this->assertTrue(extdb::is_update_allowed($program));
    }

    public function test_is_allocation_archive_possible(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user = $this->getDataGenerator()->create_user();
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id={$user->id}",
        ]);
        $program = $programgenerator->create_program();
        $data = (object)[
            'enable' => 1,
            'programid' => $program->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ];
        $source = \tool_muprog\local\source\base::update_source($data);

        extdb::sync($source);
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source->id, 'userid' => $user->id], '*', MUST_EXIST);
        $this->assertTrue(extdb::is_allocation_archive_possible($program, $source, $allocation));

        $data = (object)[
            'enable' => 1,
            'programid' => $program->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 1,
        ];
        $source = \tool_muprog\local\source\base::update_source($data);
        $this->assertFalse(extdb::is_allocation_archive_possible($program, $source, $allocation));
    }

    public function test_is_allocation_restore_possible(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user = $this->getDataGenerator()->create_user();
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id={$user->id}",
        ]);
        $program = $programgenerator->create_program();
        $data = (object)[
            'enable' => 1,
            'programid' => $program->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 1,
        ];
        $source = \tool_muprog\local\source\base::update_source($data);

        extdb::sync($source);
        \tool_mulib\local\extdb\query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=-1",
        ]);
        extdb::sync($source);
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source->id, 'userid' => $user->id], '*', MUST_EXIST);
        $this->assertSame('1', $allocation->archived);

        $this->assertFalse(extdb::is_allocation_restore_possible($program, $source, $allocation));

        $data = (object)[
            'enable' => 1,
            'programid' => $program->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ];
        $source = \tool_muprog\local\source\base::update_source($data);
        $this->assertTrue(extdb::is_allocation_restore_possible($program, $source, $allocation));
    }

    public function test_is_allocation_delete_possible(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user = $this->getDataGenerator()->create_user();
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id={$user->id}",
        ]);
        $program = $programgenerator->create_program();
        $data = (object)[
            'enable' => 1,
            'programid' => $program->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 1,
        ];
        $source = \tool_muprog\local\source\base::update_source($data);

        extdb::sync($source);
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source->id, 'userid' => $user->id], '*', MUST_EXIST);
        $this->assertFalse(extdb::is_allocation_delete_possible($program, $source, $allocation));

        \tool_mulib\local\extdb\query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=-1",
        ]);
        extdb::sync($source);
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source->id, 'userid' => $user->id], '*', MUST_EXIST);
        $this->assertSame('1', $allocation->archived);
        $this->assertTrue(extdb::is_allocation_delete_possible($program, $source, $allocation));

        $data = (object)[
            'enable' => 1,
            'programid' => $program->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ];
        $source = \tool_muprog\local\source\base::update_source($data);
        $this->assertTrue(extdb::is_allocation_delete_possible($program, $source, $allocation));
    }

    public function test_is_import_allowed(): void {
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $this->assertFalse(extdb::is_import_allowed($program1, $program2));
    }

    public function test_fix_allocations(): void {
        $this->assertFalse(extdb::fix_allocations(null, null));
    }

    public function test_cron(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user = $this->getDataGenerator()->create_user();
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id={$user->id}",
        ]);
        $program1 = $programgenerator->create_program();
        $source1 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);
        $program2 = $programgenerator->create_program();
        $source2 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program2->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);
        $program3 = $programgenerator->create_program();
        $source3 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program3->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);
        $program4 = $programgenerator->create_program();
        $source4 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program4->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);

        $source2->auxint4 = time() - (extdb::ADHOC_SYNC_TIMEOUT / 2);
        $DB->set_field('tool_muprog_source', 'auxint4', $source2->auxint4, ['id' => $source2->id]);
        $source3->auxint4 = time() - extdb::ADHOC_SYNC_TIMEOUT - 10;
        $DB->set_field('tool_muprog_source', 'auxint4', $source3->auxint4, ['id' => $source3->id]);
        $program4 = program::archive($program4->id);

        extdb::cron();
        $tasks = $DB->get_records('task_adhoc', ['component' => 'tool_muprog']);
        $this->assertCount(2, $tasks);
        $tasks = array_values($tasks);
        $s1 = json_decode($tasks[0]->customdata);
        $this->assertEquals($source1->id, $s1->id);
        $s3 = json_decode($tasks[1]->customdata);
        $this->assertEquals($source3->id, $s3->id);
    }

    public function test_sync_asap(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user = $this->getDataGenerator()->create_user();
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id={$user->id}",
        ]);
        $program1 = $programgenerator->create_program();
        $source1 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);
        $program2 = $programgenerator->create_program();
        $source2 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program2->id,
            'type' => 'extdb',
            'auxint1' => 0,
            'auxint2' => 0,
        ]);

        $this->assertTrue(extdb::sync_asap($source1));
        $tasks = $DB->get_records('task_adhoc', ['component' => 'tool_muprog']);
        $this->assertCount(1, $tasks);

        $this->assertFalse(extdb::sync_asap($source2));
        $tasks = $DB->get_records('task_adhoc', ['component' => 'tool_muprog']);
        $this->assertCount(1, $tasks);
    }

    public function test_sync(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user([
            'username' => 'user1',
            'email' => 'user1@example.com',
            'idnumber' => 'userid1',
        ]);
        $user2 = $this->getDataGenerator()->create_user([
            'username' => 'user2',
            'email' => 'user2@example.com',
            'idnumber' => 'userid2',
        ]);
        $user3 = $this->getDataGenerator()->create_user([
            'username' => 'user3',
            'email' => 'userx@example.com',
            'idnumber' => '',
        ]);
        $user4 = $this->getDataGenerator()->create_user([
            'username' => 'user4',
            'email' => 'userx@example.com',
            'idnumber' => '',
        ]);
        $user5 = $this->getDataGenerator()->create_user([
            'username' => 'user5',
            'email' => 'user5@example.com',
            'idnumber' => 'userid5',
        ]);
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user",
        ]);
        $program1 = $programgenerator->create_program();
        $source1 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);

        // Test sync via user.id field.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id]));

        // Test sync via user.username field.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT username FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id]));

        // Test sync via user.email field.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT email AS useremail FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id]));

        // Test sync via user.idnumber field including missing value.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT idnumber AS useridnumber FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id},{$user3->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id]));

        // Duplicate user identifier.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT email AS useremail FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id},{$user3->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id]));

        // Missing user identifier.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id})",
        ]);
        $this->assertDebuggingNotCalled();
        $this->assertFalse(extdb::sync($source1));
        $this->assertDebuggingCalled('External query does not contain exactly one valid user identifier column');
        $this->assertCount(0, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));

        // Multiple user identifiers.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid, username FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id})",
        ]);
        $this->assertDebuggingNotCalled();
        $this->assertFalse(extdb::sync($source1));
        $this->assertDebuggingCalled('External query does not contain exactly one valid user identifier column');
        $this->assertCount(0, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));

        // Test auxint2 controls archiving.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 0]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 0]));
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id IN ({$user1->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 0]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 0]));

        $source1 = extdb::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint2' => 1,
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 0]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 1]));

        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id IN ({$user1->id}, {$user2->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 0]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 0]));

        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id IS NULL",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 1]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 1]));

        $source1 = extdb::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint2' => 0,
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(2, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 1]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 1]));

        // Test conflict with manual source.
        $source1 = extdb::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint2' => 1,
        ]);
        $source2 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'manual',
        ]);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source2->id, [$user4->id, $user5->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id IN ({$user4->id}, {$user5->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(4, $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 1]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 1]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source2->id, 'userid' => $user4->id, 'archived' => 0]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source2->id, 'userid' => $user5->id, 'archived' => 0]));
        $allocation5 = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source2->id, 'userid' => $user5->id, 'archived' => 0]);

        \tool_muprog\local\source\manual::allocation_archive($allocation5->id);
        $this->assertTrue(extdb::sync($source1));
        $this->assertCount(4, $DB->get_records('tool_muprog_allocation', ['programid' => $program1->id]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id, 'archived' => 1]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user2->id, 'archived' => 1]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source2->id, 'userid' => $user4->id, 'archived' => 0]));
        $this->assertCount(1, $DB->get_records('tool_muprog_allocation', ['sourceid' => $source2->id, 'userid' => $user5->id, 'archived' => 1]));
    }

    /**
     * Test date overrides for allocation.
     */
    public function test_sync_dates(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user",
        ]);
        $program1 = $programgenerator->create_program();
        $source1 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);

        // Test unix timestamps.
        $teststart = (string)(time() + HOURSECS);
        $testdue = (string)(time() + HOURSECS * 2);
        $testend = (string)(time() + HOURSECS * 3);
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid, $teststart AS timestart, $testdue AS timedue, $testend AS timeend FROM {$CFG->prefix}user WHERE id IN ({$user1->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame($teststart, $allocation->timestart);
        $this->assertSame($testdue, $allocation->timedue);
        $this->assertSame($testend, $allocation->timeend);

        // Test string values.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid, '2005-08-15T15:52:01+00:00' AS timestart FROM {$CFG->prefix}user WHERE id IN ({$user1->id})",
        ]);
        $this->assertTrue(extdb::sync($source1));
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame((string)strtotime('2005-08-15T15:52:01+00:00'), $allocation->timestart);
        $this->assertSame(null, $allocation->timedue);
        $this->assertSame(null, $allocation->timeend);

        // Very old (< 2000) text dates are ignored.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid, '1999-08-15T15:52:01+00:00' AS timestart FROM {$CFG->prefix}user WHERE id IN ({$user1->id})",
        ]);
        $this->setCurrentTimeStart();
        $this->assertTrue(extdb::sync($source1));
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertTimeCurrent($allocation->timestart);
        $this->assertSame(null, $allocation->timedue);
        $this->assertSame(null, $allocation->timeend);

        // Invalid text dates are ignored.
        $DB->delete_records('tool_muprog_allocation', ['sourceid' => $source1->id]);
        query::update((object)[
            'id' => $query->id,
            'sqlquery' => "SELECT id AS userid, 'never ever' AS timestart FROM {$CFG->prefix}user WHERE id IN ({$user1->id})",
        ]);
        $this->setCurrentTimeStart();
        $this->assertTrue(extdb::sync($source1));
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertTimeCurrent($allocation->timestart);
        $this->assertSame(null, $allocation->timedue);
        $this->assertSame(null, $allocation->timeend);
    }

    /**
     * Test program parameters in query.
     */
    public function test_sync_program_parameters(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program([
            'idnumber' => 'pid1',
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid
                        FROM {$CFG->prefix}user
                       WHERE id = {$user1->id}
                             AND $program1->id = :programid AND '{$program1->idnumber}' = :programidnumber",
        ]);
        $source1 = \tool_muprog\local\source\base::update_source((object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ]);

        $this->setCurrentTimeStart();
        $this->assertTrue(extdb::sync($source1));
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $source1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertTimeCurrent($allocation->timestart);
    }
}
