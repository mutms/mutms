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

namespace tool_mutenancy\phpunit\external\form_autocomplete;

use tool_mutenancy\external\form_autocomplete\associate_add_userids;

/**
 * Multi-tenancy external function tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\external\form_autocomplete\associate_add_userids
 */
final class associate_add_userids_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::execute
     */
    public function test_execute(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:view', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('moodle/cohort:assign', CAP_ALLOW, $roleid, $syscontext->id);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $categorycontext = \context_coursecat::instance($tenant1->categoryid);

        $cohort1 = $this->getDataGenerator()->create_cohort(['contextid' => $categorycontext->id]);
        $tenant1->assoccohortid = $cohort1->id;
        $tenant1 = \tool_mutenancy\local\tenant::update($tenant1);

        $cohort2 = $this->getDataGenerator()->create_cohort(['contextid' => $categorycontext->id, 'component' => 'core_bc']);
        $tenant2->assoccohortid = $cohort2->id;
        $tenant2 = \tool_mutenancy\local\tenant::update($tenant2);

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'Manager',
            'email' => 'manager@example.com',
        ]);
        role_assign($roleid, $manager->id, $categorycontext->id);
        role_assign($roleid, $manager->id, $tenantcontext1->id);

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
        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => 'Second',
            'lastname' => 'User',
            'email' => 'user2@example.com',
        ]);
        $user3 = $this->getDataGenerator()->create_user([
            'tenantid' => $tenant1->id,
            'firstname' => 'Third',
            'lastname' => 'User',
            'email' => 'user3@example.com',
        ]);

        cohort_add_member($cohort1->id, $user2->id);

        $this->setUser($manager);
        $result = associate_add_userids::execute('', $tenant1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(4, $result['list']);
        $this->assertSame($result['list'][0]['value'], $manager->id);
        $this->assertSame($result['list'][1]['value'], $admin->id);
        $this->assertSame($result['list'][2]['value'], $user1->id);
        $this->assertSame($result['list'][3]['value'], $user0->id);

        $this->setUser($manager);
        $result = associate_add_userids::execute('First', $tenant1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(1, $result['list']);
        $this->assertSame($result['list'][0]['value'], $user1->id);

        $this->setUser($admin);
        $result = associate_add_userids::execute('First', $tenant1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(1, $result['list']);
        $this->assertSame($result['list'][0]['value'], $user1->id);

        $this->setUser($manager);
        try {
            associate_add_userids::execute('', $tenant2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame('Sorry, but you do not currently have permissions to do that (View tenant).', $ex->getMessage());
        }

        $this->setUser($admin);
        try {
            associate_add_userids::execute('', $tenant2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Associate cohort cannot belong to any component)', $ex->getMessage());
        }

        $this->setUser($admin);
        try {
            associate_add_userids::execute('', $tenant3->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant does not have associated cohort)', $ex->getMessage());
        }
    }

    /**
     * @covers ::format_label
     */
    public function test_format_label(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenantcontext1 = \context_tenant::instance($tenant1->id);

        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'User',
            'email' => 'user0@example.com',
        ]);
        $user1 = $this->getDataGenerator()->create_user([
            'tenantid' => $tenant1->id,
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'user1@example.com',
        ]);

        $result = associate_add_userids::format_label($user0, $tenantcontext1);
        $this->assertStringContainsString('Global User', $result);
        $this->assertStringNotContainsString($user0->email, $result);

        $this->setAdminUser();
        $result = associate_add_userids::format_label($user0, $tenantcontext1);
        $this->assertStringContainsString('Global User', $result);
        $this->assertStringContainsString($user0->email, $result);
    }

    /**
     * @covers ::validate_value
     */
    public function test_validate_value(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenantcontext1 = \context_tenant::instance($tenant1->id);

        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'User',
            'email' => 'user0@example.com',
        ]);
        $user1 = $this->getDataGenerator()->create_user([
            'tenantid' => $tenant1->id,
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'user1@example.com',
        ]);

        $this->assertSame(null, associate_add_userids::validate_value($user0->id, ['tenantid' => $tenant1->id], $tenantcontext1));
        $this->assertSame('Error', associate_add_userids::validate_value($user1->id, ['tenantid' => $tenant1->id], $tenantcontext1));
    }
}
