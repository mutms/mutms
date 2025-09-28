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

namespace tool_mucertify\phpunit\local;

use tool_mucertify\local\source\manual;

/**
 * Certification notification manager test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\certificate
 */
final class notification_manager_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_all_types(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['programid1' => $program1->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $assignment1 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );

        // Let's call all methods to make sure there are no missing strings and fatal errors.,
        // the actual returned values need to be tested elsewhere.

        $types = \tool_mucertify\local\notification_manager::get_all_types();
        // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingForeach
        /** @var class-string<\tool_mucertify\local\notification\base> $classname */
        foreach ($types as $type => $classname) {
            $this->assertSame('tool_mucertify', $classname::get_component());
            $this->assertSame($type, $classname::get_notificationtype());
            $classname::get_provider();
            $classname::get_name();
            $classname::get_description();
            $classname::get_default_subject();
            $classname::get_default_body();
            $this->assertSame(-10, $classname::get_notifier($certification1, $assignment1)->id);
            $classname::get_period_placeholders($certification1, $source1, $assignment1, $period1, $user1);
            $classname::get_assignment_placeholders($certification1, $source1, $assignment1, $user1);
            $generator->create_certifiction_notification(['notificationtype' => $type, 'certificationid' => $certification1->id]);
            $classname::notify_users(null, null);
            $classname::notify_users($program1, $user1);
            $classname::delete_period_notifications($assignment1);
            $classname::delete_assignment_notifications($assignment1);
        }
    }

    public function test_get_candidate_types(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $alltypes = \tool_mucertify\local\notification_manager::get_all_types();
        $candidates = \tool_mucertify\local\notification_manager::get_candidate_types($certification1->id);
        foreach ($candidates as $type => $name) {
            $this->assertIsString($name);
            $this->assertArrayHasKey($type, $alltypes);
        }
        $this->assertArrayHasKey('assignment', $candidates);

        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification1->id]);
        $candidates = \tool_mucertify\local\notification_manager::get_candidate_types($certification1->id);
        $this->assertArrayNotHasKey('assignment', $candidates);
    }

    public function test_get_instance_context(): void {
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['contextid' => $catcontext1->id, 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $context = \tool_mucertify\local\notification_manager::get_instance_context($certification1->id);
        $this->assertInstanceOf(\context::class, $context);
        $this->assertEquals($certification1->contextid, $context->id);
    }

    public function test_can_view(): void {
        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['contextid' => $catcontext1->id, 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);
        \role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);
        $this->assertTrue(\tool_mucertify\local\notification_manager::can_view($certification1->id));

        $this->setUser($user2);
        $this->assertFalse(\tool_mucertify\local\notification_manager::can_view($certification1->id));

        $this->setAdminUser();
        $this->assertTrue(\tool_mucertify\local\notification_manager::can_view($certification1->id));
    }

    public function test_can_manage(): void {
        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['contextid' => $catcontext1->id, 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mucertify:edit', CAP_ALLOW, $viewerroleid, $syscontext);
        \role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);
        $this->assertTrue(\tool_mucertify\local\notification_manager::can_manage($certification1->id));

        $this->setUser($user2);
        $this->assertFalse(\tool_mucertify\local\notification_manager::can_manage($certification1->id));

        $this->setAdminUser();
        $this->assertTrue(\tool_mucertify\local\notification_manager::can_manage($certification1->id));
    }

    public function test_get_instance_name(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['fullname' => 'Pokus', 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $this->assertSame('Pokus', \tool_mucertify\local\notification_manager::get_instance_name($certification1->id));
    }

    public function test_get_instance_management_url(): void {
        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['contextid' => $catcontext1->id, 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);
        \role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/mucertify/management/certification_notifications.php?id=' . $certification1->id,
            \tool_mucertify\local\notification_manager::get_instance_management_url($certification1->id)->out(false)
        );

        $this->setUser($user2);
        $this->assertSame(null, \tool_mucertify\local\notification_manager::get_instance_management_url($certification1->id));

        $this->setAdminUser();
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/mucertify/management/certification_notifications.php?id=' . $certification1->id,
            \tool_mucertify\local\notification_manager::get_instance_management_url($certification1->id)->out(false)
        );
    }

    public function test_trigger_notifications(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['fullname' => 'Pokus', 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['programid1' => $program1->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $assignment1 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );

        $types = \tool_mucertify\local\notification_manager::get_all_types();
        // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingForeach
        /** @var class-string<\tool_mucertify\local\notification\base> $classname */
        foreach ($types as $type => $classname) {
            $generator->create_certifiction_notification(['notificationtype' => $type, 'certificationid' => $certification1->id]);
        }

        \tool_mucertify\local\notification_manager::trigger_notifications(null, null);
        \tool_mucertify\local\notification_manager::trigger_notifications($certification1->id, $user1->id);
    }

    public function test_delete_assignment_notifications(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['fullname' => 'Pokus', 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $now = time();

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $program2 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['programid1' => $program1->id, 'sources' => ['manual' => []]]);
        $certification2 = $generator->create_certification(['programid1' => $program2->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'valid', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification2->id]);

        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $period1x1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1x1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1x1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));

        manual::assign_users($certification1->id, $source1->id, [$user2->id]);
        $period1x2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1x2->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1x2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        manual::assign_users($certification2->id, $source2->id, [$user1->id]);
        $period2x1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period2x1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period2x1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment3 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(5, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(3, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_assignment_notifications($assignment3);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_assignment_notifications($assignment1);
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_assignment_notifications($assignment2);
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));
    }

    public function test_delete_period_notifications(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['fullname' => 'Pokus', 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $now = time();

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $program2 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['programid1' => $program1->id, 'sources' => ['manual' => []]]);
        $certification2 = $generator->create_certification(['programid1' => $program2->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'valid', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification2->id]);

        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $period1x1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1x1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1x1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));

        manual::assign_users($certification1->id, $source1->id, [$user2->id]);
        $period1x2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1x2->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1x2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        manual::assign_users($certification2->id, $source2->id, [$user1->id]);
        $period2x1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period2x1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period2x1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment3 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(5, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(3, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_period_notifications($period1x1);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_assignment_notifications($assignment1);
        $this->assertCount(3, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_assignment_notifications($assignment2);
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));
    }

    public function test_delete_certification_notifications(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['fullname' => 'Pokus', 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $now = time();

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $program2 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['programid1' => $program1->id, 'sources' => ['manual' => []]]);
        $certification2 = $generator->create_certification(['programid1' => $program2->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'valid', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification2->id]);

        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $period1x1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1x1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1x1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));

        manual::assign_users($certification1->id, $source1->id, [$user2->id]);
        $period1x2 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1x2->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1x2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertCount(4, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        manual::assign_users($certification2->id, $source2->id, [$user1->id]);
        $period2x1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period2x1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period2x1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $assignment3 = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertCount(5, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(3, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(2, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_certification_notifications($certification1);
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', []));
        $this->assertCount(1, $DB->get_records('tool_mulib_notification_user', ['userid' => $user1->id]));
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', ['userid' => $user2->id]));

        \tool_mucertify\local\notification_manager::delete_certification_notifications($certification2);
        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));
    }

    public function test_get_timenotified(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['fullname' => 'Pokus', 'programid1' => $program1->id, 'sources' => ['manual' => []]]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $program1 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $program2 = $programgenerator->create_program(['fullname' => 'hokus', 'sources' => ['mucertify' => []]]);
        $certification1 = $generator->create_certification(['programid1' => $program1->id, 'sources' => ['manual' => []]]);
        $certification2 = $generator->create_certification(['programid1' => $program2->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'valid', 'certificationid' => $certification1->id]);
        $generator->create_certifiction_notification(['notificationtype' => 'assignment', 'certificationid' => $certification2->id]);

        $this->assertCount(0, $DB->get_records('tool_mulib_notification_user', []));

        $this->setCurrentTimeStart();

        manual::assign_users($certification1->id, $source1->id, [$user1->id, $user2->id]);
        manual::assign_users($certification2->id, $source2->id, [$user1->id]);

        $this->assertTimeCurrent(\tool_mucertify\local\notification_manager::get_timenotified($user1->id, $certification1->id, 'assignment'));
        $this->assertTimeCurrent(\tool_mucertify\local\notification_manager::get_timenotified($user1->id, $certification2->id, 'assignment'));
        $this->assertTimeCurrent(\tool_mucertify\local\notification_manager::get_timenotified($user2->id, $certification1->id, 'assignment'));
        $this->assertNull(\tool_mucertify\local\notification_manager::get_timenotified($user1->id, $certification1->id, 'valid'));
        $this->assertNull(\tool_mucertify\local\notification_manager::get_timenotified($user1->id, $certification2->id, 'valid'));
        $this->assertNull(\tool_mucertify\local\notification_manager::get_timenotified($user2->id, $certification1->id, 'valid'));
    }
}
