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

use tool_mutenancy\external\create_tenant;

/**
 * Multi-tenancy external function tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\external\create_tenant
 */
final class create_tenant_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_definition(): void {
        $function = \core_external\external_api::external_function_info('tool_mutenancy_create_tenant');
        $this->assertSame(create_tenant::class, $function->classname);
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

        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext->id);

        \tool_mutenancy\local\tenancy::activate();

        $this->setUser($manager);

        $tenant1 = create_tenant::execute([
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ]);
        $tenant1 = (object)create_tenant::clean_returnvalue(create_tenant::execute_returns(), $tenant1);

        $this->assertSame('Some tenant 1', $tenant1->name);
        $this->assertSame('t1', $tenant1->idnumber);
        $this->assertSame(false, $tenant1->loginshow);
        $this->assertSame(null, $tenant1->memberlimit);
        $this->assertNull($tenant1->assoccohortid);
        $this->assertSame(null, $tenant1->sitefullname);
        $this->assertSame(null, $tenant1->siteshortname);
        $this->assertSame(false, $tenant1->archived);
        $category = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $this->assertSame('Some tenant 1', $category->name);
        $this->assertSame('', $category->idnumber);
        $this->assertSame('', $category->description);
        $this->assertSame('0', $category->parent);
        $this->assertSame('1', $category->visible);
        $catcontext = \context_coursecat::instance($category->id);
        $this->assertSame((int)$tenant1->id, $catcontext->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('Tenant users: Some tenant 1', $cohort->name);
        $this->assertSame('', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('tool_mutenancy', $cohort->component);

        $tenant2 = create_tenant::execute([
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
            'loginshow' => 1,
            'memberlimit' => 11,
            'assoccohortcreate' => 1,
            'sitefullname' => 'site full n2',
            'siteshortname' => 'sfn2',
            'categoryname' => 'New cat 2',
            'categoryidnumber' => 'NC2',
            'cohortname' => 'New kohorta 2',
            'cohortidnumber' => 'NK2',
        ]);
        $tenant2 = (object)create_tenant::clean_returnvalue(create_tenant::execute_returns(), $tenant2);

        $this->assertSame('Some tenant 2', $tenant2->name);
        $this->assertSame('t2', $tenant2->idnumber);
        $this->assertSame(true, $tenant2->loginshow);
        $this->assertSame(11, $tenant2->memberlimit);
        $this->assertSame('site full n2', $tenant2->sitefullname);
        $this->assertSame('sfn2', $tenant2->siteshortname);
        $this->assertSame(false, $tenant2->archived);
        $tenantcontext = \context_tenant::instance($tenant2->id);
        $category = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $this->assertSame('New cat 2', $category->name);
        $this->assertSame('NC2', $category->idnumber);
        $this->assertSame('', $category->description);
        $this->assertSame('0', $category->parent);
        $this->assertSame('1', $category->visible);
        $catcontext = \context_coursecat::instance($category->id);
        $this->assertSame((int)$tenant2->id, $catcontext->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $tenant2->cohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('New kohorta 2', $cohort->name);
        $this->assertSame('NK2', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('tool_mutenancy', $cohort->component);
        $cohort = $DB->get_record('cohort', ['id' => $tenant2->assoccohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('Associated users: Some tenant 2', $cohort->name);
        $this->assertSame('', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('', $cohort->component);

        $this->setUser($user);
        try {
            create_tenant::execute([
                'name' => 'Some tenant 1',
                'idnumber' => 't1',
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }
    }
}
