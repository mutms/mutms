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

namespace tool_mutenancy\phpunit\local\form;

use tool_mutenancy\local\form\tenant_switch as form;

/**
 * Multi-tenancy switching form tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\form\tenant_switch
 */
final class tenant_switch_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_options
     */
    public function test_get_options(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort4 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenant3 = $generator->create_tenant();
        $tenant4 = $generator->create_tenant(['archived' => 1, 'assoccohortid' => $cohort4->id]);

        $syscontext = \context_system::instance();
        $switchroleid = create_role('sw', 'sw', 'sw');
        assign_capability('tool/mutenancy:switch', CAP_ALLOW, $switchroleid, $syscontext->id);
        $viewroleid = create_role('vw', 'vw', 'vw');
        assign_capability('tool/mutenancy:view', CAP_ALLOW, $viewroleid, $syscontext->id);

        $admin = get_admin();
        $this->setUser($admin);
        $expected = [
            '' => [
                0 => 'No tenant',
            ],
            'Tenants' => [
                $tenant1->id => $tenant1->name,
                $tenant2->id => $tenant2->name,
                $tenant3->id => $tenant3->name,
            ],
        ];
        $this->assertSame($expected, form::get_options());
        cohort_add_member($cohort1->id, $admin->id);
        $expected = [
            '' => [
                0 => 'No tenant',
            ],
            'My tenants' => [
                $tenant1->id => $tenant1->name,
            ],
            'Other tenants' => [
                $tenant2->id => $tenant2->name,
                $tenant3->id => $tenant3->name,
            ],
        ];
        $this->assertSame($expected, form::get_options());

        $user1 = $this->getDataGenerator()->create_user();
        role_assign($switchroleid, $user1->id, $syscontext);
        $this->setUser($user1);
        $expected = [
            '' => [
                0 => 'No tenant',
            ],
        ];
        $this->assertSame($expected, form::get_options());
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort4->id, $user1->id);
        $expected = [
            '' => [
                0 => 'No tenant',
            ],
            'My tenants' => [
                $tenant1->id => $tenant1->name,
                $tenant2->id => $tenant2->name,
            ],
        ];
        $this->assertSame($expected, form::get_options());

        $user2 = $this->getDataGenerator()->create_user();
        role_assign($switchroleid, $user2->id, $syscontext);
        role_assign($viewroleid, $user2->id, $syscontext);
        $this->setUser($user2);
        $expected = [
            '' => [
                0 => 'No tenant',
            ],
            'Tenants' => [
                $tenant1->id => $tenant1->name,
                $tenant2->id => $tenant2->name,
                $tenant3->id => $tenant3->name,
            ],
        ];
        $this->assertSame($expected, form::get_options());
        cohort_add_member($cohort1->id, $user2->id);
        $expected = [
            '' => [
                0 => 'No tenant',
            ],
            'My tenants' => [
                $tenant1->id => $tenant1->name,
            ],
            'Other tenants' => [
                $tenant2->id => $tenant2->name,
                $tenant3->id => $tenant3->name,
            ],
        ];
        $this->assertSame($expected, form::get_options());
    }
}
