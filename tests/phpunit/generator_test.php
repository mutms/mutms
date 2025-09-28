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

namespace tool_mutenancy\phpunit;

/**
 * Multi-tenancy generator tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::create_tenant
     */
    public function test_create_tenant(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $this->assertInstanceOf(\tool_mutenancy_generator::class, $generator);

        $syscontext = \context_system::instance();

        $this->setCurrentTimeStart();
        $tenant1 = $generator->create_tenant([]);
        $this->assertSame('Tenant 1', $tenant1->name);
        $this->assertSame('ten1', $tenant1->idnumber);
        $this->assertSame('0', $tenant1->loginshow);
        $this->assertSame(null, $tenant1->memberlimit);
        $this->assertSame(null, $tenant1->assoccohortid);
        $this->assertSame(null, $tenant1->sitefullname);
        $this->assertSame(null, $tenant1->siteshortname);
        $this->assertSame('0', $tenant1->archived);
        $this->assertTimeCurrent($tenant1->timecreated);
        $this->assertSame($tenant1->timecreated, $tenant1->timemodified);
        $tenantcontext = \context_tenant::instance($tenant1->id);

        $category = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $this->assertSame('Tenant 1', $category->name);
        $this->assertSame('', $category->idnumber);
        $this->assertSame('', $category->description);
        $this->assertSame('0', $category->parent);
        $this->assertSame('1', $category->visible);
        $catcontext = \context_coursecat::instance($category->id);
        $this->assertSame((int)$tenant1->id, $catcontext->tenantid);

        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('Tenant users: Tenant 1', $cohort->name);
        $this->assertSame('', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('tool_mutenancy', $cohort->component);

        $this->assertTrue(\tool_mutenancy\local\tenancy::is_active());

        $acohort = $this->getDataGenerator()->create_cohort();

        $this->setCurrentTimeStart();
        $tenant2 = $generator->create_tenant([
            'name' => 'Muj tenant',
            'idnumber' => 'xt33',
            'loginshow' => 1,
            'memberlimit' => 12,
            'assoccohortid' => $acohort->id,
            'sitefullname' => 'ABCDE',
            'siteshortname' => 'abc',
        ]);
        $this->assertSame('Muj tenant', $tenant2->name);
        $this->assertSame('xt33', $tenant2->idnumber);
        $this->assertSame('1', $tenant2->loginshow);
        $this->assertSame('12', $tenant2->memberlimit);
        $this->assertSame($acohort->id, $tenant2->assoccohortid);
        $this->assertSame('ABCDE', $tenant2->sitefullname);
        $this->assertSame('abc', $tenant2->siteshortname);
        $this->assertSame('0', $tenant2->archived);
        $this->assertTimeCurrent($tenant2->timecreated);
        $this->assertSame($tenant2->timecreated, $tenant2->timemodified);

        $tenant3 = $generator->create_tenant(['archived' => 1]);
        $this->assertSame('1', $tenant3->archived);

        $category4 = $this->getDataGenerator()->create_category();
        $tenant4 = $generator->create_tenant(['categoryid' => $category4->id]);
        $this->assertSame($category4->id, $tenant4->categoryid);

        $category5 = $this->getDataGenerator()->create_category(['idnumber' => 'xyz5']);
        $tenant5 = $generator->create_tenant(['category' => $category5->idnumber]);
        $this->assertSame($category5->id, $tenant5->categoryid);
    }

    /**
     * @covers ::create_tenant_manager
     */
    public function test_create_tenant_manager(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant = $generator->create_tenant();

        $user = $this->getDataGenerator()->create_user([]);
        $generator->create_tenant_manager(['tenantid' => $tenant->id, 'userid' => $user->id]);
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant->id, 'userid' => $user->id]));
    }

    /**
     * @covers \testing_data_generator::create_user
     */
    public function test_create_user(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user([]);
        $this->assertSame(null, $user0->tenantid);
        $context0 = \context_user::instance($user0->id);
        $this->assertSame(null, $context0->tenantid);

        $user1 = $this->getDataGenerator()->create_user([
            'tenantid' => $tenant->id,
        ]);
        $this->assertSame($tenant->id, $user1->tenantid);
        $context1 = \context_user::instance($user1->id);
        $this->assertSame((int)$tenant->id, $context1->tenantid);

        $user2 = $this->getDataGenerator()->create_user([
            'tenant' => $tenant->idnumber,
        ]);
        $this->assertSame($tenant->id, $user2->tenantid);
        $context2 = \context_user::instance($user2->id);
        $this->assertSame((int)$tenant->id, $context2->tenantid);
    }
}
