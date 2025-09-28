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

namespace tool_mutenancy\phpunit\privacy;

use tool_mutenancy\privacy\provider;
use core_privacy\local\request\writer;

/**
 * Privacy provider tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Set up some test data.
     *
     * @return array users and tenants.
     */
    public function set_up_data(): array {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id]);

        \tool_mutenancy\local\manager::set_userids($tenant1->id, [$user1->id, $user2->id]);
        \tool_mutenancy\local\manager::set_userids($tenant2->id, [$user1->id]);

        return [[$user1, $user2, $user3], [$tenant1, $tenant2, $tenant3]];
    }

    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('tool_mutenancy'));

        $itemcollection = $collection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('tool_mutenancy_manager', $table->get_name());

        // Make sure lang strings exist.
        get_string($table->get_summary(), 'tool_mutenancy');
        foreach ($table->get_privacy_fields() as $str) {
            get_string($str, 'tool_mutenancy');
        }
    }

    public function test_get_contexts_for_userid(): void {
        $admin = get_admin();

        $list = provider::get_contexts_for_userid($admin->id);
        $this->assertSame([], $list->get_contextids());

        [$users, $tenants] = $this->set_up_data();
        $tenant1 = $tenants[0];
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenants[1];
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant3 = $tenants[2];
        $tenant3context = \context_tenant::instance($tenant3->id);

        $list = provider::get_contexts_for_userid($users[0]->id);
        $contextids = $list->get_contextids();
        asort($contextids);
        $this->assertSame([(string)$tenant1context->id, (string)$tenant2context->id], $contextids);

        $list = provider::get_contexts_for_userid($users[1]->id);
        $contextids = $list->get_contextids();
        asort($contextids);
        $this->assertSame([(string)$tenant1context->id], $contextids);

        $list = provider::get_contexts_for_userid($users[2]->id);
        $contextids = $list->get_contextids();
        $this->assertSame([], $contextids);
    }

    public function test_export_user_data(): void {
        [$users, $tenants] = $this->set_up_data();
        $tenant1 = $tenants[0];
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenants[1];
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant3 = $tenants[2];
        $tenant3context = \context_tenant::instance($tenant3->id);

        $strtenantmanager = get_string('tenant_manager', 'tool_mutenancy');

        $writer = writer::with_context($tenant1context);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($users[0]->id, $tenant1context, 'tool_mutenancy');
        $data = $writer->get_related_data([$strtenantmanager], 'data');
        $this->assertCount(1, (array)$data);

        $writer = writer::with_context($tenant2context);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($users[0]->id, $tenant2context, 'tool_mutenancy');
        $data = $writer->get_related_data([$strtenantmanager], 'data');
        $this->assertCount(1, (array)$data);

        $writer = writer::with_context($tenant3context);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($users[0]->id, $tenant3context, 'tool_mutenancy');
        $data = $writer->get_related_data([$strtenantmanager], 'data');
        $this->assertSame([], $data);
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        [$users, $tenants] = $this->set_up_data();
        $tenant1 = $tenants[0];
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenants[1];
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant3 = $tenants[2];
        $tenant3context = \context_tenant::instance($tenant3->id);

        $this->assertSame(3, $DB->count_records('tool_mutenancy_manager', []));

        provider::delete_data_for_all_users_in_context($tenant1context);
        $this->assertSame(1, $DB->count_records('tool_mutenancy_manager', []));
    }

    public function test_delete_data_for_user(): void {
        global $DB;
        [$users, $tenants] = $this->set_up_data();
        $tenant1 = $tenants[0];
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenants[1];
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant3 = $tenants[2];
        $tenant3context = \context_tenant::instance($tenant3->id);

        $this->assertSame(3, $DB->count_records('tool_mutenancy_manager', []));

        $list = new \core_privacy\local\request\approved_contextlist($users[0], 'tool_mutenancy', [$tenant1context->id]);
        provider::delete_data_for_user($list);
        $this->assertSame(2, $DB->count_records('tool_mutenancy_manager', []));
    }

    public function test_get_users_in_context(): void {
        [$users, $tenants] = $this->set_up_data();
        $tenant1 = $tenants[0];
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenants[1];
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant3 = $tenants[2];
        $tenant3context = \context_tenant::instance($tenant3->id);

        $userlist = new \core_privacy\local\request\userlist($tenant1context, 'tool_mutenancy');
        provider::get_users_in_context($userlist);
        $this->assertSame([(int)$users[0]->id, (int)$users[1]->id], $userlist->get_userids());

        $userlist = new \core_privacy\local\request\userlist($tenant3context, 'tool_mutenancy');
        provider::get_users_in_context($userlist);
        $this->assertSame([], $userlist->get_userids());
    }

    public function test_delete_data_for_users(): void {
        global $DB;
        [$users, $tenants] = $this->set_up_data();
        $tenant1 = $tenants[0];
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant2 = $tenants[1];
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant3 = $tenants[2];
        $tenant3context = \context_tenant::instance($tenant3->id);

        $this->assertSame(3, $DB->count_records('tool_mutenancy_manager', []));

        $userlist = new \core_privacy\local\request\approved_userlist($tenant1context, 'tool_mutenancy', [$users[0]->id]);
        provider::delete_data_for_users($userlist);
        $this->assertSame(2, $DB->count_records('tool_mutenancy_manager', []));
    }
}
