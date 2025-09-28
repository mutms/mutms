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

namespace tool_muloginas\phpunit\external;

use tool_muloginas\external\token_check;
use tool_muloginas\external\token_create;
use tool_muloginas\local\loginas;

/**
 * Log-in-as token status check web service tests.
 *
 * @group       MuTMS
 * @package     tool_muloginas
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muloginas\external\token_check
 */
final class token_check_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        global $DB;

        // Do not use PHP class constants here, we do not have them in Javascript.

        $syscontext = \context_system::instance();

        $manager1 = $this->getDataGenerator()->create_user();
        $manager2 = $this->getDataGenerator()->create_user();

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muloginas:loginas', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $manager1->id, $syscontext->id);
        role_assign($managerroleid, $manager2->id, $syscontext->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($manager1);

        $token = token_create::execute($user1->id)['token'];
        $request1 = $DB->get_record('tool_muloginas_request', ['token' => $token]);

        $result = token_check::execute($token);
        $this->assertSame(1, $result['status']);

        $result = token_check::execute('xyz');
        $this->assertSame(0, $result['status']);

        $this->setUser($manager2);
        $result = token_check::execute($token);
        $this->assertSame(0, $result['status']);

        $this->setUser($manager1);
        $request1->timecreated = $request1->timecreated - loginas::LIFETIME - 1;
        $DB->update_record('tool_muloginas_request', $request1);
        $result = token_check::execute($token);
        $this->assertSame(2, $result['status']);

        $request1->timecreated = time() - 10;
        $request1->timeused = time() - 1;
        $DB->update_record('tool_muloginas_request', $request1);
        $result = token_check::execute($token);
        $this->assertSame(3, $result['status']);
    }
}
