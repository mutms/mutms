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

namespace tool_mutenancy\phpunit\patch;

use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy upstream patch test.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core_role_potential_assignees_course_and_above
 */
final class core_role_potential_assignees_course_and_above_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::find_users
     */
    public function test_find_users(): void {
        global $CFG, $DB;
        require_once("$CFG->dirroot/admin/roles/classes/admins_potential_selector.php");

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $manager = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $syscontext = \context_system::instance();
        $category0 = $DB->get_record('course_categories', ['parent' => 0], '*', MUST_EXIST);
        $categorycontext0 = \context_coursecat::instance($category0->id);

        $admin = get_admin();
        $user0 = $this->getDataGenerator()->create_user([
            'firstname' => 'First',
            'lastname' => 'User',
            'email' => 'user0@example.com',
        ]);

        $selector = new \core_role_potential_assignees_course_and_above('xxx', ['roleid' => $manager->id, 'context' => $syscontext]);
        $result = $selector->find_users('');
        $this->assertSame('First', $result['Potential users'][$user0->id]->firstname);
        $this->assertSame('Admin', $result['Potential users'][$admin->id]->firstname);
        $this->assertCount(2, $result['Potential users']);

        $result = $selector->find_users('First');
        $this->assertSame('First', $result['Potential users matching \'First\''][$user0->id]->firstname);
        $this->assertCount(1, $result['Potential users matching \'First\'']);

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => 'Prvni',
            'lastname' => 'Tenant',
            'email' => 'tenant1@example.com',
            'tenantid' => $tenant1->id,
        ]);
        $usercontext1 = \context_user::instance($user1->id);
        $category1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $categorycontext1 = \context_coursecat::instance($category1->id);

        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => 'Druhy',
            'lastname' => 'Tenant',
            'email' => 'tenant2@example.com',
            'tenantid' => $tenant2->id,
        ]);
        $usercontext2 = \context_user::instance($user2->id);
        $category2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $categorycontext2 = \context_coursecat::instance($category2->id);

        $user3 = $this->getDataGenerator()->create_user([
            'firstname' => 'Third',
            'lastname' => 'User',
            'email' => 'user3@example.com',
        ]);
        cohort_add_member($cohort1->id, $user3->id);
        $user4 = $this->getDataGenerator()->create_user([
            'firstname' => 'Fourth',
            'lastname' => 'User',
            'email' => 'user4@example.com',
        ]);
        \tool_mutenancy\local\manager::add($tenant1->id, $user4->id);

        $selector = new \core_role_potential_assignees_course_and_above('xxx', ['roleid' => $manager->id, 'context' => $syscontext]);
        $result = $selector->find_users('');
        $this->assertSame('First', $result['Potential users'][$user0->id]->firstname);
        $this->assertSame('Admin', $result['Potential users'][$admin->id]->firstname);
        $this->assertSame('Prvni', $result['Potential users'][$user1->id]->firstname);
        $this->assertSame('Druhy', $result['Potential users'][$user2->id]->firstname);
        $this->assertSame('Third', $result['Potential users'][$user3->id]->firstname);
        $this->assertSame('Fourth', $result['Potential users'][$user4->id]->firstname);
        $this->assertCount(6, $result['Potential users']);

        $selector = new \core_role_potential_assignees_course_and_above('xxx', ['roleid' => $manager->id, 'context' => $tenantcontext1]);
        $result = $selector->find_users('');
        $this->assertSame('Prvni', $result['Potential users'][$user1->id]->firstname);
        $this->assertSame('Third', $result['Potential users'][$user3->id]->firstname);
        $this->assertCount(2, $result['Potential users']);

        $selector = new \core_role_potential_assignees_course_and_above('xxx', ['roleid' => $manager->id, 'context' => $tenantcontext2]);
        $result = $selector->find_users('');
        $this->assertSame('Druhy', $result['Potential users'][$user2->id]->firstname);
        $this->assertCount(1, $result['Potential users']);
    }
}
