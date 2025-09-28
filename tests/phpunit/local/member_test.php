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

namespace tool_mutenancy\phpunit\local;

use tool_mutenancy\local\member;

/**
 * Multi-tenancy tenant member tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\member
 */
final class member_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::confirm
     */
    public function test_confirm(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user(['confirmed' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['confirmed' => 0, 'tenantid' => $tenant1->id]);
        $this->assertSame('0', $user0->confirmed);
        $this->assertSame(null, $user0->tenantid);
        $this->assertSame('0', $user1->confirmed);
        $this->assertSame($tenant1->id, $user1->tenantid);
        $context1 = \context_user::instance($user1->id);
        $this->assertSame((int)$tenant1->id, $context1->tenantid);

        $this->assertTrue(member::confirm($user1->id));
        $user1 = $DB->get_record('user', ['id' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('1', $user1->confirmed);
        $this->assertSame($tenant1->id, $user1->tenantid);

        $this->assertTrue(member::confirm($user1->id));

        try {
            member::confirm($user0->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant members only)', $ex->getMessage());
        }
        $user0 = $DB->get_record('user', ['id' => $user0->id], '*', MUST_EXIST);
        $this->assertSame('0', $user0->confirmed);
        $this->assertSame(null, $user0->tenantid);
    }

    /**
     * @covers ::resend
     */
    public function test_resend(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user(['confirmed' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['confirmed' => 0, 'tenantid' => $tenant1->id]);
        $this->assertSame('0', $user0->confirmed);
        $this->assertSame('0', $user1->confirmed);

        $sink = $this->redirectEmails();
        $this->assertTrue(member::resend($user1->id));
        $emails = $sink->get_messages();
        $this->assertCount(1, $emails);
        $email = reset($emails);
        $this->assertSame('PHPUnit test site: account confirmation', $email->subject);
        $this->assertSame($user1->email, $email->to);
        $user1 = $DB->get_record('user', ['id' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('0', $user1->confirmed);
        $this->assertSame($tenant1->id, $user1->tenantid);

        $sink->clear();
        try {
            member::confirm($user0->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant members only)', $ex->getMessage());
        }
        $this->assertSame(0, $sink->count());
        $sink->close();
    }

    /**
     * @covers ::suspend
     */
    public function test_suspend(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user(['suspended' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['suspended' => 0, 'tenantid' => $tenant1->id]);
        $this->assertSame('0', $user0->suspended);
        $this->assertSame('0', $user1->suspended);

        $user1 = member::suspend($user1->id);
        $this->assertSame('1', $user1->suspended);
        $this->assertSame($tenant1->id, $user1->tenantid);

        $user1 = member::suspend($user1->id);
        $this->assertSame('1', $user1->suspended);

        try {
            member::suspend($user0->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant members only)', $ex->getMessage());
        }
        $this->assertSame('0', $user0->suspended);
        $this->assertSame(null, $user0->tenantid);
    }

    /**
     * @covers ::unsuspend
     */
    public function test_unsuspend(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user1 = $this->getDataGenerator()->create_user(['suspended' => 1, 'tenantid' => $tenant1->id]);
        $this->assertSame('1', $user0->suspended);
        $this->assertSame('1', $user1->suspended);

        $user1 = member::unsuspend($user1->id);
        $this->assertSame('0', $user1->suspended);
        $this->assertSame($tenant1->id, $user1->tenantid);

        $user1 = member::unsuspend($user1->id);
        $this->assertSame('0', $user1->suspended);

        try {
            member::unsuspend($user0->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant members only)', $ex->getMessage());
        }
        $this->assertSame('1', $user0->suspended);
        $this->assertSame(null, $user0->tenantid);
    }

    /**
     * @covers ::unlock
     */
    public function test_unlock(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user([]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        set_config('lockoutthreshold', 3);

        login_lock_account($user0);
        login_lock_account($user1);
        $this->assertTrue(login_is_lockedout($user0));
        $this->assertTrue(login_is_lockedout($user1));

        member::unlock($user1->id);
        $user1 = $DB->get_record('user', ['id' => $user1->id], '*', MUST_EXIST); // Must reload to clear cached prefs.
        $this->assertFalse(login_is_lockedout($user1));

        try {
            member::unlock($user0->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant members only)', $ex->getMessage());
        }
        $user0 = $DB->get_record('user', ['id' => $user0->id], '*', MUST_EXIST); // Must reload to clear cached prefs.
        $this->assertTrue(login_is_lockedout($user0));
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user([]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $this->assertSame('0', $user0->deleted);
        $this->assertSame(null, $user0->tenantid);
        $this->assertSame('0', $user1->deleted);
        $this->assertSame($tenant1->id, $user1->tenantid);
        $context1 = \context_user::instance($user1->id);
        $this->assertSame((int)$tenant1->id, $context1->tenantid);

        member::delete($user1->id);
        $user1 = $DB->get_record('user', ['id' => $user1->id], '*', MUST_EXIST);
        $this->assertSame('1', $user1->deleted);
        $this->assertSame($tenant1->id, $user1->tenantid);
        $context1 = \context_user::instance($user1->id);
        $this->assertSame((int)$tenant1->id, $context1->tenantid);

        try {
            member::delete($user0->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant members only)', $ex->getMessage());
        }
        $user0 = $DB->get_record('user', ['id' => $user0->id], '*', MUST_EXIST);
        $this->assertSame('0', $user0->deleted);
        $this->assertSame(null, $user0->tenantid);
    }

    /**
     * @covers ::user_created
     */
    public function test_user_created(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $user0 = $this->getDataGenerator()->create_user([]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $role = \tool_mutenancy\local\user::get_role();
        $categorycontext1 = \context_coursecat::instance($tenant1->categoryid);

        $cms = $DB->get_records('cohort_members', []);
        $this->assertCount(1, $cms);
        $cm = reset($cms);
        $this->assertSame($tenant1->cohortid, $cm->cohortid);
        $this->assertSame($user1->id, $cm->userid);

        $ras = $DB->get_records('role_assignments', []);
        $this->assertCount(1, $ras);
        $ra = reset($ras);
        $this->assertSame($role->id, $ra->roleid);
        $this->assertSame($user1->id, $ra->userid);
        $this->assertSame((string)$categorycontext1->id, $ra->contextid);
        $this->assertSame('tool_mutenancy', $ra->component);
        $this->assertSame($tenant1->id, $ra->itemid);
    }
}
