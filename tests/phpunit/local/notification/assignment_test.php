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

use tool_mucertify\local\source\manual;
use tool_mulib\local\mulib;

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
 * @covers \tool_mucertify\local\notification\assignment
 */
final class assignment_test extends \advanced_testcase {
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
        $program = $programgenerator->create_program(['sources' => 'mucertify']);
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

        $this->setAdminUser();
        $sink = $this->redirectMessages();
        manual::assign_users($certification->id, $source->id, [$user1->id], []);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);

        $notification = $generator->create_certifiction_notification(['certificationid' => $certification->id, 'notificationtype' => 'assignment']);
        $sink = $this->redirectMessages();
        manual::assign_users($certification->id, $source->id, [$user2->id], []);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user2->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('Certification assignment notification', $message->subject);
        $this->assertStringContainsString('you have been assigned to certification', $message->fullmessage);
        $this->assertSame('tool_mucertify', $message->component);
        $this->assertSame('assignment_notification', $message->eventtype);
        $this->assertSame("$CFG->wwwroot/admin/tool/mucertify/my/certification.php?id=$certification->id", $message->contexturl);
        $this->assertSame('1', $message->notification);
    }

    public function test_cc_supervisor(): void {
        global $DB;
        if (!mulib::is_murelatio_available()) {
            return;
        }

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

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

        $syscontext = \context_system::instance();
        $program = $programgenerator->create_program(['sources' => 'mucertify']);
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

        $this->setUser($user3);

        $notification = $generator->create_certifiction_notification([
            'certificationid' => $certification->id,
            'notificationtype' => 'assignment',
            'supervisorframeworkid' => $framework1->id,
        ]);
        $sink = $this->redirectMessages();
        $this->setCurrentTimeStart();
        manual::assign_users($certification->id, $source->id, [$user2->id]);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $messages);
        $message = $messages[0];
        $this->assertSame('Certification assignment notification', $message->subject);
        $this->assertStringContainsString('you have been assigned to certification', $message->fullmessage);
        $this->assertSame($user2->id, $message->useridto);
        $this->assertSame('-10', $message->useridfrom);
        $message = $messages[1];
        $this->assertSame('Supervisor notification - Certification 1', $message->subject);
        $this->assertStringContainsString('a notification was sent to the following user', $message->fullmessage);
        $this->assertStringContainsString('you have been assigned to certification', $message->fullmessage);
        $this->assertSame($user1->id, $message->useridto);
        $this->assertSame('-10', $message->useridfrom);
    }
}
