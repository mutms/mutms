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

namespace tool_muprog\phpunit\task;

/**
 * External allocation ad-hoc sync task test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\task\extdb_sync
 */
final class extdb_sync_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->preventResetByRollback();
    }

    public function test_get_name(): void {
        global $CFG;
        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();

        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user",
        ]);
        $data = (object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ];
        $source1 = \tool_muprog\local\source\base::update_source($data);

        $this->setAdminUser();

        $task = \tool_muprog\task\extdb_sync::create_from_source($source1);
        $this->assertSame('External database program allocation', $task->get_name());
    }

    public function test_execute(): void {
        global $CFG, $DB;
        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();

        $program1 = $programgenerator->create_program();

        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id={$user1->id}",
        ]);
        $data = (object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ];
        $source1 = \tool_muprog\local\source\base::update_source($data);

        $this->setAdminUser();

        $this->setCurrentTimeStart();
        $task = \tool_muprog\task\extdb_sync::create_from_source($source1);
        $task->execute();
        $source1 = $DB->get_record('tool_muprog_source', ['id' => $source1->id]);
        $this->assertNull($source1->auxint3);
        $this->assertNull($source1->auxint4);
        $this->assertTimeCurrent($source1->auxint5);
        $allocations = $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id], 'userid ASC');
        $this->assertCount(1, $allocations);

        $DB->delete_records('tool_muprog_allocation', []);
        $task = \tool_muprog\task\extdb_sync::create_from_source($source1);
        $future = (string)(time() + 100);
        $DB->set_field('tool_muprog_source', 'auxint3', $future, ['id' => $source1->id]);
        $task->execute();
        $source1 = $DB->get_record('tool_muprog_source', ['id' => $source1->id]);
        $this->assertSame($future, $source1->auxint3);
        $this->assertNull($source1->auxint4);
        $allocations = $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id], 'userid ASC');
        $this->assertCount(0, $allocations);

        $DB->delete_records('tool_muprog_allocation', []);
        $task = \tool_muprog\task\extdb_sync::create_from_source($source1);
        $recentpending = (string)(time() - 10);
        $DB->set_field('tool_muprog_source', 'auxint4', $recentpending, ['id' => $source1->id]);
        $task->execute();
        $source1 = $DB->get_record('tool_muprog_source', ['id' => $source1->id]);
        $this->assertSame(null, $source1->auxint3);
        $this->assertSame($recentpending, $source1->auxint4);
        $allocations = $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id], 'userid ASC');
        $this->assertCount(0, $allocations);

        $DB->delete_records('tool_muprog_allocation', []);
        $task = \tool_muprog\task\extdb_sync::create_from_source($source1);
        $expired = (string)(time() - 10 - \tool_muprog\local\source\extdb::ADHOC_SYNC_TIMEOUT);
        $DB->set_field('tool_muprog_source', 'auxint4', $expired, ['id' => $source1->id]);
        $this->setCurrentTimeStart();
        $task->execute();
        $source1 = $DB->get_record('tool_muprog_source', ['id' => $source1->id]);
        $this->assertSame(null, $source1->auxint3);
        $this->assertSame(null, $source1->auxint4);
        $this->assertTimeCurrent($source1->auxint5);
        $allocations = $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id], 'userid ASC');
        $this->assertCount(1, $allocations);

        $DB->delete_records('tool_muprog_allocation', []);
        $task = \tool_muprog\task\extdb_sync::create_from_source($source1);
        $ancientpast = (string)(time() - DAYSECS - 10);
        $DB->set_field('tool_muprog_source', 'auxint3', $ancientpast, ['id' => $source1->id]);
        $d = (object)$task->get_custom_data();
        $d->auxint3 = $ancientpast;
        $task->set_custom_data($d);
        $this->setCurrentTimeStart();
        $task->execute();
        $source1 = $DB->get_record('tool_muprog_source', ['id' => $source1->id]);
        $this->assertNull($source1->auxint3);
        $this->assertNull($source1->auxint4);
        $allocations = $DB->get_records('tool_muprog_allocation', ['sourceid' => $source1->id], 'userid ASC');
        $this->assertCount(0, $allocations);
    }
}
