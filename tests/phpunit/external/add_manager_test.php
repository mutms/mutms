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

use tool_mutenancy\external\add_manager;

/**
 * Multi-tenancy external function tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\external\add_manager
 */
final class add_manager_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_definition(): void {
        $function = \core_external\external_api::external_function_info('tool_mutenancy_add_manager');
        $this->assertSame(add_manager::class, $function->classname);
        $this->assertSame('execute', $function->methodname);
        $this->assertSame('tool_mutenancy', $function->component);
        $this->assertSame(false, $function->allowed_from_ajax);
        $this->assertSame('write', $function->type);
        $this->assertSame(true, $function->loginrequired);
    }

    public function test_execute(): void {
        global $DB;
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:admin', CAP_ALLOW, $roleid, $syscontext->id);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext->id);

        $this->setUser($manager);

        $this->assertTrue(add_manager::execute($tenant1->id, $user1->id));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user1->id]));

        $this->assertFalse(add_manager::execute($tenant1->id, $user1->id));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user1->id]));

        try {
            add_manager::execute($tenant1->id, $user2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (tenant manager cannot be a member of another tenant)', $ex->getMessage());
        }

        $this->setUser($user);
        try {
            add_manager::execute($tenant1->id, $user1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }
    }
}
