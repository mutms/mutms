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

namespace tool_mulib\phpunit\task;

use tool_mulib\task\context_map_cron;

/**
 * Test context map cron task.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\task\context_map_cron
 */
final class context_map_cron_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            \tool_mutenancy\local\tenancy::deactivate();
        }
    }

    public function test_registration(): void {
        global $DB;

        $record = $DB->get_record('task_scheduled', ['classname' => '\\' . context_map_cron::class]);
        $this->assertSame('tool_mulib', $record->component);
    }

    public function test_get_name(): void {
        $task = new context_map_cron();
        $this->assertSame('Context map cron', $task->get_name());
    }

    public function test_execute(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');

        $task = new context_map_cron();
        $task->execute();

        $this->assertNotEmpty($DB->get_records('tool_mulib_context_parent'));
        $this->assertNotEmpty($DB->get_records('tool_mulib_context_map'));
    }
}
