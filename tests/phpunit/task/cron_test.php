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

namespace tool_muloginas\phpunit\task;

use tool_muloginas\local\loginas;
use tool_muloginas\task\cron;

/**
 * Log-in-as cron tests.
 *
 * @group       MuTMS
 * @package     tool_muloginas
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muloginas\task\cron
 */
final class cron_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        global $DB;

        $cron = new cron();
        $cron->execute();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $request = loginas::create_request($user2->id);

        $cron->execute();
        $this->assertTrue($DB->record_exists('tool_muloginas_request', ['id' => $request->id]));

        $request->timecreated = time() - WEEKSECS;
        $DB->update_record('tool_muloginas_request', $request);
        $cron->execute();
        $this->assertFalse($DB->record_exists('tool_muloginas_request', ['id' => $request->id]));
    }
}
