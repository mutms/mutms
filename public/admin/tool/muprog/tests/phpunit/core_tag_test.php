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

namespace tool_muprog\phpunit;

/**
 * Core tag tests.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class core_tag_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers \core\task\tag_cron_task::cleanup
     */
    public function test_tag_cron_task(): void {
        $syscontext = \context_system::instance();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $program2 = $generator->create_program(['fullname' => 'pokus', 'sources' => ['manual' => []]]);

        \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $program1->id, $syscontext, ['foo', 'bar']);
        \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $program2->id, $syscontext, ['bar']);

        $task = new \core\task\tag_cron_task();
        $task->cleanup();
    }
}
