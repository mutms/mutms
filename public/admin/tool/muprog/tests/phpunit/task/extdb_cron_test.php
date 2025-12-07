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
 * External allocation query cron test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\task\extdb_cron
 */
final class extdb_cron_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->preventResetByRollback();
    }

    public function test_get_name(): void {
        $task = new \tool_muprog\task\extdb_cron();
        $this->assertSame('External database program allocation cron', $task->get_name());
    }

    public function test_execute(): void {
        global $CFG, $DB;
        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $this->setAdminUser();

        $cron = new \tool_muprog\task\extdb_cron();
        ob_start();
        $cron->execute();
        $this->assertSame('', ob_get_clean());

        $tasks = $DB->get_records('task_adhoc', ['component' => 'tool_muprog']);
        $this->assertCount(0, $tasks);

        $server = $generator->create_extdb_server([]);
        $query = $generator->create_extdb_query([
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id IN ({$user1->id},{$user2->id})",
        ]);

        $data = (object)[
            'enable' => 1,
            'programid' => $program1->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 0,
        ];
        $source1 = \tool_muprog\local\source\base::update_source($data);

        $data = (object)[
            'enable' => 1,
            'programid' => $program2->id,
            'type' => 'extdb',
            'auxint1' => $query->id,
            'auxint2' => 1,
        ];
        $source2 = \tool_muprog\local\source\base::update_source($data);
        $program2 = \tool_muprog\local\program::archive($program2->id);

        $cron = new \tool_muprog\task\extdb_cron();
        ob_start();
        $this->setCurrentTimeStart();
        $cron->execute();
        $this->assertSame("source\\extdb::cron\n", ob_get_clean());

        $tasks = $DB->get_records('task_adhoc', ['component' => 'tool_muprog']);
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertSame('\tool_muprog\task\extdb_sync', $task->classname);
        $this->assertSame('1', $task->attemptsavailable);

        $source1 = $DB->get_record('tool_muprog_source', ['id' => $source1->id]);
        $this->assertTimeCurrent($source1->auxint3);

        $source2 = $DB->get_record('tool_muprog_source', ['id' => $source2->id]);
        $this->assertNull($source2->auxint3);
    }
}
