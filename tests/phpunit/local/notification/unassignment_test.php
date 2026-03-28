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

namespace tool_mucertify\phpunit\local\notification;

/**
 * Certification notification test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\notification\unassignment
 */
final class unassignment_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_notification(): void {
        global $DB, $CFG;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $program = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 1]);
        $certification = $generator->create_certification([
            'sources' => 'manual',
            'programid1' => $program->id,
            'contextid' => $syscontext->id,
        ]);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id, $user3->id], []);
        $assignment1 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $assignment2 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user2->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $assignment3 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user3->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $assignment3->archived = '1';
        $DB->update_record('tool_mucertify_assignment', $assignment3);

        $this->setAdminUser();

        $sink = $this->redirectMessages();
        \tool_mucertify\local\source\manual::assignment_delete($certification, $source, $assignment1);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);

        $notification = $generator->create_certifiction_notification(['certificationid' => $certification->id, 'notificationtype' => 'unassignment']);

        $sink = $this->redirectMessages();
        \tool_mucertify\local\source\manual::assignment_delete($certification, $source, $assignment2);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertSame($user2->id, $message->useridto);
        $this->assertSame('Certification un-assignment notification', $message->subject);
        $this->assertStringContainsString('you have been un-assigned from certification', $message->fullmessage);
        $this->assertSame('tool_mucertify', $message->component);
        $this->assertSame('unassignment_notification', $message->eventtype);
        $this->assertSame("$CFG->wwwroot/admin/tool/mucertify/catalogue/certification.php?id=$certification->id", $message->contexturl);
        $this->assertSame('1', $message->notification);

        $sink = $this->redirectMessages();
        \tool_mucertify\local\source\manual::assignment_delete($certification, $source, $assignment3);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertSame($user3->id, $message->useridto);
        $this->assertSame('Certification un-assignment notification', $message->subject);
        $this->assertStringContainsString('you have been un-assigned from certification', $message->fullmessage);
        $this->assertSame('tool_mucertify', $message->component);
        $this->assertSame('unassignment_notification', $message->eventtype);
        $this->assertSame("$CFG->wwwroot/admin/tool/mucertify/catalogue/certification.php?id=$certification->id", $message->contexturl);
        $this->assertSame('1', $message->notification);

        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user1->id], []);
        $assignment1 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $certification->archived = '1';
        $DB->update_record('tool_mucertify_certification', $certification);
        $sink = $this->redirectMessages();
        \tool_mucertify\local\source\manual::assignment_delete($certification, $source, $assignment1);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
    }
}
