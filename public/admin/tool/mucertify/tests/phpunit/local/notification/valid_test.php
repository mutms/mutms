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

use tool_mucertify\local\assignment;
use tool_mucertify\local\certification;
use tool_mucertify\local\period;
use tool_mucertify\local\source\manual;

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
 * @covers \tool_mucertify\local\notification\valid
 */
final class valid_test extends \advanced_testcase {
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

        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user1->id], []);

        $notification = $generator->create_certifiction_notification(['certificationid' => $certification->id, 'notificationtype' => 'valid']);

        $now = time();
        $period = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1x1 = period::override_dates((object)$dateoverrides);

        $sink = $this->redirectMessages();
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('Valid certification notification', $message->subject);
        $this->assertStringContainsString('is now valid', $message->fullmessage);
        $this->assertSame('tool_mucertify', $message->component);
        $this->assertSame('valid_notification', $message->eventtype);
        $this->assertSame("$CFG->wwwroot/admin/tool/mucertify/my/certification.php?id=$certification->id", $message->contexturl);
        $this->assertSame('1', $message->notification);
        $this->assertSame($user1->id, $message->useridto);
    }

    public function test_notify_users(): void {
        global $DB;

        if (!get_config('tool_certificate', 'version')) {
            $this->markTestSkipped('tool_certificate is required');
        }

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_certificate_generator $certificategenerator */
        $certificategenerator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $now = time();

        $template1 = $certificategenerator->create_template(['name' => 't1']);
        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $data = [
            'programid1' => $program1->id,
            'sources' => ['manual' => []],
        ];
        $certification1 = $generator->create_certification($data);
        $certification2 = $generator->create_certification($data);
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
        $certification1 = certification::update_certificate($certification1->id, $template1->get_id());
        $certification2 = certification::update_certificate($certification2->id, $template1->get_id());

        manual::assign_users($certification1->id, $source1->id, [$user1->id, $user2->id, $user3->id, $user4->id, $user5->id]);
        manual::assign_users($certification2->id, $source2->id, [$user6->id]);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user2->id],
            '*',
            MUST_EXIST
        );
        $period3 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user3->id],
            '*',
            MUST_EXIST
        );
        $period4 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user4->id],
            '*',
            MUST_EXIST
        );
        $period5 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user5->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period1 = period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period2->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now + 100),
        ];
        $period2 = period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period3->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 2000),
            'timecertified' => (string)($now - 100),
        ];
        $period3 = period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period4->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
            'timerevoked' => $now,
        ];
        $period4 = period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period5->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period5 = period::override_dates((object)$dateoverrides);
        $assignment5 = $DB->get_record(
            'tool_mucertify_assignment',
            ['certificationid' => $certification1->id, 'userid' => $user5->id],
            '*',
            MUST_EXIST
        );
        $assignment5 = \tool_mucertify\local\source\base::assignment_archive($assignment5->id);
        $period6 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification2->id, 'userid' => $user6->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period6->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)$now,
        ];
        $period6 = period::override_dates((object)$dateoverrides);
        $certification2 = certification::archive($certification2->id);

        $sink = $this->redirectMessages();
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);

        $notification = $generator->create_certifiction_notification(['certificationid' => $certification1->id, 'notificationtype' => 'valid']);
        $notification = $generator->create_certifiction_notification(['certificationid' => $certification2->id, 'notificationtype' => 'valid']);

        $sink = $this->redirectMessages();
        \tool_mucertify\local\notification\valid::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $messages);

        $message = array_shift($messages);
        $this->assertSame($user1->id, $message->useridto);

        $message = array_shift($messages);
        $this->assertSame($user2->id, $message->useridto);
    }
}
