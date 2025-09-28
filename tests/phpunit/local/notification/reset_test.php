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

use tool_muprog\local\allocation;
use tool_muprog\local\course_reset;
use tool_muprog\local\notification_manager;
use tool_muprog\local\source\manual;

/**
 * Program allocation notifications test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\notification\reset
 */
final class reset_test extends \advanced_testcase {
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

        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $sink = $this->redirectMessages();
        $data = (object)[
            'id' => $allocation1->id,
            'userid' => $user1->id,
            'resettype' => course_reset::RESETTYPE_STANDARD,
        ];
        allocation::reset($data);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
        $this->assertNull(notification_manager::get_timenotified($user1->id, $program1->id, 'reset'));

        $notification = $generator->create_program_notification(['programid' => $program1->id, 'notificationtype' => 'reset']);
        $sink = $this->redirectMessages();
        $this->setCurrentTimeStart();
        allocation::reset($data);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user1->id, $program1->id, 'reset'));
        $message = $messages[0];
        $this->assertSame('Program reset notification', $message->subject);
        $this->assertStringContainsString('was reset', $message->fullmessage);
        $this->assertSame($user1->id, $message->useridto);
        $this->assertSame('-10', $message->useridfrom);
    }
}
