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

use tool_murelation\local\supervisor;
use tool_mulib\local\mulib;
use tool_murelation\local\framework;
use core\exception\coding_exception;
use core\exception\invalid_parameter_exception;

/**
 * Supervisor class tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\local\supervisor
 */
final class supervisor_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create_supervisors(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);

        $this->setCurrentTimeStart();
        $supervisor = supervisor::create((object)[
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
        $this->assertSame($framework1->id, $subordinate->frameworkid);
        $this->assertSame(null, $subordinate->teamposition);
        $this->assertTimeCurrent($subordinate->timecreated);

        $this->setCurrentTimeStart();
        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user3->id,
            'teamname' => 'abc',
            'teamidnumber' => 'def',
            'maxsubordinates' => 10,
            'supmanaged' => 1,
            'teamcohortcreate' => 1,
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
        $subordinate = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor->id, 'userid' => $user3->id], '*', MUST_EXIST);
        $this->assertSame($framework1->id, $subordinate->frameworkid);
        $this->assertSame(null, $subordinate->teamposition);
        $this->assertTimeCurrent($subordinate->timecreated);

        try {
            supervisor::create((object)[
                'frameworkid' => $framework1->id,
                'userid' => null,
                'subuserid' => $user4->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (supervisor userid is required)', $ex->getMessage());
        }

        try {
            supervisor::create((object)[
                'frameworkid' => $framework1->id,
                'userid' => $user1->id,
                'subuserid' => null,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (subordinate subuserid is required)', $ex->getMessage());
        }

        try {
            supervisor::create((object)[
                'frameworkid' => $framework1->id,
                'userid' => $user1->id,
                'subuserid' => $user1->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (supervisor cannot be own subordinate in supervisors mode)', $ex->getMessage());
        }

        try {
            supervisor::create((object)[
                'frameworkid' => $framework1->id,
                'userid' => $user2->id,
                'subuserid' => $user3->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (subordinate already has other supervisor)', $ex->getMessage());
        }

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

        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $usert1->id,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame($tenant1->id, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $subordinate = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor->id, 'userid' => $usert1->id], '*', MUST_EXIST);
        $this->assertSame($framework1->id, $subordinate->frameworkid);
        $this->assertSame(null, $subordinate->teamposition);
    }

    public function test_create_teams(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $this->setCurrentTimeStart();
        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame(null, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('team 1', $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $this->assertCount(0, $subordinates);

        $this->setCurrentTimeStart();
        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserids' => [$user1->id, $user3->id],
            'teamname' => 'team 2',
            'teamidnumber' => 'def',
            'maxsubordinates' => 10,
            'supmanaged' => 1,
            'teamcohortcreate' => 1,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('team 2', $supervisor->teamname);
        $this->assertSame('def', $supervisor->teamidnumber);
        $this->assertSame('1', $supervisor->supmanaged);
        $this->assertSame('10', $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('team 2', $cohort->name);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(2, $subordinates);
        $this->assertSame($framework1->id, $subordinates[0]->frameworkid);
        $this->assertSame($user1->id, $subordinates[0]->userid);
        $this->assertSame(null, $subordinates[0]->teamposition);
        $this->assertTimeCurrent($subordinates[0]->timecreated);
        $this->assertSame($framework1->id, $subordinates[1]->frameworkid);
        $this->assertSame($user3->id, $subordinates[1]->userid);
        $this->assertSame(null, $subordinates[1]->teamposition);
        $this->assertTimeCurrent($subordinates[1]->timecreated);

        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => null,
            'subuserids' => [$user2->id],
            'teamname' => 'team 3',
            'teamposition' => 'some position',
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame(null, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('team 3', $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(1, $subordinates);
        $this->assertSame($framework1->id, $subordinates[0]->frameworkid);
        $this->assertSame($user2->id, $subordinates[0]->userid);
        $this->assertSame('some position', $subordinates[0]->teamposition);
        $this->assertTimeCurrent($subordinates[0]->timecreated);

        try {
            supervisor::create((object)[
                'frameworkid' => $framework1->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (teamname is required)', $ex->getMessage());
        }

        try {
            supervisor::create((object)[
                'frameworkid' => $framework1->id,
                'userid' => null,
                'subuserids' => [$user2->id],
                'teamname' => 'team X',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (subordinate already has other supervisor)', $ex->getMessage());
        }

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant();
        $tenantcatcontext3 = \context_coursecat::instance($tenant3->categoryid);

        $usert1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usert2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'tenantid' => $tenant3->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$usert1->id, $usert2->id],
            'teamcohortcreate' => 1,
            'teamcohortname' => 'mega tenant cohort',
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame($tenant3->id, $supervisor->tenantid);
        $this->assertSame('team 1', $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('mega tenant cohort', $cohort->name);
        $this->assertSame((string)$tenantcatcontext3->id, $cohort->contextid);
        $this->assertCount(2, $DB->get_records('cohort_members', ['cohortid' => $supervisor->teamcohortid]));
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(2, $subordinates);
    }

    public function test_update_supervisors(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);

        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);

        $supervisor = supervisor::update((object)[
            'id' => $supervisor->id,
            'userid' => $user3->id,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user3->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(1, $subordinates);
        $this->assertSame($user2->id, $subordinates[0]->userid);

        $supervisor = supervisor::update((object)[
            'id' => $supervisor->id,
            'tenantid' => 11,
            'userid' => $user3->id,
            'teamname' => 'team 2',
            'teamidnumber' => 'def',
            'maxsubordinates' => 10,
            'supmanaged' => 1,
            'teamposition' => 'xxx',
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user3->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
        $this->assertTimeCurrent($supervisor->timecreated);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $subordinates = array_values($subordinates);
        $this->assertCount(1, $subordinates);
        $this->assertSame($user2->id, $subordinates[0]->userid);

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

        $supervisor = supervisor::update((object)[
            'id' => $supervisor->id,
            'userid' => $usert1->id,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($usert1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);

        // Illegal direct modification test.
        $DB->set_field('user', 'tenantid', $tenant2->id, ['id' => $user2->id]);
        $supervisor = supervisor::update((object)[
            'id' => $supervisor->id,
            'userid' => $usert1->id,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($usert1->id, $supervisor->userid);
        $this->assertSame($tenant2->id, $supervisor->tenantid);
        $this->assertSame(null, $supervisor->teamname);
        $this->assertSame(null, $supervisor->teamidnumber);
        $this->assertSame(null, $supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame('1', $supervisor->maxsubordinates);
    }

    public function test_update_teams(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'subuserids' => [$user2->id],
        ]);

        $supervisor = supervisor::update((object)[
            'id' => $supervisor->id,
            'userid' => $user1->id,
            'teamname' => 'team 2',
            'teamidnumber' => 'def',
            'maxsubordinates' => 10,
            'supmanaged' => 1,
            'teamcohortcreate' => 1,
            'teamcohortname' => 'team cohort',
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame($user1->id, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('team 2', $supervisor->teamname);
        $this->assertSame('def', $supervisor->teamidnumber);
        $this->assertSame('1', $supervisor->supmanaged);
        $this->assertSame('10', $supervisor->maxsubordinates);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('team cohort', $cohort->name);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertCount(1, $DB->get_records('cohort_members', ['cohortid' => $supervisor->teamcohortid]));
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $this->assertCount(1, $subordinates);

        $supervisor = supervisor::update((object)[
            'id' => $supervisor->id,
            'userid' => '',
            'teamname' => 'team 1',
            'teamidnumber' => 'abc',
            'maxsubordinates' => '',
            'supmanaged' => 0,
            'teamcohortname' => 'not so great team',
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame(null, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('team 1', $supervisor->teamname);
        $this->assertSame('abc', $supervisor->teamidnumber);
        $this->assertNotNull($supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
        $cohort = $DB->get_record('cohort', ['id' => $supervisor->teamcohortid], '*', MUST_EXIST);
        $this->assertSame('tool_murelation', $cohort->component);
        $this->assertSame('not so great team', $cohort->name);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id], 'userid ASC');
        $this->assertCount(1, $subordinates);

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();

        $supervisor = supervisor::update((object)[
            'id' => $supervisor->id,
            'tenantid' => $tenant1->id,
        ]);
        $this->assertSame($framework1->id, $supervisor->frameworkid);
        $this->assertSame(null, $supervisor->userid);
        $this->assertSame(null, $supervisor->tenantid);
        $this->assertSame('team 1', $supervisor->teamname);
        $this->assertSame('abc', $supervisor->teamidnumber);
        $this->assertNotNull($supervisor->teamcohortid);
        $this->assertSame('0', $supervisor->supmanaged);
        $this->assertSame(null, $supervisor->maxsubordinates);
    }

    public function test_vacate(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);
        $framework2 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);

        $supervisor1 = supervisor::vacate($supervisor1->id);
        $this->assertSame($framework1->id, $supervisor1->frameworkid);
        $this->assertSame(null, $supervisor1->userid);
        $this->assertSame(null, $supervisor1->tenantid);
        $this->assertSame('team 1', $supervisor1->teamname);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id], 'id ASC');
        $this->assertCount(2, $subordinates);

        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);

        try {
            supervisor::vacate($supervisor2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: supervisor must be assigned in supervisors mode', $ex->getMessage());
        }
    }

    public function test_delete(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $roleid1 = $this->getDataGenerator()->create_role();
        $roleid2 = $this->getDataGenerator()->create_role();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'supervisorroleid' => $roleid1,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'supervisorroleid' => $roleid2,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $supervisor1b = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user4->id, $user1->id],
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

        supervisor::delete($supervisor1->id);
        supervisor::delete($supervisor2->id);
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1b->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2b->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2b->id]));
        $this->assertCount(2, $DB->get_records('role_assignments', ['roleid' => $roleid1]));
        $this->assertCount(1, $DB->get_records('role_assignments', ['roleid' => $roleid2]));
    }

    public function test_user_deleted(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $roleid1 = $this->getDataGenerator()->create_role();
        $roleid2 = $this->getDataGenerator()->create_role();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'supervisorroleid' => $roleid1,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'supervisorroleid' => $roleid2,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $supervisor1b = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user4->id, $user1->id],
        ]);
        $supervisor1c = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user3->id,
            'subuserids' => [$user6->id],
        ]);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2b = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user5->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor2c = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user2->id,
            'subuserid' => $user3->id,
        ]);

        delete_user($user1);

        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor1->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id], 'id ASC');
        $this->assertCount(2, $subordinates);

        $supervisor1b = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1b->id], '*', MUST_EXIST);
        $this->assertSame(null, $supervisor1b->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id, 'userid' => $user4->id], 'id ASC');
        $this->assertCount(1, $subordinates);

        $supervisor1c = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1c->id], '*', MUST_EXIST);
        $this->assertSame($user3->id, $supervisor1c->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor1c->id, 'userid' => $user6->id], 'id ASC');
        $this->assertCount(1, $subordinates);

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2b->id]));

        $supervisor2c = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2c->id], '*', MUST_EXIST);
        $this->assertSame($user2->id, $supervisor2c->userid);
        $subordinates = $DB->get_records('tool_murelation_subordinate', ['supervisorid' => $supervisor2c->id, 'userid' => $user3->id], 'id ASC');
        $this->assertCount(1, $subordinates);
    }

    public function test_sync_roles(): void {
        global $DB;

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $roleid1 = $this->getDataGenerator()->create_role();
        $roleid2 = $this->getDataGenerator()->create_role();
        $roleid3 = $this->getDataGenerator()->create_role();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $usercontext0 = \context_user::instance($user0->id);
        $usercontext1 = \context_user::instance($user1->id);
        $usercontext2 = \context_user::instance($user2->id);
        $usercontext3 = \context_user::instance($user3->id);

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'supervisorroleid' => $roleid1,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'supervisorroleid' => $roleid2,
        ]);

        $supervisor1 = $generator->create_supervisor([
            'frameworkid' => $framework1->id,
            'userid' => $user0->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor2 = $generator->create_supervisor([
            'frameworkid' => $framework1->id,
            'userid' => $user0->id,
            'subuserid' => $user2->id,
        ]);
        $team1 = $generator->create_team([
            'frameworkid' => $framework2->id,
            'userid' => $user0->id,
            'subuserids' => [$user0->id, $user1->id],
        ]);
        $team2 = $generator->create_team([
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserids' => [$user3->id],
        ]);

        $ras = $DB->get_records('role_assignments', [], 'id ASC');
        $this->assertCount(5, $ras);
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext1->id,
            'roleid' => $roleid1,
            'component' => 'tool_murelation',
            'itemid' => $supervisor1->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext2->id,
            'roleid' => $roleid1,
            'component' => 'tool_murelation',
            'itemid' => $supervisor2->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext0->id,
            'roleid' => $roleid2,
            'component' => 'tool_murelation',
            'itemid' => $team1->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext1->id,
            'roleid' => $roleid2,
            'component' => 'tool_murelation',
            'itemid' => $team1->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user1->id,
            'contextid' => $usercontext3->id,
            'roleid' => $roleid2,
            'component' => 'tool_murelation',
            'itemid' => $team2->id,
        ]));

        supervisor::sync_roles();
        $newras = $DB->get_records('role_assignments', [], 'id ASC');
        $this->assertEquals($ras, $newras);

        supervisor::sync_roles($supervisor1->id);
        $newras = $DB->get_records('role_assignments', [], 'id ASC');
        $this->assertEquals($ras, $newras);

        $DB->delete_records('role_assignments', []);
        supervisor::sync_roles($supervisor1->id);
        $ras = $DB->get_records('role_assignments', [], 'id ASC');
        $this->assertCount(1, $ras);
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext1->id,
            'roleid' => $roleid1,
            'component' => 'tool_murelation',
            'itemid' => $supervisor1->id,
        ]));

        $DB->set_field('tool_murelation_framework', 'supervisorroleid', $roleid3, ['id' => $framework1->id]);
        supervisor::sync_roles();
        $ras = $DB->get_records('role_assignments', [], 'id ASC');
        $this->assertCount(5, $ras);
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext1->id,
            'roleid' => $roleid3,
            'component' => 'tool_murelation',
            'itemid' => $supervisor1->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext2->id,
            'roleid' => $roleid3,
            'component' => 'tool_murelation',
            'itemid' => $supervisor2->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext0->id,
            'roleid' => $roleid2,
            'component' => 'tool_murelation',
            'itemid' => $team1->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user0->id,
            'contextid' => $usercontext1->id,
            'roleid' => $roleid2,
            'component' => 'tool_murelation',
            'itemid' => $team1->id,
        ]));
        $this->assertTrue($DB->record_exists('role_assignments', [
            'userid' => $user1->id,
            'contextid' => $usercontext3->id,
            'roleid' => $roleid2,
            'component' => 'tool_murelation',
            'itemid' => $team2->id,
        ]));
    }

    public function test_team_cohort_create(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'teamname' => 'team 1',
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $this->assertSame(null, $supervisor1->teamcohortid);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'teamname' => 'team 1',
            'subuserids' => [$user4->id, $user5->id],
        ]);
        $this->assertSame(null, $supervisor2->teamcohortid);

        $cohort1 = supervisor::team_cohort_create((object)['id' => $supervisor1->id, 'name' => 'Some cohort']);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $this->assertSame($supervisor1->teamcohortid, $cohort1->id);
        $this->assertSame('tool_murelation', $cohort1->component);
        $this->assertSame('Some cohort', $cohort1->name);
        $this->assertSame('', $cohort1->description);
        $this->assertSame(FORMAT_HTML, $cohort1->descriptionformat);
        $this->assertSame('0', $cohort1->visible);
        $this->assertSame((string)$syscontext->id, $cohort1->contextid);

        $cohort2 = supervisor::team_cohort_create((object)[
            'id' => $supervisor2->id,
            'name' => 'Cohort 2',
            'description' => 'Some info',
            'descriptionformat' => FORMAT_MARKDOWN,
            'visible' => 1,
        ]);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);
        $this->assertSame($supervisor2->teamcohortid, $cohort2->id);
        $this->assertSame('tool_murelation', $cohort2->component);
        $this->assertSame('Cohort 2', $cohort2->name);
        $this->assertSame('Some info', $cohort2->description);
        $this->assertSame(FORMAT_MARKDOWN, $cohort2->descriptionformat);
        $this->assertSame('1', $cohort2->visible);
        $this->assertSame((string)$syscontext->id, $cohort2->contextid);

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenantcatcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $supervisor3 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'tenantid' => $tenant1->id,
            'userid' => $user5->id,
            'teamname' => 'team 3',
            'subuserids' => [$user6->id],
        ]);

        $cohort3 = supervisor::team_cohort_create((object)[
            'id' => $supervisor3->id,
            'name' => 'Cohort 3',
        ]);
        $supervisor3 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor3->id], '*', MUST_EXIST);
        $this->assertSame($supervisor3->teamcohortid, $cohort3->id);
        $this->assertSame('tool_murelation', $cohort3->component);
        $this->assertSame('Cohort 3', $cohort3->name);
        $this->assertSame((string)$tenantcatcontext1->id, $cohort3->contextid);
    }

    public function test_team_team_cohort_update(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'teamname' => 'team 1',
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $this->assertSame(null, $supervisor1->teamcohortid);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'teamname' => 'team 1',
            'subuserids' => [$user4->id, $user5->id],
        ]);

        supervisor::team_cohort_create((object)['id' => $supervisor1->id, 'name' => 'Some cohort']);

        $cohort1 = supervisor::team_cohort_update((object)[
            'id' => $supervisor1->id,
            'name' => 'Cohort 1',
        ]);
        $this->assertSame('tool_murelation', $cohort1->component);
        $this->assertSame('Cohort 1', $cohort1->name);
        $this->assertSame('', $cohort1->description);
        $this->assertSame(FORMAT_HTML, $cohort1->descriptionformat);
        $this->assertSame('0', $cohort1->visible);
        $this->assertSame((string)$syscontext->id, $cohort1->contextid);

        $cohort1 = supervisor::team_cohort_update((object)[
            'id' => $supervisor1->id,
            'name' => 'Cohort X1',
            'description' => 'Some info',
            'descriptionformat' => FORMAT_MARKDOWN,
            'visible' => 1,
        ]);
        $this->assertSame('tool_murelation', $cohort1->component);
        $this->assertSame('Cohort X1', $cohort1->name);
        $this->assertSame('Some info', $cohort1->description);
        $this->assertSame(FORMAT_MARKDOWN, $cohort1->descriptionformat);
        $this->assertSame('1', $cohort1->visible);
        $this->assertSame((string)$syscontext->id, $cohort1->contextid);

        $cohort1 = supervisor::team_cohort_update((object)[
            'id' => $supervisor1->id,
            'name' => 'Cohort 1',
            'description' => 'Other info',
            'descriptionformat' => FORMAT_MARKDOWN,
            'visible' => 0,
        ]);
        $this->assertSame('tool_murelation', $cohort1->component);
        $this->assertSame('Cohort 1', $cohort1->name);
        $this->assertSame('Other info', $cohort1->description);
        $this->assertSame(FORMAT_MARKDOWN, $cohort1->descriptionformat);
        $this->assertSame('0', $cohort1->visible);
        $this->assertSame((string)$syscontext->id, $cohort1->contextid);
    }

    public function test_team_cohort_delete(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'teamname' => 'team 1',
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $this->assertSame(null, $supervisor1->teamcohortid);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'teamname' => 'team 2',
            'subuserids' => [$user4->id, $user5->id],
        ]);
        $this->assertSame(null, $supervisor2->teamcohortid);

        $cohort1 = supervisor::team_cohort_create((object)['id' => $supervisor1->id, 'name' => 'Some cohort']);

        $cohort2 = supervisor::team_cohort_create((object)[
            'id' => $supervisor2->id,
            'name' => 'Cohort 2',
            'description' => 'Some info',
            'descriptionformat' => FORMAT_MARKDOWN,
            'visible' => 1,
        ]);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);

        supervisor::team_cohort_delete($supervisor1->id);
        $supervisor1 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor1->id], '*', MUST_EXIST);
        $supervisor2 = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisor2->id], '*', MUST_EXIST);

        $this->assertFalse($DB->record_exists('cohort', ['id' => $cohort1->id]));
        $this->assertSame(null, $supervisor1->teamcohortid);

        $this->assertTrue($DB->record_exists('cohort', ['id' => $cohort2->id]));
        $this->assertSame($cohort2->id, $supervisor2->teamcohortid);

        supervisor::team_cohort_delete($supervisor1->id);
        supervisor::team_cohort_delete(-10);
    }

    public function test_sync_team_cohorts(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework(['uimode' => framework::UIMODE_TEAMS]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'teamname' => 'team 1',
            'subuserids' => [$user2->id, $user3->id],
            'teamcohortcreate' => 1,
        ]);
        $this->assertNotNull($supervisor1->teamcohortid);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'teamname' => 'team 2',
            'subuserids' => [$user4->id, $user5->id],
            'teamcohortcreate' => 1,
        ]);
        $supervisor3 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => null,
            'teamname' => 'team 3',
            'subuserids' => [$user6->id],
            'teamcohortcreate' => 1,
        ]);

        $members = $DB->get_records('cohort_members', [], 'cohortid ASC, userid ASC');
        $this->assertCount(5, $members);
        supervisor::sync_team_cohorts();
        supervisor::sync_team_cohorts($supervisor1->id);
        $this->assertEquals($members, $DB->get_records('cohort_members', [], 'cohortid ASC, userid ASC'));

        $members = $DB->get_records_menu('cohort_members', [], 'cohortid ASC, userid ASC', 'userid,cohortid');
        $this->assertCount(5, $members);
        $DB->delete_records('cohort_members', []);
        supervisor::sync_team_cohorts();
        $this->assertSame($members, $DB->get_records_menu('cohort_members', [], 'cohortid ASC, userid ASC', 'userid,cohortid'));

        $members = $DB->get_records_menu('cohort_members', ['cohortid' => $supervisor1->teamcohortid], 'cohortid ASC, userid ASC', 'userid,cohortid');
        $DB->delete_records('cohort_members', []);
        supervisor::sync_team_cohorts($supervisor1->id);
        $this->assertSame($members, $DB->get_records_menu('cohort_members', [], 'cohortid ASC, userid ASC', 'userid,cohortid'));

        supervisor::sync_team_cohorts();
        $members = $DB->get_records_menu('cohort_members', [], 'cohortid ASC, userid ASC', 'userid,cohortid');
        $DB->set_field('cohort_members', 'userid', $user7->id, ['userid' => $user2->id]);
        supervisor::sync_team_cohorts();
        $this->assertSame($members, $DB->get_records_menu('cohort_members', [], 'cohortid ASC, userid ASC', 'userid,cohortid'));

        $members = $DB->get_records_menu('cohort_members', ['cohortid' => $supervisor3->teamcohortid], 'cohortid ASC, userid ASC', 'userid,cohortid');
        $DB->delete_records('tool_murelation_supervisor', ['id' => $supervisor1->id]);
        $DB->delete_records('tool_murelation_supervisor', ['id' => $supervisor2->id]);
        supervisor::sync_team_cohorts();
        $this->assertSame($members, $DB->get_records_menu('cohort_members', [], 'cohortid ASC, userid ASC', 'userid,cohortid'));
    }

    public function test_creon_cleanup(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();

        $roleid1 = $this->getDataGenerator()->create_role();
        $roleid2 = $this->getDataGenerator()->create_role();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'supervisorroleid' => $roleid1,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
            'supervisorroleid' => $roleid2,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user4->id,
            'teamname' => 'team 1',
            'subuserids' => [$user2->id, $user3->id],
            'teamcohortcreate' => 1,
        ]);
        $this->assertNotNull($supervisor1->teamcohortid);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'teamname' => 'team 2',
            'subuserids' => [$user4->id, $user5->id],
            'teamcohortcreate' => 1,
        ]);
        $supervisor3 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'teamname' => 'team 3',
            'subuserid' => $user6->id,
        ]);

        $racount = $DB->count_records('role_assignments', []);
        $this->assertSame(5, $racount);
        $cmcount = $DB->count_records('cohort_members', []);
        $this->assertSame(4, $cmcount);

        supervisor::cron_cleanup();
        $this->assertSame($racount, $DB->count_records('role_assignments', []));
        $this->assertSame($cmcount, $DB->count_records('cohort_members', []));

        $DB->delete_records('role_assignments', []);
        $DB->delete_records('cohort_members', []);
        supervisor::cron_cleanup();
        $this->assertSame($racount, $DB->count_records('role_assignments', []));
        $this->assertSame($cmcount, $DB->count_records('cohort_members', []));

        $DB->set_field('user', 'deleted', 1, ['id' => $user1->id]);
        $DB->set_field('user', 'deleted', 1, ['id' => $user2->id]);
        supervisor::cron_cleanup();
        $this->assertSame(1, $DB->count_records('role_assignments', []));
        $this->assertSame(3, $DB->count_records('cohort_members', []));
    }
}
