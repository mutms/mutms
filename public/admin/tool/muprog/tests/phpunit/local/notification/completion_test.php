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

/**
 * Program completion notification test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\notification\completion
 */
final class completion_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_notify_users(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id, $user3->id, $user4->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id], '*', MUST_EXIST);
        $allocation4 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id], '*', MUST_EXIST);
        manual::allocate_users($program2->id, $source2->id, [$user1->id, $user2->id]);
        $allocation5 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation6 = $DB->get_record('tool_muprog_allocation', ['programid' => $program2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $now = time();
        $allocation1->timecompleted = $now - \tool_muprog\local\notification\completion::TIME_CUTOFF + 100;
        $DB->update_record('tool_muprog_allocation', $allocation1);
        $allocation2->timecompleted = $now - \tool_muprog\local\notification\completion::TIME_CUTOFF - 100;
        $DB->update_record('tool_muprog_allocation', $allocation2);
        $allocation3->timecompleted = $now + 100;
        $DB->update_record('tool_muprog_allocation', $allocation3);
        $allocation5->timecompleted = $now;
        $DB->update_record('tool_muprog_allocation', $allocation5);
        $allocation6->timecompleted = $now;
        $DB->update_record('tool_muprog_allocation', $allocation6);
        $generator->create_program_notification(['notificationtype' => 'completion', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'completion', 'programid' => $program2->id]);

        $this->setCurrentTimeStart();
        $sink = $this->redirectMessages();
        \tool_muprog\local\notification\completion::notify_users($program1, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame('Program completed', $message->subject);
        $this->assertSame('-10', $message->useridfrom);
        $this->assertSame($user1->id, $message->useridto);
        $this->assertSame('tool_muprog', $message->component);
        $this->assertSame('completion_notification', $message->eventtype);
        $this->assertSame($program1->fullname, $message->contexturlname);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user1->id, $program1->id, 'completion'));
        $this->assertNull(notification_manager::get_timenotified($user2->id, $program1->id, 'completion'));

        $this->setCurrentTimeStart();
        $sink = $this->redirectMessages();
        \tool_muprog\local\notification\completion::notify_users(null, $user1);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame('Program completed', $message->subject);
        $this->assertSame('-10', $message->useridfrom);
        $this->assertSame($user1->id, $message->useridto);
        $this->assertSame('tool_muprog', $message->component);
        $this->assertSame('completion_notification', $message->eventtype);
        $this->assertSame($program2->fullname, $message->contexturlname);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user1->id, $program2->id, 'completion'));
        $this->assertNull(notification_manager::get_timenotified($user2->id, $program2->id, 'completion'));

        $this->setCurrentTimeStart();
        $sink = $this->redirectMessages();
        \tool_muprog\local\notification\completion::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame('Program completed', $message->subject);
        $this->assertSame('-10', $message->useridfrom);
        $this->assertSame($user2->id, $message->useridto);
        $this->assertSame('tool_muprog', $message->component);
        $this->assertSame('completion_notification', $message->eventtype);
        $this->assertSame($program2->fullname, $message->contexturlname);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user2->id, $program2->id, 'completion'));

        $this->setCurrentTimeStart();
        $sink = $this->redirectMessages();
        \tool_muprog\local\notification\completion::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
    }
}
