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

namespace tool_muprog\phpunit\external\form_autocomplete;

use tool_muprog\external\form_autocomplete\source_manual_allocate_users;

/**
 * External API for program allocation candidate test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\phpunit\external\form_autocomplete\source_manual_allocate_users
 */
final class source_manual_allocate_users_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execution(): void {
        global $DB, $CFG;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'contextid' => $catcontext2->id, 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1']);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 2']);
        $user3 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 3']);
        $user4 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 4']);
        $user5 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 5']);

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user5->id, $catcontext2);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);

        $admin = get_admin();
        $this->setUser($admin);

        $CFG->maxusersperpage = 10;
        $result = source_manual_allocate_users::execute('', $program1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(4, $result['list']); // Admin is included.
        foreach ($result['list'] as $u) {
            $u = (object)$u;
            if ($u->value == $user3->id) {
                $this->assertStringContainsString(\fullname($user3, true), $u->label);
            } else if ($u->value == $user4->id) {
                $this->assertStringContainsString(\fullname($user4, true), $u->label);
            } else if ($u->value == $user5->id) {
                $this->assertStringContainsString(\fullname($user5, true), $u->label);
            } else if ($u->value == $admin->id) {
                $this->assertStringContainsString(\fullname($admin, true), $u->label);
            } else {
                $this->fail('Unexpected user returned: ' . $u->label);
            }
        }
        $result = source_manual_allocate_users::execute('Prijmeni', $program1->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(3, $result['list']); // Admin is NOT included.
        foreach ($result['list'] as $u) {
            $u = (object)$u;
            if ($u->value == $user3->id) {
                $this->assertStringContainsString(\fullname($user3, true), $u->label);
            } else if ($u->value == $user4->id) {
                $this->assertStringContainsString(\fullname($user4, true), $u->label);
            } else if ($u->value == $user5->id) {
                $this->assertStringContainsString(\fullname($user5, true), $u->label);
            } else {
                $this->fail('Unexpected user returned: ' . $u->label);
            }
        }

        $this->setUser($user5);
        try {
            source_manual_allocate_users::execute('', $program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf('required_capability_exception', $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Allocate users to programs).',
                $ex->getMessage()
            );
        }

        $this->setUser($user5);
        $CFG->maxusersperpage = 10;
        $result = source_manual_allocate_users::execute('', $program2->id);
        $this->assertFalse($result['overflow']);
        $this->assertCount(6, $result['list']);
    }

    public function test_execution_tenant(): void {
        global $DB, $CFG;

        if (!\tool_muprog\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $generator->create_program(['fullname' => 'prg0', 'sources' => ['manual' => []]]);
        $source0 = $DB->get_record('tool_muprog_source', ['programid' => $program0->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program1 = $generator->create_program(['idnumber' => 'prg2', 'contextid' => $tenant1catcontext->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'prg3', 'contextid' => $tenant2catcontext->id, 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $admin = get_admin();
        $user0 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 2', 'tenantid' => $tenant2->id]);

        $admin = get_admin();
        $this->setUser($admin);

        $result = source_manual_allocate_users::execute('', $program0->id);
        $this->assertEquals([$user0->id, $user1->id, $user2->id, $admin->id], array_column($result['list'], 'value'));

        $result = source_manual_allocate_users::execute('', $program1->id);
        $this->assertEquals([$user1->id], array_column($result['list'], 'value'));

        $result = source_manual_allocate_users::execute('', $program2->id);
        $this->assertEquals([$user2->id], array_column($result['list'], 'value'));

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant1->id);

        $result = source_manual_allocate_users::execute('', $program0->id);
        $this->assertEquals([$user1->id], array_column($result['list'], 'value'));

        $result = source_manual_allocate_users::execute('', $program1->id);
        $this->assertEquals([$user1->id], array_column($result['list'], 'value'));

        $result = source_manual_allocate_users::execute('', $program2->id);
        $this->assertEquals([$user2->id], array_column($result['list'], 'value'));
    }
}
