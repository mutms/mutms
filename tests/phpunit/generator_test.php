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

namespace tool_murelation\phpunit;

use tool_murelation\local\framework;

/**
 * Supervisors and teams generator tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_murelation_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::create_supervisor_role
     */
    public function test_create_supervisor_role(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');
        $this->assertInstanceOf(\tool_murelation_generator::class, $generator);

        $roleid = $generator->create_supervisor_role(['name' => 'Some name', 'shortname' => 'somename']);
        $allowed = framework::get_allowed_supervisor_roles(null);
        $this->assertArrayHasKey($roleid, $allowed);
    }

    /**
     * @covers ::create_framework
     */
    public function test_create_framework(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');
        $this->assertInstanceOf(\tool_murelation_generator::class, $generator);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $role1 = $this->getDataGenerator()->create_role();

        $this->setCurrentTimeStart();
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $this->assertSame('Framework 1', $framework1->name);
        $this->assertSame(null, $framework1->idnumber);
        $this->assertSame('', $framework1->description);
        $this->assertSame(FORMAT_HTML, $framework1->descriptionformat);
        $this->assertSame('1', $framework1->uimode);
        $this->assertSame((string)framework::VISIBILITY_SUBORDINATES, $framework1->visibility);
        $this->assertSame('1', $framework1->alltenants);
        $this->assertSame(null, $framework1->managecohortid);
        $this->assertSame('Supervisor', $framework1->supervisortitle);
        $this->assertSame('Supervisors', $framework1->supervisorstitle);
        $this->assertSame(null, $framework1->supervisorcohortid);
        $this->assertSame(null, $framework1->supervisorroleid);
        $this->assertSame('Subordinate', $framework1->subordinatetitle);
        $this->assertSame('Subordinates', $framework1->subordinatestitle);
        $this->assertSame(null, $framework1->subordinatecohortid);
        $this->assertTimeCurrent($framework1->timecreated);

        $this->setCurrentTimeStart();
        $framework2 = $generator->create_framework([
            'uimode' => 'supervisors',
            'name' => 'Tridy',
            'idnumber' => 'id2',
            'description' => 'Some desc',
            'visibility' => framework::VISIBILITY_SUPERVISORS,
            'alltenants' => 0,
            'managecohortid' => $cohort1->id,
            'supervisortitle' => 'Ucitel',
            'supervisorstitle' => 'Ucitele',
            'supervisorcohortid' => $cohort2->id,
            'supervisorroleid' => $role1,
            'subordinatetitle' => 'Zak',
            'subordinatestitle' => 'Zaci',
            'subordinatecohortid' => $cohort3->id,
        ]);
        $this->assertSame('Tridy', $framework2->name);
        $this->assertSame('id2', $framework2->idnumber);
        $this->assertSame('Some desc', $framework2->description);
        $this->assertSame(FORMAT_HTML, $framework2->descriptionformat);
        $this->assertSame('1', $framework2->uimode);
        $this->assertSame((string)framework::VISIBILITY_SUPERVISORS, $framework2->visibility);
        $this->assertSame('1', $framework2->alltenants); // Ignored if tenants not active.
        $this->assertSame($cohort1->id, $framework2->managecohortid);
        $this->assertSame('Ucitel', $framework2->supervisortitle);
        $this->assertSame('Ucitele', $framework2->supervisorstitle);
        $this->assertSame($cohort2->id, $framework2->supervisorcohortid);
        $this->assertSame((string)$role1, $framework2->supervisorroleid);
        $this->assertSame('Zak', $framework2->subordinatetitle);
        $this->assertSame('Zaci', $framework2->subordinatestitle);
        $this->assertSame($cohort3->id, $framework2->subordinatecohortid);
        $this->assertTimeCurrent($framework2->timecreated);

        $framework3 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $this->assertSame('2', $framework3->uimode);

        $framework4 = $generator->create_framework([
            'uimode' => 'teams',
        ]);
        $this->assertSame('2', $framework4->uimode);
    }

    /**
     * @covers ::create_supervisor
     */
    public function test_create_supervisor(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);

        $this->setCurrentTimeStart();
        $supervisor = $generator->create_supervisor([
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $subordinate = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor->id, 'userid' => $user2->id]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
    }

    /**
     * @covers ::create_team
     */
    public function test_create_team(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $this->setCurrentTimeStart();
        $supervisor = $generator->create_team([
            'frameworkid' => $framework1->id,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame(null, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Team name 1', $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $this->assertSame(null, $supervisor->teamcohortid);

        $this->setCurrentTimeStart();
        $supervisor = $generator->create_team([
            'frameworkid' => $framework1->id,
            'teamname' => 'Some team',
            'teamidnumber' => 'T1',
            'userid' => $user1->id,
            'supmanaged' => 1,
            'maxsubordinates' => 66,
            'teamcohortcreate' => 1,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('Some team', $supervisor->teamname);
        $this->assertSame('T1', $supervisor->teamidnumber);
        $this->assertSame('1', $supervisor->supmanaged);
        $this->assertSame('66', $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $teamcohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $teamcohort->component);
    }

    /**
     * @covers ::create_member
     */
    public function test_team_create_member(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor = $generator->create_team([
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
        ]);

        $this->setCurrentTimeStart();
        $subordinate = $generator->create_team_member(['supervisorid' => $supervisor->id, 'userid' => $user2->id]);
        $this->assertSame($supervisor->id, $subordinate->supervisorid);
        $this->assertSame($user2->id, $subordinate->userid);
        $this->assertSame($framework1->id, $subordinate->frameworkid);
        $this->assertTimeCurrent($subordinate->timecreated);

        $supervisor = $generator->create_team([
            'frameworkid' => $framework1->id,
            'teamname' => 'mu team 2',
        ]);
        $subordinate = $generator->create_team_member(['teamname' => $supervisor->teamname, 'userid' => $user3->id]);
        $this->assertSame($supervisor->id, $subordinate->supervisorid);
        $this->assertSame($user3->id, $subordinate->userid);
        $this->assertSame($framework1->id, $subordinate->frameworkid);
    }
}
