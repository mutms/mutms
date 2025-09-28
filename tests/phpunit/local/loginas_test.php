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

namespace tool_muloginas\phpunit\local;

use tool_muloginas\local\loginas;

/**
 * Log-in-as helper tests.
 *
 * @group       MuTMS
 * @package     tool_muloginas
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muloginas\local\loginas
 */
final class loginas_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_can_loginas(): void {
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
        $user4 = $this->getDataGenerator()->create_user();
        delete_user($user3);
        $user3->deleted = '1';

        $this->setUser($manager);
        $this->assertTrue(loginas::can_loginas($user1));
        $this->assertTrue(loginas::can_loginas($user4));
        $this->assertFalse(loginas::can_loginas($manager));
        $this->assertFalse(loginas::can_loginas($admin));
        $this->assertFalse(loginas::can_loginas($guest));
        $this->assertFalse(loginas::can_loginas($user2));
        $this->assertFalse(loginas::can_loginas($user3));

        $this->setUser($user1);
        $this->assertFalse(loginas::can_loginas($manager));

        \core\session\manager::loginas($manager->id, \context_system::instance());
        $this->assertFalse(loginas::can_loginas($user4));
        $this->assertFalse(loginas::can_loginas($user1));
    }

    public function test_create_request(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $this->setCurrentTimeStart();
        $request = loginas::create_request($user2->id);
        $this->assertSame($user1->id, $request->userid);
        $this->assertSame(40, strlen($request->token));
        $this->assertTimeCurrent($request->timecreated);
        $this->assertNotEmpty($request->sid);
        $this->assertSame($user2->id, $request->targetuserid);
        $this->assertNull($request->timeused);
        $this->assertNull($request->targetsid);
    }

    public function test_validate_request(): void {
        global $DB;

        $_SERVER['HTTP_USER_AGENT'] = 'some browser';

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $request1 = loginas::create_request($user2->id);

        $this->setUser(null);

        $this->assertNull(loginas::validate_request('abc'));

        $this->setCurrentTimeStart();
        [$targetuser, $request, $user] = loginas::validate_request($request1->token);
        $this->assertEquals($user2, $targetuser);
        $this->assertEquals($user1, $user);
        $this->assertSame($request1->id, $request->id);
        $this->assertNull($request->timeused);
        $this->assertNull($request->targetsid);

        $this->assertNotNull(loginas::validate_request($request1->token));

        $_SERVER['HTTP_USER_AGENT'] = 'other browser';
        $this->assertNull(loginas::validate_request($request1->token));

        $_SERVER['HTTP_USER_AGENT'] = 'some browser';
        $this->assertNotNull(loginas::validate_request($request1->token));

        $DB->set_field('tool_muloginas_request', 'timeused', time(), ['id' => $request->id]);
        $this->assertNull(loginas::validate_request($request1->token));
    }

    public function test_log_in_as(): void {
        global $USER;

        $_SERVER['HTTP_USER_AGENT'] = 'some browser';

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $request1 = loginas::create_request($user2->id);
        $this->setUser(null);
        [$targetuser, $request, $user] = loginas::validate_request($request1->token);

        loginas::log_in_as($targetuser, $request, $user);

        $this->assertSame($user2->id, $USER->id);
        $this->assertTrue(\core\session\manager::is_loggedinas());
        $this->assertSame($user1->id, $USER->realuser);
    }

    public function test_cron_cleanup(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $request1 = loginas::create_request($user2->id);
        $request1->timecreated = time() - 60;
        $DB->update_record('tool_muloginas_request', $request1);

        $request2 = loginas::create_request($user2->id);
        $request2->timecreated = time() - WEEKSECS;
        $DB->update_record('tool_muloginas_request', $request2);

        $request3 = loginas::create_request($user2->id);
        $request3->timecreated = time() - HOURSECS;
        $request3->timeused = time() - HOURSECS + 3;
        $request3->targetsid = 'xyz';
        $DB->update_record('tool_muloginas_request', $request3);

        $request4 = loginas::create_request($user2->id);
        $request4->timecreated = time() - (DAYSECS * 2);
        $request4->timeused = time() - (DAYSECS * 2) + 3;
        $request4->targetsid = 'def';
        $DB->update_record('tool_muloginas_request', $request4);

        loginas::cron_cleanup();

        $this->assertTrue($DB->record_exists('tool_muloginas_request', ['id' => $request1->id]));
        $this->assertFalse($DB->record_exists('tool_muloginas_request', ['id' => $request2->id]));
        $this->assertTrue($DB->record_exists('tool_muloginas_request', ['id' => $request3->id]));
        $this->assertFalse($DB->record_exists('tool_muloginas_request', ['id' => $request4->id]));
    }
}
