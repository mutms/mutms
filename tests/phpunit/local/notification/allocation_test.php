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

namespace tool_muprog\phpunit\local\notification;

use tool_muprog\local\notification_manager;
use tool_muprog\local\source\manual;
use tool_mulib\local\mulib;

/**
 * Program allocation notifications test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\notification\allocation
 */
final class allocation_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_notification(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $this->setUser($user3);

        $sink = $this->redirectMessages();
        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertNull(notification_manager::get_timenotified($user1->id, $program1->id, 'allocation'));

        $notification = $generator->create_program_notification(['programid' => $program1->id, 'notificationtype' => 'allocation']);
        $sink = $this->redirectMessages();
        $this->setCurrentTimeStart();
        manual::allocate_users($program1->id, $source1->id, [$user2->id]);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user2->id, $program1->id, 'allocation'));
        $message = $messages[0];
        $this->assertSame('Program allocation notification', $message->subject);
        $this->assertStringContainsString('you have been allocated to program', $message->fullmessage);
        $this->assertSame($user2->id, $message->useridto);
        $this->assertSame('-10', $message->useridfrom);
    }

    public function test_cc_supervisor(): void {
        global $DB;
        if (!mulib::is_murelatio_available()) {
            return;
        }

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_murelation_generator $relationgenerator */
        $relationgenerator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $framework1 = $relationgenerator->create_framework([
            'uimode' => \tool_murelation\local\framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = $relationgenerator->create_supervisor([
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $this->setUser($user3);

        $notification = $generator->create_program_notification([
            'programid' => $program1->id,
            'notificationtype' => 'allocation',
            'supervisorframeworkid' => $framework1->id,
        ]);
        $sink = $this->redirectMessages();
        $this->setCurrentTimeStart();
        manual::allocate_users($program1->id, $source1->id, [$user2->id]);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $messages);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user2->id, $program1->id, 'allocation'));
        $message = $messages[0];
        $this->assertSame('Program allocation notification', $message->subject);
        $this->assertStringContainsString('you have been allocated to program', $message->fullmessage);
        $this->assertSame($user2->id, $message->useridto);
        $this->assertSame('-10', $message->useridfrom);
        $message = $messages[1];
        $this->assertSame('Supervisor notification - Program 1', $message->subject);
        $this->assertStringContainsString('a notification was sent to the following user', $message->fullmessage);
        $this->assertStringContainsString('you have been allocated to program', $message->fullmessage);
        $this->assertSame($user1->id, $message->useridto);
        $this->assertSame('-10', $message->useridfrom);
    }
}
