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

namespace tool_murelation\phpunit\external\form_autocomplete;

use tool_murelation\external\form_autocomplete\members_add_cohort_cohortid;
use tool_murelation\local\framework;
use tool_mulib\local\mulib;

/**
 * List of cohorts with subordinate candidates for team tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_murelation\external\form_autocomplete\members_add_cohort_cohortid
 */
final class members_add_cohort_cohortid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::execute
     */
    public function test_execute(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $category1 = $this->getDataGenerator()->create_category([]);
        $category2 = $this->getDataGenerator()->create_category([]);

        $syscontext = \context_system::instance();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $cohort0 = $this->getDataGenerator()->create_cohort(['visible' => 1]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $catcontext1->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['visible' => 0, 'contextid' => $catcontext2->id]);

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'subordinatecohortid' => $cohort0->id,
        ]);

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $roleid, $syscontext->id);

        role_assign($roleid, $manager->id, $syscontext);

        cohort_add_member($cohort0->id, $user0->id);
        cohort_add_member($cohort0->id, $user1->id);
        cohort_add_member($cohort0->id, $user2->id);
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort2->id, $user3->id);

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user0->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor1 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'contextid' => $catcontext1->id,
        ]);
        $supervisor2 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 2',
        ]);
        $supervisor3 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'subuserids' => [$user1->id],
        ]);

        $this->setUser($admin);

        $result = members_add_cohort_cohortid::execute('', $supervisor1->id);
        $result = members_add_cohort_cohortid::clean_returnvalue(members_add_cohort_cohortid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(3, $result['list']);
        $this->assertEquals(['value' => $cohort0->id, 'label' => $cohort0->name], $result['list'][0]);
        $this->assertEquals(['value' => $cohort1->id, 'label' => $cohort1->name], $result['list'][1]);
        $this->assertEquals(['value' => $cohort2->id, 'label' => $cohort2->name], $result['list'][2]);

        $this->setUser($manager);

        $result = members_add_cohort_cohortid::execute('', $supervisor1->id);
        $result = members_add_cohort_cohortid::clean_returnvalue(members_add_cohort_cohortid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(2, $result['list']);
        $this->assertEquals(['value' => $cohort0->id, 'label' => $cohort0->name], $result['list'][0]);
        $this->assertEquals(['value' => $cohort1->id, 'label' => $cohort1->name], $result['list'][1]);

        $result = members_add_cohort_cohortid::execute($cohort0->name, $supervisor1->id);
        $result = members_add_cohort_cohortid::clean_returnvalue(members_add_cohort_cohortid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(1, $result['list']);
        $this->assertEquals(['value' => $cohort0->id, 'label' => $cohort0->name], $result['list'][0]);

        try {
            members_add_cohort_cohortid::execute('', $supervisor0->id);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Framework is not compatible with Teams mode)', $ex->getMessage());
        }

        $this->setUser($user1);
        try {
            members_add_cohort_cohortid::execute('', $supervisor1->id);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Cannot manage team members)', $ex->getMessage());
        }

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenantcatcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenantcatcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $cohort3 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcatcontext1->id]);
        $cohort4 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcatcontext2->id]);

        $this->setUser($manager);

        $result = members_add_cohort_cohortid::execute('', $supervisor1->id);
        $result = members_add_cohort_cohortid::clean_returnvalue(members_add_cohort_cohortid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(2, $result['list']);
        $this->assertEquals(['value' => $cohort0->id, 'label' => $cohort0->name], $result['list'][0]);
        $this->assertEquals(['value' => $cohort1->id, 'label' => $cohort1->name], $result['list'][1]);

        $supervisor4 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 4',
            'tenantid' => $tenant1->id,
        ]);

        $result = members_add_cohort_cohortid::execute('', $supervisor4->id);
        $result = members_add_cohort_cohortid::clean_returnvalue(members_add_cohort_cohortid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertSame(50, $result['maxitems']);
        $this->assertCount(3, $result['list']);
        $this->assertEquals(['value' => $cohort0->id, 'label' => $cohort0->name], $result['list'][0]);
        $this->assertEquals(['value' => $cohort1->id, 'label' => $cohort1->name], $result['list'][1]);
        $this->assertEquals(['value' => $cohort3->id, 'label' => $cohort3->name], $result['list'][2]);
    }

    /**
     * @covers ::get_candidates
     */
    public function test_get_candidates(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $category1 = $this->getDataGenerator()->create_category([]);
        $category2 = $this->getDataGenerator()->create_category([]);

        $syscontext = \context_system::instance();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $cohort0 = $this->getDataGenerator()->create_cohort(['visible' => 1]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $catcontext1->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['visible' => 0, 'contextid' => $catcontext2->id]);

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'subordinatecohortid' => $cohort0->id,
        ]);

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $roleid, $syscontext->id);

        role_assign($roleid, $manager->id, $syscontext);

        cohort_add_member($cohort0->id, $user0->id);
        cohort_add_member($cohort0->id, $user1->id);
        cohort_add_member($cohort0->id, $user2->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort2->id, $user3->id);

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user0->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor1 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'contextid' => $catcontext1->id,
        ]);
        $supervisor2 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 2',
        ]);
        $supervisor3 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'subuserids' => [$user1->id],
        ]);

        $this->setUser($manager);

        $this->assertEquals(
            [$user0->id, $user1->id, $user2->id],
            members_add_cohort_cohortid::get_candidates($supervisor1->id, $cohort0->id)
        );
        $this->assertEquals(
            [$user1->id, $user2->id, $user3->id],
            members_add_cohort_cohortid::get_candidates($supervisor1->id, $cohort2->id)
        );
        $this->assertEquals(
            [$user0->id, $user1->id, $user2->id],
            members_add_cohort_cohortid::get_candidates($supervisor2->id, $cohort0->id)
        );
        $this->assertEquals(
            [$user1->id, $user2->id],
            members_add_cohort_cohortid::get_candidates($supervisor2->id, $cohort2->id)
        );
        $this->assertEquals(
            [$user0->id, $user2->id],
            members_add_cohort_cohortid::get_candidates($supervisor3->id, $cohort0->id)
        );
        $this->assertEquals(
            [$user2->id, $user3->id],
            members_add_cohort_cohortid::get_candidates($supervisor3->id, $cohort2->id)
        );

        if (!mulib::is_mutenancy_available()) {
            return;
        }
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $tenantcatcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenantcatcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $cohort3 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcatcontext1->id]);
        $cohort4 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcatcontext2->id]);

        $supervisor4 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 4',
            'tenantid' => $tenant1->id,
        ]);
        $supervisor5 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 5',
            'tenantid' => $tenant2->id,
        ]);

        $tuser1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tuser2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        cohort_add_member($cohort0->id, $tuser1->id);
        cohort_add_member($cohort0->id, $tuser2->id);
        cohort_add_member($cohort3->id, $tuser1->id);
        cohort_add_member($cohort4->id, $tuser2->id);

        $this->assertEquals(
            [$user0->id, $user1->id, $user2->id, $tuser1->id, $tuser2->id],
            members_add_cohort_cohortid::get_candidates($supervisor1->id, $cohort0->id)
        );

        $this->assertEquals(
            [$tuser1->id],
            members_add_cohort_cohortid::get_candidates($supervisor4->id, $cohort0->id)
        );
    }

    /**
     * @covers ::validate_value
     */
    public function test_validate_value(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $category1 = $this->getDataGenerator()->create_category([]);
        $category2 = $this->getDataGenerator()->create_category([]);

        $syscontext = \context_system::instance();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $cohort0 = $this->getDataGenerator()->create_cohort(['visible' => 1]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $catcontext1->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['visible' => 0, 'contextid' => $catcontext2->id]);

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'subordinatecohortid' => $cohort0->id,
        ]);

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $roleid, $syscontext->id);

        role_assign($roleid, $manager->id, $syscontext);

        cohort_add_member($cohort0->id, $user0->id);
        cohort_add_member($cohort0->id, $user1->id);
        cohort_add_member($cohort0->id, $user2->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort2->id, $user3->id);

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user0->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor1 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
            'contextid' => $catcontext1->id,
        ]);
        $supervisor2 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 2',
        ]);
        $supervisor3 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'subuserids' => [$user1->id],
        ]);

        $this->setUser($manager);

        $this->assertSame(null, members_add_cohort_cohortid::validate_value($cohort0->id, ['supervisorid' => $supervisor1->id], $syscontext));
        $this->assertSame('No subordinates found', members_add_cohort_cohortid::validate_value($cohort1->id, ['supervisorid' => $supervisor1->id], $syscontext));
        $this->assertSame('Error', members_add_cohort_cohortid::validate_value($cohort2->id, ['supervisorid' => $supervisor1->id], $syscontext));

        $supervisor1 = \tool_murelation\local\supervisor::update((object)[
            'id' => $supervisor1->id,
            'maxsubordinates' => 2,
        ]);
        $this->assertSame('Subordinates limit reached', members_add_cohort_cohortid::validate_value($cohort0->id, ['supervisorid' => $supervisor1->id], $syscontext));
        $supervisor1 = \tool_murelation\local\supervisor::update((object)[
            'id' => $supervisor1->id,
            'maxsubordinates' => 3,
        ]);
        $this->assertSame(null, members_add_cohort_cohortid::validate_value($cohort0->id, ['supervisorid' => $supervisor1->id], $syscontext));

        if (!mulib::is_mutenancy_available()) {
            return;
        }
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $tenant1 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $tenantcatcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenantcatcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $cohort3 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcatcontext1->id]);
        $cohort4 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcatcontext2->id]);

        $supervisor4 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 4',
            'tenantid' => $tenant1->id,
        ]);
        $supervisor5 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 5',
            'tenantid' => $tenant2->id,
        ]);

        $tuser1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tuser2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setUser($manager);

        $this->assertSame('No subordinates found', members_add_cohort_cohortid::validate_value(
            $cohort1->id,
            ['supervisorid' => $supervisor4->id],
            $syscontext
        ));

        cohort_add_member($cohort1->id, $tuser1->id);
        cohort_add_member($cohort1->id, $tuser2->id);
        cohort_add_member($cohort3->id, $tuser1->id);
        cohort_add_member($cohort4->id, $tuser2->id);

        $this->assertSame(null, members_add_cohort_cohortid::validate_value(
            $cohort1->id,
            ['supervisorid' => $supervisor4->id],
            $tenantcatcontext1
        ));
        $this->assertSame(null, members_add_cohort_cohortid::validate_value(
            $cohort1->id,
            ['supervisorid' => $supervisor5->id],
            $tenantcatcontext2
        ));

        $this->assertSame(null, members_add_cohort_cohortid::validate_value(
            $cohort3->id,
            ['supervisorid' => $supervisor4->id],
            $tenantcatcontext1
        ));
        $this->assertSame('No subordinates found', members_add_cohort_cohortid::validate_value(
            $cohort3->id,
            ['supervisorid' => $supervisor5->id],
            $tenantcatcontext2
        ));
    }
}
