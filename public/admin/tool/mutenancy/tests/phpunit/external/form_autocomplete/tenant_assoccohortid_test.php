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

use tool_mutenancy\external\form_autocomplete\tenant_assoccohortid;

/**
 * Multi-tenancy external function tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\external\form_autocomplete\tenant_assoccohortid
 */
final class tenant_assoccohortid_test extends \advanced_testcase {
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
        assign_capability('tool/mutenancy:admin', CAP_ALLOW, $roleid, $syscontext->id);

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'Manager',
            'email' => 'manager@example.com',
        ]);
        role_assign($roleid, $manager->id, $tenantcontext1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort([
            'name' => 'First kohort',
            'idnumber' => 'koh1',
        ]);
        $cohort2 = $this->getDataGenerator()->create_cohort([
            'name' => 'Second kohort',
            'idnumber' => 'koh2',
        ]);
        $cohort3 = $this->getDataGenerator()->create_cohort([
            'name' => 'Third kohort',
            'idnumber' => 'koh2',
            'visible' => 0,
        ]);
        $cohort4 = $this->getDataGenerator()->create_cohort([
            'name' => 'Fourth kohort',
            'idnumber' => 'koh2',
            'contextid' => $tenantcontext2->id,
        ]);

        $this->setUser($manager);

        $result = tenant_assoccohortid::execute('', $tenant1->id);
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = tenant_assoccohortid::execute('First', $tenant1->id);
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = tenant_assoccohortid::execute('koh2', $tenant1->id);
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort2->id, 'label' => $cohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($admin);

        $result = tenant_assoccohortid::execute('', $tenant1->id);
        $this->assertfalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = tenant_assoccohortid::execute('', 0);
        $this->assertfalse($result['overflow']);
        $this->assertSame($expected, $result['list']);
    }

    /**
     * @covers ::validate_value
     */
    public function test_validate_value(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:admin', CAP_ALLOW, $roleid, $syscontext->id);

        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user([
            'firstname' => 'Global',
            'lastname' => 'Manager',
            'email' => 'manager@example.com',
        ]);
        role_assign($roleid, $manager->id, $tenantcontext1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort([
            'name' => 'First kohort',
            'idnumber' => 'koh1',
        ]);
        $cohort2 = $this->getDataGenerator()->create_cohort([
            'name' => 'Second kohort',
            'idnumber' => 'koh2',
        ]);
        $cohort3 = $this->getDataGenerator()->create_cohort([
            'name' => 'Third kohort',
            'idnumber' => 'koh2',
            'visible' => 0,
        ]);
        $cohort4 = $this->getDataGenerator()->create_cohort([
            'name' => 'Fourth kohort',
            'idnumber' => 'koh2',
            'contextid' => $tenantcontext2->id,
        ]);

        $this->setUser($manager);
        $this->assertSame(null, tenant_assoccohortid::validate_value($cohort1->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame(null, tenant_assoccohortid::validate_value($cohort2->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame('Error', tenant_assoccohortid::validate_value($cohort3->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame('Error', tenant_assoccohortid::validate_value($cohort4->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame(null, tenant_assoccohortid::validate_value($cohort1->id, ['tenantid' => $tenant2->id], $syscontext)); // Tenant access not checked.

        $this->setUser($admin);
        $this->assertSame(null, tenant_assoccohortid::validate_value($cohort1->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame(null, tenant_assoccohortid::validate_value($cohort2->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame(null, tenant_assoccohortid::validate_value($cohort3->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame('Error', tenant_assoccohortid::validate_value($cohort4->id, ['tenantid' => $tenant1->id], $syscontext));
        $this->assertSame(null, tenant_assoccohortid::validate_value($cohort1->id, ['tenantid' => $tenant2->id], $syscontext));
    }
}
