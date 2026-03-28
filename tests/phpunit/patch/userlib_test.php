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

namespace tool_mutenancy\phpunit\patch;

use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy tests for user/lib.php modifications.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class userlib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::user_create_user()
     */
    public function test_user_create_user(): void {
        global $DB, $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $userid = user_create_user((object)[
            'username' => 'user1',
            'email' => 'user1@example.com',
            'firstname' => 'User',
            'lastname' => '1',
        ], false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame(null, $user->tenantid);
        $this->assertSame('0', $user->suspended);

        $userid = user_create_user((object)[
            'username' => 'user2',
            'email' => 'user2@example.com',
            'firstname' => 'User',
            'lastname' => '2',
            'tenantid' => 10,
        ], false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame(null, $user->tenantid);

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant(['archived' => 1]);

        $userid = user_create_user((object)[
            'username' => 'user3',
            'email' => 'user3@example.com',
            'firstname' => 'User',
            'lastname' => '3',
        ], false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame(null, $user->tenantid);
        $this->assertSame('0', $user->suspended);

        $userid = user_create_user((object)[
            'username' => 'user4',
            'email' => 'user4@example.com',
            'firstname' => 'User',
            'lastname' => '4',
            'tenantid' => $tenant1->id,
        ], false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame($tenant1->id, $user->tenantid);

        $userid = user_create_user((object)[
            'username' => 'user5',
            'email' => 'user5@example.com',
            'firstname' => 'User',
            'lastname' => '5',
            'tenant' => $tenant2->idnumber,
        ], false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $user->tenantid);

        $userid = user_create_user((object)[
            'username' => 'user6',
            'email' => 'user6@example.com',
            'firstname' => 'User',
            'lastname' => '6',
            'suspended' => 0,
            'tenant' => $tenant3->idnumber,
        ], false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame($tenant3->id, $user->tenantid);
        $this->assertSame('0', $user->suspended);
    }

    /**
     * @covers ::user_update_user()
     */
    public function test_user_update_user(): void {
        global $DB, $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $userid = user_create_user((object)[
            'username' => 'user1',
            'email' => 'user1@example.com',
            'firstname' => 'User',
            'lastname' => '1',
            'tenantid' => $tenant1->id,
        ], false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $user->tenantid = $tenant2->id;
        user_update_user($user, false, true);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->assertSame($tenant1->id, $user->tenantid);
    }
}
