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

use tool_murelation\local\uimode_teams;
use tool_mulib\local\mulib;
use tool_murelation\local\framework;
use core\exception\invalid_parameter_exception;
use core\exception\coding_exception;

/**
 * Teams helper class tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\local\uimode_teams
 */
final class uimode_teams_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_team_create(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $this->setCurrentTimeStart();
        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame(null, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Team 1', $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);

        $this->setCurrentTimeStart();
        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 2',
            'teamidnumber' => 't2',
            'userid' => $user1->id,
            'teamcohortcreate' => 1,
            'teamcohortname' => 'Team 2 cohort',
            'supmanaged' => 1,
            'maxsubordinates' => 11,
            'teamposition' => 'worker',
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Team 2', $supervisor->teamname);
        $this->assertSame('t2', $supervisor->teamidnumber);
        $this->assertNotNull($supervisor->teamcohortid);
        $this->assertSame('1', $supervisor->supmanaged);
        $this->assertSame('11', $supervisor->maxsubordinates);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('Team 2 cohort', $cohort->name);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(2, $subordinates);
        $this->assertSame($framework1->id, $subordinates[0]->frameworkid);
        $this->assertSame($user2->id, $subordinates[0]->userid);
        $this->assertSame('worker', $subordinates[0]->teamposition);
        $this->assertTimeCurrent($subordinates[0]->timecreated);
        $this->assertSame($framework1->id, $subordinates[1]->frameworkid);
        $this->assertSame($user3->id, $subordinates[1]->userid);
        $this->assertSame('worker', $subordinates[1]->teamposition);
        $this->assertTimeCurrent($subordinates[1]->timecreated);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'userid' => $user1->id,
            'subuserids' => [$user1->id],
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Team 3', $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(1, $subordinates);
        $this->assertSame($framework1->id, $subordinates[0]->frameworkid);
        $this->assertSame($user1->id, $subordinates[0]->userid);
        $this->assertSame(null, $subordinates[0]->teamposition);
        $this->assertTimeCurrent($subordinates[0]->timecreated);

        try {
            uimode_teams::team_create((object)[
                'frameworkid' => $framework0->id,
                'teamname' => 'Team X',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Framework is not compatible with Teams mode', $ex->getMessage());
        }

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcatcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 4',
            'tenantid' => $tenant1->id,
            'teamcohortcreate' => 1,
        ]);
        $this->assertSame($tenant1->id, $supervisor->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('Team 4', $cohort->name);
        $this->assertSame((string)$tenantcatcontext1->id, $cohort->contextid);
    }

    public function test_team_update(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
        ]);

        $supervisor = uimode_teams::team_update((object)[
            'id' => $supervisor->id,
            'teamname' => 'Team 2',
            'teamidnumber' => 't2',
            'userid' => $user1->id,
            'teamcohortcreate' => 1,
            'teamcohortname' => 'Team 2 cohort',
            'supmanaged' => 1,
            'maxsubordinates' => 11,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Team 2', $supervisor->teamname);
        $this->assertSame('t2', $supervisor->teamidnumber);
        $this->assertNotNull($supervisor->teamcohortid);
        $this->assertSame('1', $supervisor->supmanaged);
        $this->assertSame('11', $supervisor->maxsubordinates);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('Team 2 cohort', $cohort->name);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);

        $supervisor = uimode_teams::team_update((object)[
            'id' => $supervisor->id,
            'teamname' => 'Team 1',
            'teamidnumber' => 't1',
            'userid' => $user2->id,
            'teamcohortname' => 'Team 1 COHORT',
            'supmanaged' => 0,
            'maxsubordinates' => '14',
        ]);
        $this->assertSame($user2->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Team 1', $supervisor->teamname);
        $this->assertSame('t1', $supervisor->teamidnumber);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('14', $supervisor->maxsubordinates);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('Team 1 COHORT', $cohort->name);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);

        $supervisor = uimode_teams::team_update((object)[
            'id' => $supervisor->id,
            'teamidnumber' => '',
            'userid' => '',
            'supmanaged' => 0,
            'maxsubordinates' => '',
        ]);
        $this->assertSame(null, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Team 1', $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        try {
            uimode_teams::team_update((object)[
                'id' => $supervisor0->id,
                'teamname' => 'Team X',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Framework is not compatible with Teams mode', $ex->getMessage());
        }

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 2',
            'tenantid' => $tenant1->id,
        ]);

        $supervisor = uimode_teams::team_update((object)[
            'id' => $supervisor->id,
            'teanntid' => $tenant2->id,
        ]);
        $this->assertSame($tenant1->id, $supervisor->tenantid);
    }

    public function test_team_delete(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor1 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'teamidnumber' => 't1',
            'userid' => $user1->id,
            'teamcohortcreate' => 1,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $supervisor2 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'teamidnumber' => 't2',
            'userid' => $user2->id,
            'teamcohortcreate' => 1,
            'subuserids' => [$user4->id],
        ]);

        uimode_teams::team_delete($supervisor1->id);
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertFalse($DB->record_exists('cohort', ['id' => $supervisor1->teamcohortid]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('cohort', ['id' => $supervisor2->teamcohortid]));

        uimode_teams::team_delete($supervisor1->id);
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('cohort', ['id' => $supervisor2->teamcohortid]));

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        try {
            uimode_teams::team_delete($supervisor0->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Framework is not compatible with Teams mode', $ex->getMessage());
        }
    }

    public function test_members_create(): void {
        global $DB;

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'userid' => $user1->id,
        ]);

        $subordinates = uimode_teams::members_create((object)[
            'supervisorid' => $supervisor->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $this->assertCount(2, $subordinates);
        $subordinates = array_values($subordinates);
        $this->assertSame($framework1->id, $subordinates[0]->frameworkid);
        $this->assertSame($user2->id, $subordinates[0]->userid);
        $this->assertSame($supervisor->id, $subordinates[0]->supervisorid);
        $this->assertSame(null, $subordinates[0]->teamposition);
        $this->assertSame($framework1->id, $subordinates[1]->frameworkid);
        $this->assertSame($user3->id, $subordinates[1]->userid);
        $this->assertSame($supervisor->id, $subordinates[1]->supervisorid);
        $this->assertSame(null, $subordinates[1]->teamposition);

        $subordinates = uimode_teams::members_create((object)[
            'supervisorid' => $supervisor->id,
            'subuserids' => [$user1->id, $user3->id],
            'teamposition' => 'Manager',
        ]);
        $this->assertCount(2, $subordinates);
        $subordinates = array_values($subordinates);
        $this->assertSame($framework1->id, $subordinates[0]->frameworkid);
        $this->assertSame($user1->id, $subordinates[0]->userid);
        $this->assertSame($supervisor->id, $subordinates[0]->supervisorid);
        $this->assertSame('Manager', $subordinates[0]->teamposition);
        $this->assertSame($framework1->id, $subordinates[1]->frameworkid);
        $this->assertSame($user3->id, $subordinates[1]->userid);
        $this->assertSame($supervisor->id, $subordinates[1]->supervisorid);
        $this->assertSame(null, $subordinates[1]->teamposition);

        \tool_murelation\local\supervisor::update((object)['id' => $supervisor->id, 'maxsubordinates' => 3]);
        try {
            uimode_teams::members_create((object)[
                'supervisorid' => $supervisor->id,
                'subuserids' => [$user4->id, $user3->id],
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (subordinate limit was already reached)', $ex->getMessage());
        }
        $this->assertCount(3, $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id]));

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        try {
            uimode_teams::members_create((object)[
                'supervisorid' => $supervisor0->id,
                'subuserids' => [$user1->id],
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Framework is not compatible with Teams mode', $ex->getMessage());
        }

        try {
            uimode_teams::members_create((object)[
                'subuserids' => [$user2->id, $user3->id],
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (supervisorid is required)', $ex->getMessage());
        }
    }

    public function test_member_update(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'userid' => $user1->id,
        ]);

        $subordinates = uimode_teams::members_create((object)[
            'supervisorid' => $supervisor->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $subordinates = array_values($subordinates);

        $subordinate = uimode_teams::member_update((object)[
            'id' => $subordinates[0]->id,
            'teamposition' => 'observer',
        ]);
        $this->assertSame($user2->id, $subordinate->userid);
        $this->assertSame($supervisor->id, $subordinate->supervisorid);
        $this->assertSame('observer', $subordinate->teamposition);
    }

    public function test_member_delete(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'userid' => $user1->id,
        ]);

        $subordinates = uimode_teams::members_create((object)[
            'supervisorid' => $supervisor->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $subordinates = array_values($subordinates);

        uimode_teams::member_delete($subordinates[0]->id);
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['id' => $subordinates['0']->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['id' => $subordinates['1']->id]));
    }

    public function test_get_team_context(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
        ]);

        $this->assertEquals($syscontext, uimode_teams::get_team_context($framework1, $supervisor));

        try {
            uimode_teams::get_team_context($framework0, $supervisor);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Framework is not compatible with Teams mode)', $ex->getMessage());
        }

        try {
            uimode_teams::get_team_context($framework2, $supervisor);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Invalid Teams framework', $ex->getMessage());
        }

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 2',
            'tenantid' => $tenant1->id,
        ]);

        $this->assertEquals($tenantcontext1, uimode_teams::get_team_context($framework1, $supervisor));
    }

    public function test_can_create_team(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $managerole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $managerole, $syscontext);
        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $viewrole, $syscontext);

        role_assign($managerole, $user1->id, $syscontext);
        role_assign($viewrole, $user1->id, $syscontext);
        role_assign($managerole, $user2->id, $syscontext);
        role_assign($viewrole, $user3->id, $syscontext);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));

        $this->setUser($user2);
        $this->assertFalse(uimode_teams::can_create_team($framework1, $syscontext));

        $this->setUser($user3);
        $this->assertFalse(uimode_teams::can_create_team($framework1, $syscontext));

        role_assign($viewrole, $user2->id, $syscontext);
        role_assign($managerole, $user3->id, $syscontext);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        $framework1 = framework::update((object)['id' => $framework1->id, 'managecohortid' => $cohort1->id]);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));

        $this->setUser($user2);
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));

        $this->setUser($user3);
        $this->assertFalse(uimode_teams::can_create_team($framework1, $syscontext));

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

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));
        $this->assertTrue(uimode_teams::can_create_team($framework1, $tenantcontext1));
        $this->assertTrue(uimode_teams::can_create_team($framework1, $tenantcontext2));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));
        $this->assertTrue(uimode_teams::can_create_team($framework1, $tenantcontext1));
        $this->assertTrue(uimode_teams::can_create_team($framework1, $tenantcontext2));

        role_assign($managerole, $user4->id, $tenantcontext1);
        role_assign($viewrole, $user4->id, $tenantcontext1);
        $this->setUser($user4);
        $this->assertFalse(uimode_teams::can_create_team($framework1, $syscontext));
        $this->assertFalse(uimode_teams::can_create_team($framework1, $tenantcontext1));
        $this->assertFalse(uimode_teams::can_create_team($framework1, $tenantcontext2));

        $framework1 = framework::update((object)['id' => $framework1->id, 'managecohortid' => null]);
        $this->assertFalse(uimode_teams::can_create_team($framework1, $syscontext));
        $this->assertTrue(uimode_teams::can_create_team($framework1, $tenantcontext1));
        $this->assertFalse(uimode_teams::can_create_team($framework1, $tenantcontext2));

        $framework1 = framework::update((object)['id' => $framework1->id, 'alltenants' => 0, 'tenantids' => [$tenant1->id]]);
        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_create_team($framework1, $syscontext));
        $this->assertTrue(uimode_teams::can_create_team($framework1, $tenantcontext1));
        $this->assertFalse(uimode_teams::can_create_team($framework1, $tenantcontext2));
    }

    public function test_can_update_team(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $managerole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $managerole, $syscontext);
        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $viewrole, $syscontext);

        role_assign($managerole, $user1->id, $syscontext);
        role_assign($viewrole, $user1->id, $syscontext);
        role_assign($managerole, $user2->id, $syscontext);
        role_assign($viewrole, $user3->id, $syscontext);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'userid' => $user4->id,
            'subuserids' => [],
        ]);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));

        $this->setUser($user2);
        $this->assertFalse(uimode_teams::can_update_team($framework1, $supervisor));

        $this->setUser($user3);
        $this->assertFalse(uimode_teams::can_update_team($framework1, $supervisor));

        role_assign($viewrole, $user2->id, $syscontext);
        role_assign($managerole, $user3->id, $syscontext);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        $framework1 = framework::update((object)['id' => $framework1->id, 'managecohortid' => $cohort1->id]);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));

        $this->setUser($user2);
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));

        $this->setUser($user3);
        $this->assertFalse(uimode_teams::can_update_team($framework1, $supervisor));

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

        $supervisor1 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 2',
            'tenantid' => $tenant1->id,
        ]);
        $supervisor2 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'tenantid' => $tenant2->id,
        ]);
        $framework1 = framework::update((object)['id' => $framework1->id, 'managecohortid' => null]);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor1));
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor2));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor1));
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor2));

        role_assign($managerole, $user4->id, $tenantcontext1);
        role_assign($viewrole, $user4->id, $tenantcontext1);
        $this->setUser($user4);
        $this->assertFalse(uimode_teams::can_update_team($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor1));
        $this->assertFalse(uimode_teams::can_update_team($framework1, $supervisor2));

        $framework1 = framework::update((object)['id' => $framework1->id, 'alltenants' => 0, 'tenantids' => [$tenant1->id]]);
        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor1));
        $this->assertTrue(uimode_teams::can_update_team($framework1, $supervisor2));
    }

    public function test_can_manage_members(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $managerole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $managerole, $syscontext);
        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $viewrole, $syscontext);

        role_assign($managerole, $user1->id, $syscontext);
        role_assign($viewrole, $user1->id, $syscontext);
        role_assign($managerole, $user2->id, $syscontext);
        role_assign($viewrole, $user3->id, $syscontext);

        $supervisor = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'userid' => $user4->id,
            'subuserids' => [],
        ]);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));

        $this->setUser($user2);
        $this->assertFalse(uimode_teams::can_manage_members($framework1, $supervisor));

        $this->setUser($user3);
        $this->assertFalse(uimode_teams::can_manage_members($framework1, $supervisor));

        role_assign($viewrole, $user2->id, $syscontext);
        role_assign($managerole, $user3->id, $syscontext);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        $framework1 = framework::update((object)['id' => $framework1->id, 'managecohortid' => $cohort1->id]);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));

        $this->setUser($user2);
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));

        $this->setUser($user3);
        $this->assertFalse(uimode_teams::can_manage_members($framework1, $supervisor));

        $this->setUser($user4);
        $this->assertFalse(uimode_teams::can_manage_members($framework1, $supervisor));

        $supervisor = \tool_murelation\local\supervisor::update((object)['id' => $supervisor->id, 'supmanaged' => 1]);
        $this->assertFalse(uimode_teams::can_manage_members($framework1, $supervisor));

        role_assign($viewrole, $user4->id, $syscontext);
        $this->setUser($user4);
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));

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

        $supervisor1 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 2',
            'tenantid' => $tenant1->id,
        ]);
        $supervisor2 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'tenantid' => $tenant2->id,
        ]);
        $framework1 = framework::update((object)['id' => $framework1->id, 'managecohortid' => null]);

        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor1));
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor2));

        $this->setUser($user1);
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor1));
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor2));

        role_assign($managerole, $user4->id, $tenantcontext1);
        role_assign($viewrole, $user4->id, $tenantcontext1);
        $this->setUser($user4);
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor1));
        $this->assertFalse(uimode_teams::can_manage_members($framework1, $supervisor2));

        $framework1 = framework::update((object)['id' => $framework1->id, 'alltenants' => 0, 'tenantids' => [$tenant1->id]]);
        $this->setAdminUser();
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor));
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor1));
        $this->assertTrue(uimode_teams::can_manage_members($framework1, $supervisor2));
    }

    public function test_get_visible_teams(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user5->id, $course->id, 'editingteacher');

        $framework0 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_HIDDEN,
        ]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_MANAGERS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_SUPERVISORS,
        ]);
        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_SUBORDINATES,
        ]);
        $framework4 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_EVERYBODY,
        ]);

        $managerole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $managerole, $syscontext);
        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $viewrole, $syscontext);

        role_assign($managerole, $user6->id, $syscontext);
        role_assign($viewrole, $user7->id, $syscontext);

        $team0 = uimode_teams::team_create((object)[
            'frameworkid' => $framework0->id,
            'teamname' => 'Team 0',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $team1 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $team2 = uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 2',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $team3 = uimode_teams::team_create((object)[
            'frameworkid' => $framework3->id,
            'teamname' => 'Team 3',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $team4 = uimode_teams::team_create((object)[
            'frameworkid' => $framework4->id,
            'teamname' => 'Team 4',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);

        $this->setGuestUser();
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([], array_keys($result));

        $this->setAdminUser();
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));

        $this->setUser($user1);
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team2->id, $team3->id, $team4->id], array_keys($result));

        $this->setUser($user2);
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team4->id], array_keys($result));

        $this->setUser($user4);
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team4->id], array_keys($result));

        $this->setUser($user5);
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user1, $course);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2, $course);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3, $course);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));

        $this->setUser($user6);
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));

        $this->setUser($user7);
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $courset = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);
        $this->getDataGenerator()->enrol_user($user2->id, $courset->id, 'student');
        $this->getDataGenerator()->enrol_user($user8->id, $courset->id, 'editingteacher');

        $this->setUser($user8);
        $result = uimode_teams::get_visible_teams($user1);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user1, $courset);
        $this->assertEquals([], array_keys($result));
        $result = uimode_teams::get_visible_teams($user2, $courset);
        $this->assertEquals([$team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3, $courset);
        $this->assertEquals([$team4->id], array_keys($result));

        $user2 = \tool_mutenancy\local\user::allocate($user2->id, $tenant1->id);
        $this->setAdminUser();
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));

        $framework1 = framework::update((object)['id' => $framework1->id, 'alltenants' => 0, 'tenantids' => [$tenant1->id]]);
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));

        $framework1 = framework::update((object)['id' => $framework1->id, 'alltenants' => 0, 'tenantids' => [$tenant2->id]]);
        $result = uimode_teams::get_visible_teams($user2);
        $this->assertEquals([$team2->id, $team3->id, $team4->id], array_keys($result));
        $result = uimode_teams::get_visible_teams($user3);
        $this->assertEquals([$team1->id, $team2->id, $team3->id, $team4->id], array_keys($result));
    }

    public function test_get_supervised_teams(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework0 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_MANAGERS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_HIDDEN,
        ]);

        $supervisor0 = \tool_murelation\local\supervisor::create((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $team1 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $team2 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 2',
            'userid' => $user1->id,
            'subuserids' => [$user4->id],
        ]);
        $team3 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'userid' => $user3->id,
            'subuserids' => [$user1->id],
        ]);
        $team4 = uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 4',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);

        $this->setGuestUser();
        $result = uimode_teams::get_supervised_teams($user1);
        $this->assertSame([], $result);

        $this->setUser($user1);
        $result = uimode_teams::get_supervised_teams($user1);
        $this->assertEquals([$team1->id, $team2->id], array_keys($result));

        $this->setUser($user2);
        $result = uimode_teams::get_supervised_teams($user1);
        $this->assertEquals([], $result);

        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $viewrole, $syscontext);
        $this->setUser($user2);
        role_assign($viewrole, $user2->id, $syscontext);
        $result = uimode_teams::get_supervised_teams($user1);
        $this->assertEquals([$team1->id, $team2->id], array_keys($result));

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

        $usert1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usert2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $team5 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 5',
            'userid' => $usert1->id,
        ]);
        $team6 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 6',
            'userid' => $usert2->id,
        ]);

        role_assign($viewrole, $user3->id, $tenantcontext1);

        $this->setUser($user3);
        $result = uimode_teams::get_supervised_teams($usert1);
        $this->assertEquals([$team5->id], array_keys($result));
        $result = uimode_teams::get_supervised_teams($usert2);
        $this->assertEquals([], array_keys($result));
    }

    public function test_cron_cleanup(): void {
        global $DB;

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $framework0 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);

        uimode_teams::cron_cleanup();

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $team0 = uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 0',
            'userid' => $user0->id,
            'subuserids' => [$user0->id, $user1->id, $user2->id],
        ]);
        $team1 = uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 0',
            'userid' => $user1->id,
            'subuserids' => [$user0->id, $user1->id, $user2->id],
        ]);
        $team2 = uimode_teams::team_create((object)[
            'frameworkid' => $framework3->id,
            'teamname' => 'Team 0',
            'userid' => $user2->id,
            'subuserids' => [$user0->id, $user1->id, $user2->id],
        ]);

        $oldteams = $DB->get_records('tool_murelation_supervisor', [], 'id ASC');
        $this->assertCount(3, $oldteams);
        uimode_teams::cron_cleanup();
        $teams = $DB->get_records('tool_murelation_supervisor', [], 'id ASC');
        $this->assertEquals($oldteams, $teams);

        $DB->set_field('tool_murelation_supervisor', 'tenantid', $tenant1->id, ['id' => $team1->id]);

        uimode_teams::cron_cleanup();
        $teams = $DB->get_records('tool_murelation_supervisor', [], 'id ASC');
        $this->assertNotEquals($oldteams, $teams);

        $this->assertSame($user0->id, $teams[$team0->id]->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $team0->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(3, $subordinates);
        $this->assertSame($user0->id, $subordinates[0]->userid);
        $this->assertSame($user1->id, $subordinates[1]->userid);
        $this->assertSame($user2->id, $subordinates[2]->userid);

        $this->assertSame($user1->id, $teams[$team1->id]->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $team1->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(2, $subordinates);
        $this->assertSame($user0->id, $subordinates[0]->userid);
        $this->assertSame($user1->id, $subordinates[1]->userid);

        $DB->set_field('tool_murelation_supervisor', 'tenantid', $tenant2->id, ['id' => $team1->id]);

        uimode_teams::cron_cleanup();
        $teams = $DB->get_records('tool_murelation_supervisor', [], 'id ASC');

        $this->assertSame($user0->id, $teams[$team0->id]->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $team0->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(3, $subordinates);
        $this->assertSame($user0->id, $subordinates[0]->userid);
        $this->assertSame($user1->id, $subordinates[1]->userid);
        $this->assertSame($user2->id, $subordinates[2]->userid);

        $this->assertSame(null, $teams[$team1->id]->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $team1->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(1, $subordinates);
        $this->assertSame($user0->id, $subordinates[0]->userid);
    }
}
