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

namespace tool_mulib\phpunit\external\form_autocomplete;

use tool_mulib\external\form_autocomplete\user;
use tool_mulib\local\mulib;

/**
 * MuTMS helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\external\form_autocomplete\user
 */
final class user_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_tenant_related_users_where(): void {
        global $DB;

        $user0 = $this->getDataGenerator()->create_user();
        $syscontext = \context_system::instance();
        $result = user::get_tenant_related_users_where('u.id', $syscontext);
        $this->assertSame("", $result);

        $result = user::get_tenant_related_users_where('u.id', $syscontext, '');
        $this->assertSame("1=1", $result);

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        \tool_mutenancy\local\tenancy::activate();

        $cohort2 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        cohort_add_member($cohort2->id, $user0->id);

        $result = user::get_tenant_related_users_where('u.id', $syscontext);
        $this->assertSame("", $result);

        $result = user::get_tenant_related_users_where('u.id', $syscontext, '');
        $this->assertSame("1=1", $result);

        $result = user::get_tenant_related_users_where('u.id', $tenantcontext1, '');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE $result
              ORDER BY u.id ASC";
        $this->assertEquals([$user1->id], array_keys($DB->get_records_sql($sql)));

        $result = user::get_tenant_related_users_where('u.id', $tenantcontext2, '');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE $result
              ORDER BY u.id ASC";
        $this->assertEquals([$user0->id, $user2->id], array_keys($DB->get_records_sql($sql)));

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);

        $result = user::get_tenant_related_users_where('u.id', $syscontext, '');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE $result
              ORDER BY u.id ASC";
        $this->assertEquals([$user0->id, $user2->id], array_keys($DB->get_records_sql($sql)));

        $result = user::get_tenant_related_users_where('u.id', $tenantcontext1, '');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE $result
              ORDER BY u.id ASC";
        $this->assertEquals([$user1->id], array_keys($DB->get_records_sql($sql)));

        $result = user::get_tenant_related_users_where('u.id', $tenantcontext2, '');
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE $result
              ORDER BY u.id ASC";
        $this->assertEquals([$user0->id, $user2->id], array_keys($DB->get_records_sql($sql)));
    }

    public function test_validate_tenant_relation(): void {
        $admin = get_admin();
        $user0 = $this->getDataGenerator()->create_user();
        $syscontext = \context_system::instance();
        $this->assertNull(user::validate_tenant_relation($user0, $syscontext));

        if (!mulib::is_mutenancy_available()) {
            return;
        }

        \tool_mutenancy\local\tenancy::activate();

        $cohort2 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenantgenerator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        cohort_add_member($cohort2->id, $user0->id);

        $this->assertNull(user::validate_tenant_relation($admin, $syscontext));
        $this->assertNull(user::validate_tenant_relation($user0, $syscontext));
        $this->assertNull(user::validate_tenant_relation($user1, $syscontext));
        $this->assertNull(user::validate_tenant_relation($user2, $syscontext));

        $this->assertSame('Error', user::validate_tenant_relation($admin, $tenantcontext1));
        $this->assertSame('Error', user::validate_tenant_relation($user0, $tenantcontext1));
        $this->assertNull(user::validate_tenant_relation($user1, $tenantcontext1));
        $this->assertSame('Error', user::validate_tenant_relation($user2, $tenantcontext1));

        $this->assertSame('Error', user::validate_tenant_relation($admin, $tenantcontext2));
        $this->assertNull(user::validate_tenant_relation($user0, $tenantcontext2));
        $this->assertSame('Error', user::validate_tenant_relation($user1, $tenantcontext2));
        $this->assertNull(user::validate_tenant_relation($user2, $tenantcontext2));

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);

        $this->assertSame('Error', user::validate_tenant_relation($admin, $syscontext));
        $this->assertNull(user::validate_tenant_relation($user0, $syscontext));
        $this->assertSame('Error', user::validate_tenant_relation($user1, $syscontext));
        $this->assertNull(user::validate_tenant_relation($user2, $syscontext));

        $this->assertSame('Error', user::validate_tenant_relation($admin, $tenantcontext1));
        $this->assertSame('Error', user::validate_tenant_relation($user0, $tenantcontext1));
        $this->assertNull(user::validate_tenant_relation($user1, $tenantcontext1));
        $this->assertSame('Error', user::validate_tenant_relation($user2, $tenantcontext1));

        $this->assertSame('Error', user::validate_tenant_relation($admin, $tenantcontext2));
        $this->assertNull(user::validate_tenant_relation($user0, $tenantcontext2));
        $this->assertSame('Error', user::validate_tenant_relation($user1, $tenantcontext2));
        $this->assertNull(user::validate_tenant_relation($user2, $tenantcontext2));
    }

    public function test_format_label(): void {
        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();

        $fullname = fullname($user1);
        $expected = "<span>
    <span>$fullname</span>
    <small>
    </small>
</span>";
        $this->assertSame($expected, user::format_label($user1, $syscontext));
    }
}
