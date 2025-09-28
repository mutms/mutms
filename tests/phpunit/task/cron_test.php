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

namespace tool_murelation\phpunit\task;

use tool_murelation\task\cron;
use tool_murelation\local\framework;

/**
 * Cron class tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\task\cron
 */
final class cron_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        $task = new cron();
        $task->execute();

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        ob_start();
        $task = new cron();
        $task->execute();
        $output = ob_get_clean();
        $this->assertStringContainsString('supervisor::cron_cleanup', $output);
        $this->assertStringContainsString('subordinate::cron_cleanup', $output);
        $this->assertStringContainsString('uimode_supervisors::cron_cleanup', $output);
        $this->assertStringContainsString('uimode_teams::cron_cleanup', $output);
    }
}
