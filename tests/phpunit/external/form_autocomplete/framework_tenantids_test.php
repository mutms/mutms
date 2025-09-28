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

use tool_murelation\external\form_autocomplete\framework_tenantids;
use tool_mulib\local\mulib;

/**
 * Framework allow tenant selection external function tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_murelation\external\form_autocomplete\framework_tenantids
 */
final class framework_tenantids_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('Multi-tenancy is not available');
        }
    }

    /**
     * @covers ::execute
     */
    public function test_execute(): void {
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();

        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/murelation:manageframeworks', CAP_ALLOW, $roleid, $syscontext->id);

        $user0 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext);

        $tenant1 = $tenantgenerator->create_tenant([
            'name' => 'First tenant',
            'idnumber' => 'ten1',
        ]);
        $tenant2 = $tenantgenerator->create_tenant([
            'name' => 'Second tenant',
            'idnumber' => 'ten2',
        ]);
        $tenant3 = $tenantgenerator->create_tenant([
            'name' => 'Third tenant',
            'idnumber' => 'ten3',
        ]);

        $this->setUser($manager);

        $result = framework_tenantids::execute('');
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $tenant1->id, 'label' => $tenant1->name],
            ['value' => $tenant2->id, 'label' => $tenant2->name],
            ['value' => $tenant3->id, 'label' => $tenant3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = framework_tenantids::execute('Second');
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $tenant2->id, 'label' => $tenant2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = framework_tenantids::execute('2');
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $tenant2->id, 'label' => $tenant2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($user0);
        try {
            framework_tenantids::execute('');
            $this->fail('exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame('Sorry, but you do not currently have permissions to do that (Manage user relation frameworks).', $ex->getMessage());
        }
    }

    /**
     * @covers ::validate_value
     */
    public function test_validate_value(): void {
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \context_system::instance();

        $manager = $this->getDataGenerator()->create_user();

        $tenant1 = $tenantgenerator->create_tenant([
            'name' => 'First tenant',
            'idnumber' => 'ten1',
        ]);
        $tenant2 = $tenantgenerator->create_tenant([
            'name' => 'Second tenant',
            'idnumber' => 'ten2',
        ]);
        $tenant3 = $tenantgenerator->create_tenant([
            'name' => 'Third tenant',
            'idnumber' => 'ten3',
        ]);

        $this->setUser($manager);

        $this->assertSame(null, framework_tenantids::validate_value($tenant1->id, [], $syscontext));
        $this->assertSame(null, framework_tenantids::validate_value($tenant2->id, [], $syscontext));
        $this->assertSame(null, framework_tenantids::validate_value($tenant3->id, [], $syscontext));
        $this->assertSame('Error', framework_tenantids::validate_value(-10, [], $syscontext));
    }
}
