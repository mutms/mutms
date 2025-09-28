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

namespace tool_muprog\phpunit\local;

use tool_muprog\local\notification_manager;

/**
 * Program notification manager test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\notification_manager
 */
final class notification_manager_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_all_types(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        // Let's call all methods to make sure there are no missing strings and fatal errors.,
        // the actual returned values need to be tested elsewhere.

        // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
        /** @var class-string<\tool_muprog\local\notification\base> $classname */
        $types = notification_manager::get_all_types();
        foreach ($types as $type => $classname) {
            $this->assertSame('tool_muprog', $classname::get_component());
            $this->assertSame($type, $classname::get_notificationtype());
            $classname::get_provider();
            $classname::get_name();
            $classname::get_description();
            $classname::get_default_subject();
            $classname::get_default_body();
            $this->assertSame(-10, $classname::get_notifier($program1, $allocation1)->id);
            $classname::get_allocation_placeholders($program1, $source1, $allocation1, $user1);
            $generator->create_program_notification(['notificationtype' => $type, 'programid' => $program1->id]);
            $classname::notify_users(null, null);
            $classname::notify_users($program1, $user1);
            $classname::delete_allocation_notifications($allocation1);
        }
    }

    public function test_get_candidate_types(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);

        $alltypes = notification_manager::get_all_types();
        $candidates = notification_manager::get_candidate_types($program1->id);
        foreach ($candidates as $type => $name) {
            $this->assertIsString($name);
            $this->assertArrayHasKey($type, $alltypes);
        }
        $this->assertArrayHasKey('allocation', $candidates);

        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program1->id]);
        $candidates = notification_manager::get_candidate_types($program1->id);
        $this->assertArrayNotHasKey('allocation', $candidates);
    }

    public function test_get_instance_context(): void {
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);

        $context = notification_manager::get_instance_context($program1->id);
        $this->assertInstanceOf(\context::class, $context);
        $this->assertEquals($program1->contextid, $context->id);
    }

    public function test_can_view(): void {
        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);
        $this->assertTrue(notification_manager::can_view($program1->id));

        $this->setUser($user2);
        $this->assertFalse(notification_manager::can_view($program1->id));

        $this->setAdminUser();
        $this->assertTrue(notification_manager::can_view($program1->id));
    }

    public function test_can_manage(): void {
        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);
        $this->assertTrue(notification_manager::can_manage($program1->id));

        $this->setUser($user2);
        $this->assertFalse(notification_manager::can_manage($program1->id));

        $this->setAdminUser();
        $this->assertTrue(notification_manager::can_manage($program1->id));
    }

    public function test_get_instance_name(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);

        $this->assertSame('hokus', notification_manager::get_instance_name($program1->id));
    }

    public function test_get_instance_management_url(): void {
        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program1 = $generator->create_program(['contextid' => $catcontext1->id, 'fullname' => 'hokus']);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muprog/management/program_notifications.php?id=' . $program1->id,
            notification_manager::get_instance_management_url($program1->id)->out(false)
        );

        $this->setUser($user2);
        $this->assertSame(null, notification_manager::get_instance_management_url($program1->id));

        $this->setAdminUser();
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muprog/management/program_notifications.php?id=' . $program1->id,
            notification_manager::get_instance_management_url($program1->id)->out(false)
        );
    }

    public function test_trigger_notifications(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $types = notification_manager::get_all_types();
        foreach ($types as $type => $classname) {
            $generator->create_program_notification(['notificationtype' => $type, 'programid' => $program1->id]);
        }

        notification_manager::trigger_notifications(null, null);
        notification_manager::trigger_notifications($program1->id, $user1->id);
    }

    public function test_delete_allocation_notifications(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['fullname' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'start', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program2->id]);

        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user2->id]);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_muprog\local\source\manual::allocate_users($program2->id, $source2->id, [$user1->id]);
        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(5, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(3, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        notification_manager::delete_allocation_notifications($allocation3);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        notification_manager::delete_allocation_notifications($allocation1);
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        notification_manager::delete_allocation_notifications($allocation2);
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));
    }

    public function test_delete_program_notifications(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['fullname' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'start', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program2->id]);

        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user2->id]);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program2->id, $source2->id, [$user1->id]);
        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(5, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(3, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        notification_manager::delete_program_notifications($program2);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        notification_manager::delete_program_notifications($program1);
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));
    }

    public function test_get_timenotified(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['fullname' => 'pokus', 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'completion', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program2->id]);

        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        $this->setCurrentTimeStart();
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user2->id]);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program2->id, $source2->id, [$user1->id]);
        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $this->assertTimeCurrent(notification_manager::get_timenotified($user1->id, $program1->id, 'allocation'));
        $this->assertTimeCurrent(notification_manager::get_timenotified($user1->id, $program2->id, 'allocation'));
        $this->assertTimeCurrent(notification_manager::get_timenotified($user2->id, $program1->id, 'allocation'));
        $this->assertNull(notification_manager::get_timenotified($user1->id, $program1->id, 'completion'));
        $this->assertNull(notification_manager::get_timenotified($user1->id, $program2->id, 'completion'));
        $this->assertNull(notification_manager::get_timenotified($user2->id, $program1->id, 'completion'));
    }

    /**
     * @covers ::is_import_supported
     */
    public function test_is_notification_supported(): void {
        $this->assertTrue(notification_manager::is_import_supported());
    }

    /**
     * @covers \tool_mulib\local\notification\util::notification_import
     */
    public function test_notification_util_notification_import(): void {
        global $DB;

        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $syscontext = \context_system::instance();
        $cohort1 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program([
            'fullname' => 'hokus',
            'idnumber' => 'p1',
            'description' => 'some desc 1',
            'descriptionformat' => \FORMAT_MARKDOWN,
            'publicaccess' => 1,
            'archived' => 0,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
            'cohorts' => [$cohort1->id],
        ]);
        $notification1 = $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program1->id,
            'custom' => 1, 'subject' => 'You are allocated', 'body' => 'Welcome to the program']);
        $notification2 = $generator->create_program_notification(['notificationtype' => 'endsoon', 'programid' => $program1->id]);
        $program2 = $generator->create_program([
            'fullname' => 'pokus',
            'idnumber' => 'p2',
            'description' => 'some desc 2',
            'descriptionformat' => \FORMAT_MARKDOWN,
            'publicaccess' => 1,
            'archived' => 0,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
            'cohorts' => [$cohort1->id],
        ]);
        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program2->id]);
        $notificationsbeforeimport = $DB->get_records('tool_mulib_notification');
        $this->assertCount(3, $notificationsbeforeimport);
        \tool_mulib\local\notification\util::notification_import(
            (object)['component' => 'tool_muprog', 'instanceid' => $program2->id, 'frominstance' => $program1->id],
            [$notification1->id, $notification2->id]
        );
        $notificationsafterimport = $DB->get_records('tool_mulib_notification');
        $this->assertCount(4, $notificationsafterimport);
        $allocationnotificationprogram2 = $DB->get_record(
            'tool_mulib_notification',
            ['component' => 'tool_muprog', 'notificationtype' => 'allocation', 'instanceid' => $program2->id]
        );
        $this->assertSame('{"subject":"You are allocated","body":"Welcome to the program"}', $allocationnotificationprogram2->customjson);
    }
}
