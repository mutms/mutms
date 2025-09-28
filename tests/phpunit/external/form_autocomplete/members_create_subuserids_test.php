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

use tool_murelation\external\form_autocomplete\members_create_subuserids;
use tool_murelation\local\framework;
use tool_mulib\local\mulib;

/**
 * List of subordinate candidates for team tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_murelation\external\form_autocomplete\members_create_subuserids
 */
final class members_create_subuserids_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::execute
     */
    public function test_execute(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('moodle/site:viewuseridentity', CAP_ALLOW, $roleid, $syscontext->id);

        $cohort = $this->getDataGenerator()->create_cohort();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'subordinatecohortid' => $cohort->id,
        ]);

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'Manager',
            'email' => 'manager@example.com',
        ]);
        role_assign($roleid, $manager->id, $syscontext);
        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'User',
            'email' => 'user0@example.com',
        ]);
        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'user1@example.com',
        ]);
        cohort_add_member($cohort->id, $user1->id);
        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => 'Second',
            'lastname' => 'User',
            'email' => 'user2@example.com',
        ]);
        cohort_add_member($cohort->id, $user2->id);
        $user3 = $this->getDataGenerator()->create_user([
            'firstname' => 'Third',
            'lastname' => 'User',
            'email' => 'user3@example.com',
        ]);
        cohort_add_member($cohort->id, $user3->id);

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user0->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor1 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
        ]);
        $supervisor2 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 2',
        ]);
        $supervisor3 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'subuserids' => [$user3->id],
        ]);

        $this->setUser($manager);

        $result = members_create_subuserids::execute('', $supervisor1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(5, $result['list']);
        $this->assertSame($result['list'][0]['value'], $manager->id);
        $this->assertSame($result['list'][1]['value'], $admin->id);
        $this->assertSame($result['list'][2]['value'], $user1->id);
        $this->assertSame($result['list'][3]['value'], $user0->id);
        $this->assertSame($result['list'][4]['value'], $user2->id);

        $result = members_create_subuserids::execute('irst', $supervisor1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(1, $result['list']);
        $this->assertSame($result['list'][0]['value'], $user1->id);

        $result = members_create_subuserids::execute('', $supervisor2->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(3, $result['list']);
        $this->assertSame($result['list'][0]['value'], $user1->id);
        $this->assertSame($result['list'][1]['value'], $user2->id);
        $this->assertSame($result['list'][2]['value'], $user3->id);

        $result = members_create_subuserids::execute('user2@', $supervisor2->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(1, $result['list']);
        $this->assertSame($result['list'][0]['value'], $user2->id);

        try {
            members_create_subuserids::execute('', $supervisor0->id);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Framework is not compatible with Teams mode)', $ex->getMessage());
        }

        $this->setUser($user1);
        try {
            members_create_subuserids::execute('', $supervisor1->id);
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

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort2->id]);

        $this->setUser($manager);

        $DB->set_field('tool_murelation_supervisor', 'tenantid', $tenant1->id, ['id' => $supervisor1->id]);
        $result = members_create_subuserids::execute('', $supervisor1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(0, $result['list']);

        $user1 = \tool_mutenancy\local\user::allocate($user1->id, $tenant1->id);
        $user2 = \tool_mutenancy\local\user::allocate($user2->id, $tenant2->id);
        cohort_add_member($cohort1->id, $user0->id);

        $result = members_create_subuserids::execute('', $supervisor1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(2, $result['list']);
        $this->assertSame($result['list'][0]['value'], $user1->id);
        $this->assertSame($result['list'][1]['value'], $user0->id);

        $DB->set_field('tool_murelation_supervisor', 'tenantid', $tenant2->id, ['id' => $supervisor1->id]);
        $result = members_create_subuserids::execute('', $supervisor1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(1, $result['list']);
        $this->assertSame($result['list'][0]['value'], $user2->id);
    }

    /**
     * @covers ::format_label
     */
    public function test_format_label(): void {
        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('moodle/site:viewuseridentity', CAP_ALLOW, $roleid, $syscontext->id);

        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext);
        $user = $this->getDataGenerator()->create_user();

        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'user1@example.com',
        ]);

        $this->setUser($user);
        $result = members_create_subuserids::format_label($user1, $syscontext);
        $this->assertStringContainsString('First User', $result);
        $this->assertStringNotContainsString($user1->email, $result);

        $this->setUser($manager);
        $result = members_create_subuserids::format_label($user1, $syscontext);
        $this->assertStringContainsString('First User', $result);
        $this->assertStringContainsString($user1->email, $result);
    }

    /**
     * @covers ::validate_value
     */
    public function test_validate_value(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:viewpositions', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('tool/murelation:managepositions', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('moodle/site:viewuseridentity', CAP_ALLOW, $roleid, $syscontext->id);

        $cohort = $this->getDataGenerator()->create_cohort();

        $framework0 = $generator->create_framework(['uimode' => framework::UIMODE_SUPERVISORS]);
        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
            'subordinatecohortid' => $cohort->id,
        ]);

        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'Manager',
            'email' => 'manager@example.com',
        ]);
        role_assign($roleid, $manager->id, $syscontext);
        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'User',
            'email' => 'user0@example.com',
        ]);
        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'user1@example.com',
        ]);
        cohort_add_member($cohort->id, $user1->id);
        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => 'Second',
            'lastname' => 'User',
            'email' => 'user2@example.com',
        ]);
        cohort_add_member($cohort->id, $user2->id);
        $user3 = $this->getDataGenerator()->create_user([
            'firstname' => 'Third',
            'lastname' => 'User',
            'email' => 'user3@example.com',
        ]);
        cohort_add_member($cohort->id, $user3->id);

        $supervisor0 = \tool_murelation\local\uimode_supervisors::supervisor_edit((object)[
            'frameworkid' => $framework0->id,
            'userid' => $user0->id,
            'subuserid' => $user1->id,
        ]);
        $supervisor1 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 1',
        ]);
        $supervisor2 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework2->id,
            'teamname' => 'Team 2',
        ]);
        $supervisor3 = \tool_murelation\local\uimode_teams::team_create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'Team 3',
            'subuserids' => [$user3->id],
        ]);

        $this->setUser($manager);

        $this->assertSame(null, members_create_subuserids::validate_value(
            $user1->id,
            ['supervisorid' => $supervisor2->id],
            $syscontext
        ));
        $this->assertSame(null, members_create_subuserids::validate_value(
            $user2->id,
            ['supervisorid' => $supervisor2->id],
            $syscontext
        ));
        $this->assertSame('Error', members_create_subuserids::validate_value(
            $user0->id,
            ['supervisorid' => $supervisor2->id],
            $syscontext
        ));
        $this->assertSame(null, members_create_subuserids::validate_value(
            $user0->id,
            ['supervisorid' => $supervisor1->id],
            $syscontext
        ));
        $this->assertSame('Error', members_create_subuserids::validate_value(
            $user3->id,
            ['supervisorid' => $supervisor1->id],
            $syscontext
        ));

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        \tool_mutenancy\local\tenancy::activate();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenantcontext1 = \context_tenant::instance($tenant1->id);

        $this->setUser($manager);

        $user1 = \tool_mutenancy\local\user::allocate($user1->id, $tenant1->id);
        $user2 = \tool_mutenancy\local\user::allocate($user2->id, $tenant2->id);
        cohort_add_member($cohort1->id, $user0->id);

        $this->assertSame(null, members_create_subuserids::validate_value(
            $user1->id,
            ['supervisorid' => $supervisor1->id],
            $syscontext
        ));
        $this->assertSame(null, members_create_subuserids::validate_value(
            $user2->id,
            ['supervisorid' => $supervisor1->id],
            $syscontext
        ));

        $DB->set_field('tool_murelation_supervisor', 'tenantid', $tenant1->id, ['id' => $supervisor1->id]);
        $this->assertSame(null, members_create_subuserids::validate_value(
            $user1->id,
            ['supervisorid' => $supervisor1->id],
            $tenantcontext1
        ));
        $this->assertSame('Error', members_create_subuserids::validate_value(
            $user2->id,
            ['supervisorid' => $supervisor1->id],
            $tenantcontext1
        ));
    }
}
