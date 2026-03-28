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

namespace tool_mutenancy\phpunit\external;

use tool_mutenancy\external\get_tenants;

/**
 * Multi-tenancy external function tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\external\get_tenants
 */
final class get_tenants_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_definition(): void {
        $function = \core_external\external_api::external_function_info('tool_mutenancy_get_tenants');
        $this->assertSame(get_tenants::class, $function->classname);
        $this->assertSame('execute', $function->methodname);
        $this->assertSame('tool_mutenancy', $function->component);
        $this->assertSame(false, $function->allowed_from_ajax);
        $this->assertSame('read', $function->type);
        $this->assertSame(true, $function->loginrequired);
    }

    public function test_execute(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:view', CAP_ALLOW, $roleid, $syscontext->id);

        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext->id);

        $this->setUser($manager);
        $this->assertSame([], get_tenants::execute([]));

        $tenant1 = $generator->create_tenant(['idnumber' => 'xt1']);
        $tenant2 = $generator->create_tenant(['name' => 'Xten2']);
        $tenant3 = $generator->create_tenant(['archived' => 1]);

        $result = get_tenants::execute([]);
        $result = get_tenants::clean_returnvalue(get_tenants::execute_returns(), $result);
        $this->assertCount(3, $result);

        $result = get_tenants::execute([['field' => 'id', 'value' => -1]]);
        $this->assertCount(0, $result);

        $result = get_tenants::execute([['field' => 'id', 'value' => $tenant1->id]]);
        $this->assertCount(1, $result);
        $tenant = reset($result);
        $this->assertEquals($tenant1, $tenant);

        $result = get_tenants::execute([['field' => 'name', 'value' => $tenant2->name]]);
        $this->assertCount(1, $result);
        $tenant = reset($result);
        $this->assertEquals($tenant2, $tenant);

        $result = get_tenants::execute([['field' => 'idnumber', 'value' => $tenant2->idnumber]]);
        $this->assertCount(1, $result);
        $tenant = reset($result);
        $this->assertEquals($tenant2, $tenant);

        $result = get_tenants::execute([['field' => 'archived', 'value' => 1]]);
        $this->assertCount(1, $result);

        $result = get_tenants::execute([['field' => 'archived', 'value' => 0]]);
        $this->assertCount(2, $result);

        $this->setUser($user);
        try {
            get_tenants::execute([]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }
    }
}
