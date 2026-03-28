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

namespace tool_murelation\phpunit\local;

use tool_murelation\local\callbacks;
use tool_mulib\local\mulib;
use tool_murelation\local\supervisor;
use tool_murelation\local\framework;

/**
 * User relation event and hook callbacks tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_murelation\local\callbacks
 */
final class callbacks_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::user_deleted
     */
    public function test_user_deleted(): void {
        global $DB;

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $supervisor1b = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 2',
            'userid' => $user2->id,
            'subuserids' => [$user4->id, $user5->id],
        ]);

        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2b = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user2->id,
            'subuserid' => $user3->id,
        ]);
        $supervisor2c = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user3->id,
            'subuserid' => $user4->id,
        ]);

        delete_user($user2);

        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1b->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2b->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2c->id]));

        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $this->assertSame($user1->id, $supervisor1->userid);
        $supervisor1b = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1b->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor1b->userid);

        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id, 'userid' => $user4->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id, 'userid' => $user5->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id, 'userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2b->id, 'userid' => $user3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2c->id, 'userid' => $user4->id]));
    }

    /**
     * @covers ::tenant_deleted
     */
    public function test_tenant_deleted(): void {
        global $DB;
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('Multi-tenancy not available');
        }

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user4 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'alltenants' => 0,
            'tenantids' => [$tenant1->id, $tenant2->id],
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'tenantid' => $tenant1->id,
            'subuserids' => [$user2->id],
        ]);
        $supervisor1b = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 2',
            'userid' => $user2->id,
            'tenantid' => $tenant2->id,
            'subuserids' => [$user3->id],
        ]);
        $supervisor1c = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 3',
            'userid' => $user2->id,
            'tenantid' => null,
            'subuserids' => [$user4->id],
        ]);

        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2b = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user3->id,
        ]);
        $supervisor2c = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user4->id,
        ]);

        \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);

        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor1->tenantid);
        $supervisor1b = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1b->id], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $supervisor1b->tenantid);
        $supervisor1c = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1c->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor1c->tenantid);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor2->tenantid);
        $supervisor2b = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2b->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor2b->tenantid);
        $supervisor2c = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2c->id], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $supervisor2c->tenantid);

        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant2->id]));
    }

    /**
     * @covers ::user_allocated
     */
    public function test_user_allocated(): void {
        global $DB;
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('Multi-tenancy not available');
        }

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user0->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user0->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor3 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user3->id,
        ]);

        $this->assertSame($tenant1->id, $supervisor1->tenantid);
        $this->assertSame($tenant2->id, $supervisor2->tenantid);
        $this->assertSame(null, $supervisor3->tenantid);

        \tool_mutenancy\local\user::allocate($user1->id, $tenant2->id);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $supervisor3 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor3->id], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $supervisor1->tenantid);
        $this->assertSame($tenant2->id, $supervisor2->tenantid);
        $this->assertSame(null, $supervisor3->tenantid);

        \tool_mutenancy\local\user::allocate($user1->id, 0);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $supervisor3 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor3->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame($tenant2->id, $supervisor2->tenantid);
        $this->assertSame(null, $supervisor3->tenantid);

        \tool_mutenancy\local\user::allocate($user1->id, $tenant1->id);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $supervisor3 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor3->id], '*', MUST_EXIST);
        $this->assertSame($tenant1->id, $supervisor1->tenantid);
        $this->assertSame($tenant2->id, $supervisor2->tenantid);
        $this->assertSame(null, $supervisor3->tenantid);
    }
}
