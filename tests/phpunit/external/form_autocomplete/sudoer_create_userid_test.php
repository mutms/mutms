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

namespace tool_musudo\phpunit\external\form_autocomplete;

use tool_musudo\external\form_autocomplete\sudoer_create_userid;
use tool_musudo\local\sudoer;

/**
 * WS test for picking users when adding new sudoer.
 *
 * @group      MuTMS
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\external\form_autocomplete\sudoer_create_userid
 */
final class sudoer_create_userid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $manager1 = $this->getDataGenerator()->create_user([
            'email' => 'manager1@example.com',
            'firstname' => 'Manager',
            'lastname' => '1',
        ]);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        assign_capability('moodle/site:config', CAP_ALLOW, $managerrole->id, $syscontext);
        role_assign($managerrole->id, $manager1->id, $syscontext->id);

        $user1 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'firstname' => 'User',
            'lastname' => '1',
        ]);
        $user2 = $this->getDataGenerator()->create_user([
            'email' => 'user2@example.com',
            'firstname' => 'User',
            'lastname' => '2',
        ]);
        $user3 = $this->getDataGenerator()->create_user([
            'email' => 'user3@example.com',
            'firstname' => 'User',
            'lastname' => '3',
        ]);

        $admin1 = get_admin();
        $admin2 = $this->getDataGenerator()->create_user([
            'email' => 'admin2@example.com',
            'firstname' => 'Admin',
            'lastname' => '2',
        ]);
        set_config('siteadmins', "$admin1->id,$admin2->id");

        $this->setAdminUser();

        $result = sudoer_create_userid::execute('');
        $this->assertFalse($result['overflow']);
        $this->assertCount(4, $result['list']);
        $this->assertSame($manager1->id, $result['list'][0]['value']);
        $this->assertSame($user1->id, $result['list'][1]['value']);
        $this->assertSame($user2->id, $result['list'][2]['value']);
        $this->assertSame($user3->id, $result['list'][3]['value']);

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);
        $result = sudoer_create_userid::execute('');
        $this->assertFalse($result['overflow']);
        $this->assertCount(3, $result['list']);
        $this->assertSame($manager1->id, $result['list'][0]['value']);
        $this->assertSame($user2->id, $result['list'][1]['value']);
        $this->assertSame($user3->id, $result['list'][2]['value']);

        $result = sudoer_create_userid::execute('user2');
        $this->assertFalse($result['overflow']);
        $this->assertCount(1, $result['list']);
        $this->assertSame($user2->id, $result['list'][0]['value']);
        $this->assertStringContainsString(fullname($user2), $result['list'][0]['label']);
        $this->assertStringContainsString($user2->email, $result['list'][0]['label']);

        $this->setUser($manager1);
        try {
            sudoer_create_userid::execute('');
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid role', $ex->getMessage());
        }
    }
}
