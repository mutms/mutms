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

use tool_mutenancy\external\form_autocomplete\user_allocate_tenantid;

/**
 * Multi-tenancy external function tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\external\form_autocomplete\user_allocate_tenantid
 */
final class user_allocate_tenantid_test extends \advanced_testcase {
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
        assign_capability('tool/mutenancy:allocate', CAP_ALLOW, $roleid, $syscontext->id);

        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext);

        $tenant1 = $generator->create_tenant([
            'name' => 'First tenant',
            'idnumber' => 'ten1',
        ]);
        $tenant2 = $generator->create_tenant([
            'name' => 'Second tenant',
            'idnumber' => 'ten2',
        ]);
        $tenant3 = $generator->create_tenant([
            'name' => 'Third tenant',
            'idnumber' => 'ten3',
        ]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $this->setUser($manager);

        $result = user_allocate_tenantid::execute('', $user0->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $tenant1->id, 'label' => $tenant1->name],
            ['value' => $tenant2->id, 'label' => $tenant2->name],
            ['value' => $tenant3->id, 'label' => $tenant3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = user_allocate_tenantid::execute('', $user1->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $tenant2->id, 'label' => $tenant2->name],
            ['value' => $tenant3->id, 'label' => $tenant3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = user_allocate_tenantid::execute('Second', $user1->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $tenant2->id, 'label' => $tenant2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = user_allocate_tenantid::execute('2', $user1->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $tenant2->id, 'label' => $tenant2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($user0);
        try {
            user_allocate_tenantid::execute('', $user0->id);
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame('Sorry, but you do not currently have permissions to do that (Allocate users to tenants).', $ex->getMessage());
        }
    }
}
