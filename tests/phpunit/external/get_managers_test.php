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

use tool_mutenancy\external\get_managers;

/**
 * Multi-tenancy external function tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\external\get_managers
 */
final class get_managers_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_definition(): void {
        $function = \core_external\external_api::external_function_info('tool_mutenancy_get_managers');
        $this->assertSame(get_managers::class, $function->classname);
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

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext->id);

        \tool_mutenancy\local\manager::add($tenant1->id, $user1->id);
        \tool_mutenancy\local\manager::add($tenant2->id, $user1->id);
        \tool_mutenancy\local\manager::add($tenant2->id, $user2->id);

        $this->setUser($manager);

        $managers = get_managers::execute($tenant1->id);
        $this->assertCount(1, $managers);
        $m1 = $managers[$user1->id];
        $this->assertSame($user1->id, $m1->id);
        $this->assertSame($user1->username, $m1->username);
        $this->assertSame($user1->email, $m1->email);
        $this->assertSame($user1->firstname, $m1->firstname);
        $this->assertSame($user1->lastname, $m1->lastname);
        $this->assertSame($user1->tenantid, $m1->tenantid);

        $managers = get_managers::execute($tenant2->id);
        $this->assertCount(2, $managers);
        $m1 = $managers[$user1->id];
        $this->assertSame($user1->id, $m1->id);
        $this->assertSame($user1->username, $m1->username);
        $this->assertSame($user1->email, $m1->email);
        $this->assertSame($user1->firstname, $m1->firstname);
        $this->assertSame($user1->lastname, $m1->lastname);
        $this->assertSame($user1->tenantid, $m1->tenantid);
        $m2 = $managers[$user2->id];
        $this->assertSame($user2->id, $m2->id);
        $this->assertSame($user2->username, $m2->username);
        $this->assertSame($user2->email, $m2->email);
        $this->assertSame($user2->firstname, $m2->firstname);
        $this->assertSame($user2->lastname, $m2->lastname);
        $this->assertSame($user2->tenantid, $m2->tenantid);

        $this->setUser($user);
        try {
            get_managers::execute($tenant1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }
    }
}
