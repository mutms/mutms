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
// phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing

namespace tool_murelation\phpunit\local;

use tool_murelation\local\uimode_supervisors;
use tool_mulib\local\mulib;
use tool_murelation\local\framework;
use core\exception\invalid_parameter_exception;
use core\exception\coding_exception;

/**
 * Supervisors helper class tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\local\uimode_supervisors
 */
final class uimode_supervisors_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_supervisor_edit(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $this->setCurrentTimeStart();
        $supervisor = uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $subordinate = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $oldsupervisor = $supervisor;
        $oldsubordinate = $subordinate;

        $supervisor = uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user3->id,
            'subuserid' => $user2->id,
        ]);
        $this->assertSame($oldsupervisor->id, $supervisor->id);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user3->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $subordinate = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($oldsubordinate->id, $subordinate->id);

        try {
            uimode_supervisors::supervisor_edit((object)[
                'frameworkid' => $framework1->id,
                'userid' => $user2->id,
                'subuserid' => $user2->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Supervisors cannot supervise themselves)', $ex->getMessage());
        }

        try {
            uimode_supervisors::supervisor_edit((object)[
                'frameworkid' => $framework1->id,
                'userid' => -10,
                'subuserid' => $user2->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $ex);
        }

        try {
            uimode_supervisors::supervisor_edit((object)[
                'frameworkid' => $framework1->id,
                'userid' => $user2->id,
                'subuserid' => -10,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $ex);
        }

        try {
            uimode_supervisors::supervisor_edit((object)[
                'frameworkid' => $framework2->id,
                'userid' => $user1->id,
                'subuserid' => $user2->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Framework is not compatible with Supervisors mode', $ex->getMessage());
        }
    }

    public function test_bulk_create(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisors = uimode_supervisors::bulk_create((object)[
            'frameworkid' => $framework1->id,
            'supuserid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $this->assertCount(2, $supervisors);
        $supervisors = array_values($supervisors);
        $this->assertNotNull($supervisors[0]->id, $supervisors[1]->id);

        $supervisor = $supervisors[0];
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $subordinate = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $supervisor = $supervisors[1];
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $subordinate = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor->id, 'userid' => $user3->id], '*', MUST_EXIST);
    }

    public function test_can_manage_subordinate(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $usercontext3 = \context_user::instance($user3->id);
        $usercontext4 = \context_user::instance($user4->id);

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $managerole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $managerole, $syscontext);

        role_assign($managerole, $user1->id, $syscontext);
        role_assign($managerole, $user2->id, $usercontext3);
        role_assign($managerole, $user2->id, $usercontext4);

        uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);

        $this->setAdminUser();
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));
        try {
            uimode_supervisors::can_manage_subordinate($framework2, $user1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Framework is not compatible with Supervisors mode)', $ex->getMessage());
        }

        $this->setUser($user1);
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $this->setUser($user2);
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $this->setUser($user3);
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user3->id);
        $framework1 = framework::update((object)['id' => $framework1->id, 'subordinatecohortid' => $cohort1->id]);

        $this->setAdminUser();
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $this->setUser($user1);
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $this->setUser($user2);
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $cohort2 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort2->id, $user2->id);
        $framework1 = framework::update((object)['id' => $framework1->id, 'managecohortid' => $cohort2->id]);

        $this->setAdminUser();
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $this->setUser($user1);
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        $this->setUser($user2);
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user1->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user2->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework1, $user3->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user4->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework1, $user5->id));

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $usert1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usert2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'alltenants' => 0,
            'tenantids' => [$tenant1->id],
        ]);

        $this->setAdminUser();
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework3, $user1->id));
        $this->assertTrue(uimode_supervisors::can_manage_subordinate($framework3, $usert1->id));
        $this->assertFalse(uimode_supervisors::can_manage_subordinate($framework3, $usert2->id));
    }

    public function test_can_bulk_create(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $managerole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $managerole, $syscontext);
        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $viewrole, $syscontext);

        role_assign($managerole, $user1->id, $syscontext);
        role_assign($viewrole, $user1->id, $syscontext);
        role_assign($managerole, $user2->id, $syscontext);
        role_assign($viewrole, $user3->id, $syscontext);

        $this->setAdminUser();
        $this->assertTrue(uimode_supervisors::can_bulk_create($framework1, $syscontext));
        try {
            uimode_supervisors::can_bulk_create($framework2, $syscontext);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Framework is not compatible with Supervisors mode)', $ex->getMessage());
        }

        $this->setUser($user1);
        $this->assertTrue(uimode_supervisors::can_bulk_create($framework1, $syscontext));

        $this->setUser($user2);
        $this->assertFalse(uimode_supervisors::can_bulk_create($framework1, $syscontext));

        $this->setUser($user2);
        $this->assertFalse(uimode_supervisors::can_bulk_create($framework1, $syscontext));

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        role_assign($managerole, $user3->id, $tenantcontext1);
        role_assign($viewrole, $user2->id, $tenantcontext1);

        $this->setUser($user1);
        $this->assertTrue(uimode_supervisors::can_bulk_create($framework1, $tenantcontext1));
        $this->assertTrue(uimode_supervisors::can_bulk_create($framework1, $tenantcontext2));

        $this->setUser($user3);
        $this->assertTrue(uimode_supervisors::can_bulk_create($framework1, $tenantcontext1));
        $this->assertFalse(uimode_supervisors::can_bulk_create($framework1, $tenantcontext2));
    }

    public function test_get_visible_frameworks(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $framework0 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'visibility' => framework::VISIBILITY_HIDDEN,
            'supervisortitle' => 'Sup 0',
        ]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'visibility' => framework::VISIBILITY_MANAGERS,
            'supervisortitle' => 'Sup 1',
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'visibility' => framework::VISIBILITY_SUPERVISORS,
            'supervisortitle' => 'Sup 2',
        ]);
        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'visibility' => framework::VISIBILITY_SUBORDINATES,
            'supervisortitle' => 'Sup 3',
        ]);
        $framework4 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'visibility' => framework::VISIBILITY_EVERYBODY,
            'supervisortitle' => 'Sup 4',
        ]);
        $framework5 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'visibility' => framework::VISIBILITY_EVERYBODY,
            'supervisortitle' => 'Sup 5',
        ]);

        $user0 = $this->getDataGenerator()->create_user(); // Target.
        $user1 = $this->getDataGenerator()->create_user(); // Manager with permission in user0 context.
        $user2 = $this->getDataGenerator()->create_user(); // Supervisor of user0 in fw2.
        $user3 = $this->getDataGenerator()->create_user(); // Teacher in course.
        $user4 = $this->getDataGenerator()->create_user(); // Subordinate of user0 in fw4 and fw3.
        $user5 = $this->getDataGenerator()->create_user(); // Viewer permission in system.
        $user6 = $this->getDataGenerator()->create_user(); // Manage permission in system.

        $usercontext0 = \context_user::instance($user0->id);
        $managerole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $managerole, $syscontext);
        role_assign($managerole, $user1->id, $usercontext0);
        role_assign($managerole, $user6->id, $syscontext);
        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $viewrole, $syscontext);
        role_assign($viewrole, $user5->id, $syscontext);

        uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user2->id,
            'subuserid' => $user0->id,
        ]);

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user0->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, 'editingteacher');

        uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework4->id,
            'userid' => $user0->id,
            'subuserid' => $user4->id,
        ]);
        uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework3->id,
            'userid' => $user0->id,
            'subuserid' => $user4->id,
        ]);

        uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework5->id,
            'userid' => $user1->id,
            'subuserid' => $user0->id,
        ]);

        $this->setUser(null);
        $this->assertSame([], uimode_supervisors::get_visible_frameworks($user0));

        $this->setGuestUser();
        $this->assertSame([], uimode_supervisors::get_visible_frameworks($user0));

        $this->setAdminUser();
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework1->id, $framework2->id, $framework3->id, $framework4->id, $framework5->id], array_keys($result));
        unset($result[$framework1->id]->supuserid);
        unset($result[$framework1->id]->canmanage);
        $this->assertEquals($framework1, $result[$framework1->id]);

        $this->setUser($user0);
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework5->id], array_keys($result));

        $this->setUser($user1);
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework1->id, $framework2->id, $framework3->id, $framework4->id, $framework5->id], array_keys($result));
        $result = uimode_supervisors::get_visible_frameworks($user1);
        $this->assertEquals([], array_keys($result));

        $this->setUser($user2);
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework2->id, $framework5->id], array_keys($result));
        $result = uimode_supervisors::get_visible_frameworks($user2);
        $this->assertEquals([], array_keys($result));

        $this->setUser($user3);
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework5->id], array_keys($result));

        $this->setUser($user3);
        $result = uimode_supervisors::get_visible_frameworks($user0, $course);
        $this->assertEquals([$framework2->id, $framework5->id], array_keys($result));

        $this->setUser($user4);
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework5->id], array_keys($result));
        $result = uimode_supervisors::get_visible_frameworks($user4);
        $this->assertEquals([$framework3->id, $framework4->id], array_keys($result));

        $this->setUser($user5);
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework2->id, $framework5->id], array_keys($result));
        $result = uimode_supervisors::get_visible_frameworks($user1);
        $this->assertEquals([], array_keys($result));

        $this->setUser($user6);
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework1->id, $framework2->id, $framework3->id, $framework4->id, $framework5->id], array_keys($result));
        $result = uimode_supervisors::get_visible_frameworks($user1);
        $this->assertEquals([$framework1->id, $framework2->id, $framework3->id, $framework4->id, $framework5->id], array_keys($result));

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $usert1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usert2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $framework6 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'alltenants' => 0,
            'tenantids' => [$tenant1->id],
            'supervisortitle' => 'Sup 6',
        ]);

        $this->setAdminUser();
        $result = uimode_supervisors::get_visible_frameworks($user0);
        $this->assertEquals([$framework1->id, $framework2->id, $framework3->id, $framework4->id, $framework5->id, $framework6->id], array_keys($result));
        $result = uimode_supervisors::get_visible_frameworks($usert1);
        $this->assertEquals([$framework1->id, $framework2->id, $framework3->id, $framework4->id, $framework5->id, $framework6->id], array_keys($result));
        $result = uimode_supervisors::get_visible_frameworks($usert2);
        $this->assertEquals([$framework1->id, $framework2->id, $framework3->id, $framework4->id, $framework5->id], array_keys($result));
    }

    public function test_supervisor_has_subordinates(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $framework0 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'subuserid' => $user3->id,
        ]);
        \tool_murelation\local\supervisor::create((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user4->id,
            'subuserids' => [$user3->id],
            'teamname' => 'team 1',
        ]);

        $this->assertTrue(uimode_supervisors::supervisor_has_subordinates($user1->id));
        $this->assertTrue(uimode_supervisors::supervisor_has_subordinates($user2->id));
        $this->assertFalse(uimode_supervisors::supervisor_has_subordinates($user3->id));
        $this->assertFalse(uimode_supervisors::supervisor_has_subordinates($user4->id));
    }

    public function test_tenant_allocation_changed(): void {
        global $DB;
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('Multi-tenancy not available');
        }

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $framework0 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $supervisor0 = \tool_murelation\local\supervisor::create((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user1->id,
            'subuserids' => [$user1->id, $user2->id],
            'teamname' => 'team 1',
        ]);
        $supervisor1 = uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2 = uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'subuserid' => $user1->id,
        ]);

        $this->assertSame(null, $supervisor0->tenantid);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame(null, $supervisor2->tenantid);

        $user1 = \tool_mutenancy\local\user::allocate($user1->id, $tenant1->id);
        $supervisor0 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor0->id], '*', MUST_EXIST);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor0->tenantid);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame($tenant1->id, $supervisor2->tenantid);

        $user1 = \tool_mutenancy\local\user::allocate($user1->id, $tenant2->id);
        $supervisor0 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor0->id], '*', MUST_EXIST);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor0->tenantid);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame($tenant2->id, $supervisor2->tenantid);

        $user1 = \tool_mutenancy\local\user::allocate($user1->id, 0);
        $supervisor0 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor0->id], '*', MUST_EXIST);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor0->tenantid);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame(null, $supervisor2->tenantid);
    }

    public function test_cron_cleanup(): void {
        global $DB;
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('Multi-tenancy not available');
        }

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $framework0 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $supervisor0 = \tool_murelation\local\supervisor::create((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user1->id,
            'subuserids' => [$user1->id, $user2->id],
            'teamname' => 'team 1',
        ]);
        $supervisor1 = uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2 = uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'subuserid' => $user1->id,
        ]);

        $user1 = \tool_mutenancy\local\user::allocate($user1->id, $tenant1->id);

        $DB->set_field('tool_murelation_supervisor', 'tenantid', null, []);
        uimode_supervisors::cron_cleanup();
        $supervisor0 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor0->id], '*', MUST_EXIST);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor0->tenantid);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame($tenant1->id, $supervisor2->tenantid);

        $DB->set_field('tool_murelation_supervisor', 'tenantid', $tenant2->id, []);
        uimode_supervisors::cron_cleanup();
        $supervisor0 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor0->id], '*', MUST_EXIST);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $supervisor0->tenantid);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame($tenant1->id, $supervisor2->tenantid);

        $user2 = \tool_mutenancy\local\user::allocate($user2->id, $tenant2->id);
        $user1 = \tool_mutenancy\local\user::allocate($user1->id, $tenant2->id);
        $supervisor0 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor0->id], '*', MUST_EXIST);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame($tenant2->id, $supervisor0->tenantid);
        $this->assertSame($tenant2->id, $supervisor1->tenantid);
        $this->assertSame($tenant2->id, $supervisor2->tenantid);

        $user2 = \tool_mutenancy\local\user::allocate($user2->id, $tenant1->id);
        uimode_supervisors::cron_cleanup();
        $supervisor0 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor0->id], '*', MUST_EXIST);
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
    }
}
