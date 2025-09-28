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

namespace tool_mulib\phpunit\privacy;

use tool_mulib\privacy\provider;
use core_privacy\local\request\writer;

/**
 * Privacy provider tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Set up some test data.
     *
     * @return array users and notifications.
     */
    public function set_up_data(): array {
        global $DB;

        $now = \core\di::get(\core\clock::class)->time();

        $notification1 = (object)[
            'component' => 'tool_xyz',
            'notificationtype' => 'user_is_happy',
            'instanceid' => 123,
        ];
        $notification1->id = $DB->insert_record('tool_mulib_notification', $notification1);

        $notification2 = (object)[
            'component' => 'tool_xyz',
            'notificationtype' => 'user_is_sad',
            'instanceid' => 321,
        ];
        $notification2->id = $DB->insert_record('tool_mulib_notification', $notification2);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $notifications = [];

        $notifications[$user1->id][] = $DB->insert_record('tool_mulib_notification_user', [
            'notificationid' => $notification1->id,
            'userid' => $user1->id,
            'timenotified' => $now - DAYSECS,
        ]);
        $notifications[$user1->id][] = $DB->insert_record('tool_mulib_notification_user', [
            'notificationid' => $notification1->id,
            'userid' => $user1->id,
            'timenotified' => $now - HOURSECS,
        ]);
        $notifications[$user2->id][] = $DB->insert_record('tool_mulib_notification_user', [
            'notificationid' => $notification2->id,
            'userid' => $user1->id,
            'timenotified' => $now - DAYSECS,
        ]);
        $notifications[$user2->id][] = $DB->insert_record('tool_mulib_notification_user', [
            'notificationid' => $notification1->id,
            'userid' => $user2->id,
            'timenotified' => $now - HOURSECS,
        ]);

        return [[$user1, $user2], $notifications];
    }

    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('tool_mulib'));

        $itemcollection = $collection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('tool_mulib_notification_user', $table->get_name());

        // Make sure lang strings exist.
        get_string($table->get_summary(), 'tool_mulib');
        foreach ($table->get_privacy_fields() as $str) {
            get_string($str, 'tool_mulib');
        }
    }

    public function test_get_contexts_for_userid(): void {
        $admin = get_admin();

        $syscontext = \context_system::instance();

        $list = provider::get_contexts_for_userid($admin->id);
        $this->assertSame([(string)$syscontext->id], $list->get_contextids());
    }

    public function test_export_user_data(): void {
        [$users, $notifications] = $this->set_up_data();
        $syscontext = \context_system::instance();

        $subcontexts = [get_string('privacy:metadata:tool_mulib_notification_user:tableexplanation', 'tool_mulib')];

        $writer = writer::with_context($syscontext);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($users[0]->id, $syscontext, 'tool_mulib');
        $data = $writer->get_related_data($subcontexts, 'data');
        $this->assertCount(3, $data);
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        [$users, $notifications] = $this->set_up_data();
        $syscontext = \context_system::instance();
        $usercontext1 = \context_user::instance($users[0]->id);

        $this->assertSame(4, $DB->count_records('tool_mulib_notification_user', []));

        provider::delete_data_for_all_users_in_context($usercontext1);
        $this->assertSame(4, $DB->count_records('tool_mulib_notification_user', []));

        provider::delete_data_for_all_users_in_context($syscontext);
        $this->assertSame(0, $DB->count_records('tool_mulib_notification_user', []));
    }

    public function test_delete_data_for_user(): void {
        global $DB;

        [$users, $notifications] = $this->set_up_data();
        $syscontext = \context_system::instance();
        $usercontext1 = \context_user::instance($users[0]->id);

        $this->assertSame(4, $DB->count_records('tool_mulib_notification_user', []));

        $list = new \core_privacy\local\request\approved_contextlist($users[0], 'tool_mulib', [$usercontext1->id]);
        provider::delete_data_for_user($list);
        $this->assertSame(4, $DB->count_records('tool_mulib_notification_user', []));

        $list = new \core_privacy\local\request\approved_contextlist($users[0], 'tool_mulib', [$syscontext->id]);
        provider::delete_data_for_user($list);
        $this->assertSame(1, $DB->count_records('tool_mulib_notification_user', []));
    }

    public function test_get_users_in_context(): void {
        [$users, $notifications] = $this->set_up_data();
        $user3 = $this->getDataGenerator()->create_user();

        $syscontext = \context_system::instance();
        $usercontext1 = \context_user::instance($users[0]->id);

        $userlist = new \core_privacy\local\request\userlist($syscontext, 'tool_mulib');
        provider::get_users_in_context($userlist);
        $this->assertSame([(int)$users[0]->id, (int)$users[1]->id], $userlist->get_userids());

        $userlist = new \core_privacy\local\request\userlist($usercontext1, 'tool_mulib');
        provider::get_users_in_context($userlist);
        $this->assertSame([], $userlist->get_userids());
    }

    public function test_delete_data_for_users(): void {
        global $DB;

        [$users, $notifications] = $this->set_up_data();

        $syscontext = \context_system::instance();
        $usercontext1 = \context_user::instance($users[0]->id);

        $this->assertSame(4, $DB->count_records('tool_mulib_notification_user', []));

        $userlist = new \core_privacy\local\request\approved_userlist($usercontext1, 'tool_mulib', [$users[0]->id]);
        provider::delete_data_for_users($userlist);
        $this->assertSame(4, $DB->count_records('tool_mulib_notification_user', []));

        $userlist = new \core_privacy\local\request\approved_userlist($syscontext, 'tool_mulib', [$users[0]->id]);
        provider::delete_data_for_users($userlist);
        $this->assertSame(1, $DB->count_records('tool_mulib_notification_user', []));
    }
}
