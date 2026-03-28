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

use tool_muloginas\external\token_create;

/**
 * Log-in-as token creation web service tests.
 *
 * @group       MuTMS
 * @package     tool_muloginas
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muloginas\external\token_create
 */
final class token_create_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        $syscontext = \context_system::instance();

        $manager = $this->getDataGenerator()->create_user();

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muloginas:loginas', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $manager->id, $syscontext->id);

        $admin = get_admin();
        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['confirmed' => 0]);
        $user3 = $this->getDataGenerator()->create_user();
        delete_user($user3);

        $this->setUser($manager);
        $result = token_create::execute($user1->id);
        $this->assertSame(40, strlen($result['token']));
        $this->assertSame(\tool_muloginas\local\loginas::LIFETIME, $result['lifetime']);

        try {
            token_create::execute($admin->id);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Cannot log-in-as given user)', $ex->getMessage());
        }

        try {
            token_create::execute($user3->id);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Cannot log-in-as given user)', $ex->getMessage());
        }
    }
}
