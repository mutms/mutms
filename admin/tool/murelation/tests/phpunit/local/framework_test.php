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

use tool_murelation\local\framework;
use tool_mulib\local\mulib;

/**
 * User relation framework tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\local\framework
 */
final class framework_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::create
     */
    public function test_create(): void {
        global $DB;

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $role1 = $this->getDataGenerator()->create_role();

        $this->setCurrentTimeStart();
        $framework1 = framework::create((object)[
            'uimode' => framework::UIMODE_SUPERVISORS,
            'name' => 'Tridy',
            'visibility' => framework::VISIBILITY_SUPERVISORS,
            'alltenants' => 1,
            'supervisortitle' => 'Ucitel',
            'supervisorstitle' => 'Ucitele',
            'subordinatetitle' => 'Zak',
            'subordinatestitle' => 'Zaci',
        ]);
        $this->assertSame('Tridy', $framework1->name);
        $this->assertSame(null, $framework1->idnumber);
        $this->assertSame('', $framework1->description);
        $this->assertSame(FORMAT_HTML, $framework1->descriptionformat);
        $this->assertSame('1', $framework1->uimode);
        $this->assertSame((string)framework::VISIBILITY_SUPERVISORS, $framework1->visibility);
        $this->assertSame('1', $framework1->alltenants);
        $this->assertSame(null, $framework1->managecohortid);
        $this->assertSame('Ucitel', $framework1->supervisortitle);
        $this->assertSame('Ucitele', $framework1->supervisorstitle);
        $this->assertSame(null, $framework1->supervisorcohortid);
        $this->assertSame(null, $framework1->supervisorroleid);
        $this->assertSame('Zak', $framework1->subordinatetitle);
        $this->assertSame('Zaci', $framework1->subordinatestitle);
        $this->assertSame(null, $framework1->subordinatecohortid);
        $this->assertTimeCurrent($framework1->timecreated);

        $this->setCurrentTimeStart();
        $framework2 = framework::create((object)[
            'uimode' => framework::UIMODE_TEAMS,
            'name' => 'Projects',
            'idnumber' => 'id2',
            'description' => 'Some desc',
            'visibility' => framework::VISIBILITY_HIDDEN,
            'alltenants' => 0,
            'managecohortid' => $cohort1->id,
            'supervisortitle' => 'Team leader',
            'supervisorstitle' => 'Team leaders',
            'supervisorcohortid' => $cohort2->id,
            'supervisorroleid' => $role1,
            'subordinatetitle' => 'Team member',
            'subordinatestitle' => 'Team members',
            'subordinatecohortid' => $cohort3->id,
        ]);
        $this->assertSame('Projects', $framework2->name);
        $this->assertSame('id2', $framework2->idnumber);
        $this->assertSame('Some desc', $framework2->description);
        $this->assertSame(FORMAT_HTML, $framework2->descriptionformat);
        $this->assertSame('2', $framework2->uimode);
        $this->assertSame((string)framework::VISIBILITY_HIDDEN, $framework2->visibility);
        $this->assertSame('1', $framework2->alltenants); // Ignored if tenants not active.
        $this->assertSame($cohort1->id, $framework2->managecohortid);
        $this->assertSame('Team leader', $framework2->supervisortitle);
        $this->assertSame('Team leaders', $framework2->supervisorstitle);
        $this->assertSame($cohort2->id, $framework2->supervisorcohortid);
        $this->assertSame((string)$role1, $framework2->supervisorroleid);
        $this->assertSame('Team member', $framework2->subordinatetitle);
        $this->assertSame('Team members', $framework2->subordinatestitle);
        $this->assertSame($cohort3->id, $framework2->subordinatecohortid);
        $this->assertTimeCurrent($framework2->timecreated);

        try {
            framework::create((object)[
                'uimode' => framework::UIMODE_SUPERVISORS,
                'name' => 'Tridy 2',
                'idnumber' => 'id2',
                'visibility' => framework::VISIBILITY_SUPERVISORS,
                'alltenants' => 1,
                'supervisortitle' => 'Ucitel',
                'supervisorstitle' => 'Ucitele',
                'subordinatetitle' => 'Zak',
                'subordinatestitle' => 'Zaci',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (frameword idnumber must be unique)', $ex->getMessage());
        }

        try {
            framework::create((object)[
                'uimode' => '888',
                'name' => 'Tridy',
                'visibility' => framework::VISIBILITY_SUPERVISORS,
                'alltenants' => 1,
                'supervisortitle' => 'Ucitel',
                'supervisorstitle' => 'Ucitele',
                'subordinatetitle' => 'Zak',
                'subordinatestitle' => 'Zaci',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (invalid uimode)', $ex->getMessage());
        }

        try {
            framework::create((object)[
                'uimode' => framework::UIMODE_TEAMS,
                'name' => 'Tridy',
                'visibility' => '888',
                'alltenants' => 1,
                'supervisortitle' => 'Ucitel',
                'supervisorstitle' => 'Ucitele',
                'subordinatetitle' => 'Zak',
                'subordinatestitle' => 'Zaci',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Invalid visibility option: 888)', $ex->getMessage());
        }

        if (!mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant();

        $framework3 = framework::create((object)[
            'uimode' => framework::UIMODE_TEAMS,
            'name' => 'Tridy',
            'visibility' => framework::VISIBILITY_EVERYBODY,
            'alltenants' => 0,
            'tenantids' => [$tenant1->id, $tenant2->id],
            'supervisortitle' => 'Ucitel',
            'supervisorstitle' => 'Ucitele',
            'subordinatetitle' => 'Zak',
            'subordinatestitle' => 'Zaci',
        ]);
        $this->assertSame((string)framework::VISIBILITY_EVERYBODY, $framework3->visibility);
        $this->assertSame('0', $framework3->alltenants);
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant3->id]));

        $framework4 = framework::create((object)[
            'uimode' => framework::UIMODE_TEAMS,
            'name' => 'Tridy',
            'visibility' => framework::VISIBILITY_MANAGERS,
            'alltenants' => 1,
            'tenantids' => [$tenant1->id, $tenant2->id],
            'supervisortitle' => 'Ucitel',
            'supervisorstitle' => 'Ucitele',
            'subordinatetitle' => 'Zak',
            'subordinatestitle' => 'Zaci',
        ]);
        $this->assertSame((string)framework::VISIBILITY_MANAGERS, $framework4->visibility);
        $this->assertSame('1', $framework4->alltenants);
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework4->id, 'tenantid' => $tenant1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework4->id, 'tenantid' => $tenant2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework4->id, 'tenantid' => $tenant3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant3->id]));
    }

    /**
     * @covers ::update
     */
    public function test_update(): void {
        global $DB;

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $cohort4 = $this->getDataGenerator()->create_cohort();
        $cohort5 = $this->getDataGenerator()->create_cohort();
        $cohort6 = $this->getDataGenerator()->create_cohort();
        $role1 = $this->getDataGenerator()->create_role();
        $role2 = $this->getDataGenerator()->create_role();

        $framework1 = framework::create((object)[
            'uimode' => framework::UIMODE_SUPERVISORS,
            'name' => 'Tridy',
            'visibility' => framework::VISIBILITY_SUPERVISORS,
            'alltenants' => 1,
            'supervisortitle' => 'Ucitel',
            'supervisorstitle' => 'Ucitele',
            'subordinatetitle' => 'Zak',
            'subordinatestitle' => 'Zaci',
        ]);

        $framework2 = framework::update((object)[
            'id' => $framework1->id,
            'uimode' => framework::UIMODE_TEAMS,
            'name' => 'Projects',
            'idnumber' => 'id2',
            'description' => 'Some desc',
            'visibility' => framework::VISIBILITY_HIDDEN,
            'alltenants' => 0,
            'managecohortid' => $cohort1->id,
            'supervisortitle' => 'Team leader',
            'supervisorstitle' => 'Team leaders',
            'supervisorcohortid' => $cohort2->id,
            'supervisorroleid' => $role1,
            'subordinatetitle' => 'Team member',
            'subordinatestitle' => 'Team members',
            'subordinatecohortid' => $cohort3->id,
        ]);
        $this->assertSame('Projects', $framework2->name);
        $this->assertSame('id2', $framework2->idnumber);
        $this->assertSame('Some desc', $framework2->description);
        $this->assertSame(FORMAT_HTML, $framework2->descriptionformat);
        $this->assertSame($framework1->uimode, $framework2->uimode);
        $this->assertSame((string)framework::VISIBILITY_HIDDEN, $framework2->visibility);
        $this->assertSame('1', $framework2->alltenants);
        $this->assertSame($cohort1->id, $framework2->managecohortid);
        $this->assertSame('Team leader', $framework2->supervisortitle);
        $this->assertSame('Team leaders', $framework2->supervisorstitle);
        $this->assertSame($cohort2->id, $framework2->supervisorcohortid);
        $this->assertSame((string)$role1, $framework2->supervisorroleid);
        $this->assertSame('Team member', $framework2->subordinatetitle);
        $this->assertSame('Team members', $framework2->subordinatestitle);
        $this->assertSame($cohort3->id, $framework2->subordinatecohortid);
        $this->assertSame($framework1->timecreated, $framework2->timecreated);

        $framework3 = framework::update((object)[
            'id' => $framework1->id,
            'name' => 'Tridy',
            'idnumber' => '',
            'description' => '',
            'visibility' => framework::VISIBILITY_SUPERVISORS,
            'alltenants' => 1,
            'supervisortitle' => 'Ucitel',
            'supervisorstitle' => 'Ucitele',
            'subordinatetitle' => 'Zak',
            'subordinatestitle' => 'Zaci',
            'managecohortid' => '',
            'supervisorcohortid' => 0,
            'supervisorroleid' => null,
            'subordinatecohortid' => '0',
        ]);
        $this->assertSame('Tridy', $framework3->name);
        $this->assertSame(null, $framework3->idnumber);
        $this->assertSame('', $framework3->description);
        $this->assertSame(FORMAT_HTML, $framework3->descriptionformat);
        $this->assertSame('1', $framework3->uimode);
        $this->assertSame((string)framework::VISIBILITY_SUPERVISORS, $framework3->visibility);
        $this->assertSame('1', $framework3->alltenants);
        $this->assertSame(null, $framework3->managecohortid);
        $this->assertSame('Ucitel', $framework3->supervisortitle);
        $this->assertSame('Ucitele', $framework3->supervisorstitle);
        $this->assertSame(null, $framework3->supervisorcohortid);
        $this->assertSame(null, $framework3->supervisorroleid);
        $this->assertSame('Zak', $framework3->subordinatetitle);
        $this->assertSame('Zaci', $framework3->subordinatestitle);
        $this->assertSame(null, $framework3->subordinatecohortid);

        if (!mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant();

        $framework3 = framework::update((object)[
            'id' => $framework3->id,
            'alltenants' => 0,
            'tenantids' => [$tenant1->id, $tenant2->id],
        ]);
        $this->assertSame('0', $framework3->alltenants);
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant3->id]));

        $framework3 = framework::update((object)[
            'id' => $framework3->id,
            'alltenants' => 0,
            'tenantids' => [$tenant2->id, $tenant3->id],
        ]);
        $this->assertSame('0', $framework3->alltenants);
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant3->id]));
    }

    /**
     * @covers ::is_deletable
     */
    public function test_is_deletable(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'visibility' => framework::VISIBILITY_MANAGERS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_MANAGERS,
        ]);
        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_EVERYBODY,
        ]);
        $framework4 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'visibility' => framework::VISIBILITY_HIDDEN,
        ]);
        $supervisor3 = $generator->create_team(['frameworkid' => $framework3->id]);
        $supervisor4 = $generator->create_team(['frameworkid' => $framework4->id]);

        $this->assertTrue(framework::is_deletable($framework1->id));
        $this->assertTrue(framework::is_deletable($framework2->id));
        $this->assertFalse(framework::is_deletable($framework3->id));
        $this->assertTrue(framework::is_deletable($framework4->id));
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);

        $team1 = $generator->create_team(['frameworkid' => $framework1->id, 'userid' => $user1->id]);
        $member1 = $generator->create_team_member(['supervisorid' => $team1->id, 'userid' => $user2->id]);
        $supervisor1 = $generator->create_supervisor(['frameworkid' => $framework2->id, 'userid' => $user1->id, 'subuserid' => $user2->id]);
        $subordinate1 = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $this->assertTrue($DB->record_exists('tool_murelation_framework', ['id' => $framework1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['id' => $member1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_framework', ['id' => $framework2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['id' => $subordinate1->id]));

        framework::delete($framework1->id);
        $this->assertFalse($DB->record_exists('tool_murelation_framework', ['id' => $framework1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['id' => $member1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_framework', ['id' => $framework2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['id' => $subordinate1->id]));

        framework::delete($framework2->id);
        $this->assertFalse($DB->record_exists('tool_murelation_framework', ['id' => $framework1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['id' => $member1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_framework', ['id' => $framework2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['id' => $subordinate1->id]));

        if (!mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant();

        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'alltenants' => 0,
            'tenantids' => [$tenant1->id, $tenant2->id],
        ]);
        $framework4 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'alltenants' => 0,
            'tenantids' => [$tenant2->id],
        ]);

        $team1 = $generator->create_team(['frameworkid' => $framework3->id, 'userid' => $user1->id]);
        $member1 = $generator->create_team_member(['supervisorid' => $team1->id, 'userid' => $user2->id]);

        $this->assertTrue($DB->record_exists('tool_murelation_framework', ['id' => $framework3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['id' => $member1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework4->id, 'tenantid' => $tenant2->id]));

        framework::delete($framework3->id);

        $this->assertFalse($DB->record_exists('tool_murelation_framework', ['id' => $framework3->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['id' => $member1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework3->id, 'tenantid' => $tenant2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_tenant_allow', ['frameworkid' => $framework4->id, 'tenantid' => $tenant2->id]));
    }

    /**
     * @covers ::get_uimodes
     */
    public function test_get_uimodes(): void {
        $uimodes = framework::get_uimodes();
        $expected = [
            1 => 'Supervisors',
            2 => 'Teams',
        ];
        $this->assertSame($expected, $uimodes);
    }

    /**
     * @covers ::get_description_editor_options
     */
    public function test_get_description_editor_options(): void {
        $options = framework::get_description_editor_options();
        $this->assertSame(0, $options['maxfiles']);
        $this->assertSame(CONTEXT_SYSTEM, $options['context']->contextlevel);
    }

    /**
     * @covers ::get_visibility_options
     */
    public function test_get_visibility_options(): void {
        $expected = [
            0 => 'Hidden',
            1 => 'Position managers',
            2 => 'Position managers, supervisors and course teachers',
            3 => 'Position managers, supervisors, course teachers and subordinates',
            4 => 'Everybody',
        ];
        $this->assertSame($expected, framework::get_visibility_options());
    }

    /**
     * @covers ::get_allowed_supervisor_roles
     */
    public function test_get_allowed_supervisor_roles(): void {
        global $DB;

        $DB->delete_records('role_context_levels', []);
        $this->assertSame([], framework::get_allowed_supervisor_roles(0));

        $role1 = $DB->get_record('role', ['id' => $this->getDataGenerator()->create_role()], '*', MUST_EXIST);
        $role2 = $DB->get_record('role', ['id' => $this->getDataGenerator()->create_role()], '*', MUST_EXIST);
        $role3 = $DB->get_record('role', ['id' => $this->getDataGenerator()->create_role()], '*', MUST_EXIST);
        $this->assertSame([], framework::get_allowed_supervisor_roles(0));

        $DB->delete_records('role_context_levels', []);
        set_role_contextlevels($role1->id, [CONTEXT_USER]);
        set_role_contextlevels($role2->id, [CONTEXT_USER]);
        $this->assertSame([], framework::get_allowed_supervisor_roles(0));

        set_config('roles', "$role1->id", 'tool_murelation');
        $this->assertSame([$role1->id => $role1->name], framework::get_allowed_supervisor_roles(0));

        set_config('roles', "$role1->id,$role2->id,$role3->id", 'tool_murelation');
        $this->assertSame([$role1->id => $role1->name, $role2->id => $role2->name], framework::get_allowed_supervisor_roles(0));

        set_config('roles', "$role2->id,$role3->id", 'tool_murelation');
        $this->assertSame([$role2->id => $role2->name, $role3->id => $role3->name], framework::get_allowed_supervisor_roles($role3->id));

        set_config('roles', "$role2->id", 'tool_murelation');
        $this->assertSame([$role2->id => $role2->name, $role3->id => $role3->name], framework::get_allowed_supervisor_roles($role3->id));

        set_config('roles', "$role2->id", 'tool_murelation');
        $this->assertSame([$role2->id => $role2->name, 66666 => 'Error'], framework::get_allowed_supervisor_roles(66666));
    }
}
