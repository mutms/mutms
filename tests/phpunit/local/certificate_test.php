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
 * Certification certificate util test.
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
final class certificate_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_is_available(): void {
        if (get_config('tool_certificate', 'version')) {
            $this->assertTrue(\tool_mucertify\local\certificate::is_available());
        } else {
            $this->assertFalse(\tool_mucertify\local\certificate::is_available());
        }
    }

    public function test_issue(): void {
        global $DB;

        if (!\tool_mucertify\local\certificate::is_available()) {
            $this->markTestSkipped('tool_certificate not available');
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

        $data = [
            'programid1' => $program1->id,
            'sources' => ['manual' => []],
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $certification1 = \tool_mucertify\local\certification::update_certificate($certification1->id, $template1->get_id());
        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(null, $period1->certificateissueid);

        $this->assertTrue(\tool_mucertify\local\certificate::issue($period1->id));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $issue1 = $DB->get_record('tool_certificate_issues', ['id' => $period1->certificateissueid], '*', MUST_EXIST);
        $this->assertSame((string)$template1->get_id(), $issue1->templateid);

        $this->assertFalse(\tool_mucertify\local\certificate::issue($period1->id));

        manual::assign_users($certification1->id, $source1->id, [$user2->id]);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user2->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period2->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 2000),
            'timecertified' => (string)$now,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertTrue(\tool_mucertify\local\certificate::issue($period2->id));
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $issue2 = $DB->get_record('tool_certificate_issues', ['id' => $period2->certificateissueid], '*', MUST_EXIST);
        $this->assertSame((string)$template1->get_id(), $issue2->templateid);

        manual::assign_users($certification1->id, $source1->id, [$user3->id]);
        $period3 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user3->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period3->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 3000),
            'timecertified' => (string)$now,
            'timerevoked' => (string)$now,
        ];
        $period3 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertFalse(\tool_mucertify\local\certificate::issue($period3->id));

        $dateoverrides = [
            'id' => $period3->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 3000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period3 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertFalse(\tool_mucertify\local\certificate::issue($period3->id));

        $dateoverrides = [
            'id' => $period3->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 3000),
            'timecertified' => (string)$now,
        ];
        $certification1 = \tool_mucertify\local\certification::update_certificate($certification1->id, null);
        $period3 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertFalse(\tool_mucertify\local\certificate::issue($period3->id));

        $this->assertFalse(\tool_mucertify\local\certificate::issue(99999999));
    }

    public function test_revoke(): void {
        global $DB;

        if (!\tool_mucertify\local\certificate::is_available()) {
            $this->markTestSkipped('tool_certificate not available');
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

        $data = [
            'programid1' => $program1->id,
            'sources' => ['manual' => []],
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $certification1 = \tool_mucertify\local\certification::update_certificate($certification1->id, $template1->get_id());
        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $assignment1 = $DB->get_record(
            'tool_mucertify_assignment',
            ['certificationid' => $certification1->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(null, $period1->certificateissueid);

        $this->assertTrue(\tool_mucertify\local\certificate::issue($period1->id));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertNotEmpty($period1->certificateissueid);

        \tool_mucertify\local\certificate::revoke($period1->id);
        $this->assertFalse($DB->record_exists('tool_certificate_issues', ['id' => $period1->certificateissueid]));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertSame(null, $period1->certificateissueid);

        $this->assertTrue(\tool_mucertify\local\certificate::issue($period1->id));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertNotEmpty($period1->certificateissueid);
        $dateoverrides = [
            'id' => $period1->id,
            'timerevoked' => (string)$now,
        ];
        $period1x = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertFalse($DB->record_exists('tool_certificate_issues', ['id' => $period1->certificateissueid]));
        $this->assertSame(null, $period1x->certificateissueid);

        $dateoverrides = [
            'id' => $period1->id,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertTrue(\tool_mucertify\local\certificate::issue($period1->id));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertNotEmpty($period1->certificateissueid);

        \tool_mucertify\local\period::delete($period1->id);
        ;
        $this->assertFalse($DB->record_exists('tool_mucertify_period', ['id' => $period1->id]));
        $this->assertFalse($DB->record_exists('tool_certificate_issues', ['id' => $period1->certificateissueid]));

        $data = [
            'certificationid' => $certification1->id,
            'userid' => $user1->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::add((object)$data);
        $this->assertTrue(\tool_mucertify\local\certificate::issue($period1->id));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);

        manual::assignment_delete($certification1, $source1, $assignment1);
        $this->assertFalse($DB->record_exists('tool_mucertify_period', ['id' => $period1->id]));
        $this->assertFalse($DB->record_exists('tool_certificate_issues', ['id' => $period1->certificateissueid]));
    }

    public function test_template_deleted(): void {
        global $DB;

        if (!\tool_mucertify\local\certificate::is_available()) {
            $this->markTestSkipped('tool_certificate not available');
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

        $data = [
            'programid1' => $program1->id,
            'sources' => ['manual' => []],
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $certification1 = \tool_mucertify\local\certification::update_certificate($certification1->id, $template1->get_id());
        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $assignment1 = $DB->get_record(
            'tool_mucertify_assignment',
            ['certificationid' => $certification1->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertTrue(\tool_mucertify\local\certificate::issue($period1->id));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('tool_certificate_issues', ['id' => $period1->certificateissueid]));

        $template1->delete();
        $this->assertFalse($DB->record_exists('tool_certificate_issues', ['id' => $period1->certificateissueid]));
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertSame(null, $period1->certificateissueid);
    }

    public function test_cron(): void {
        global $DB;

        \tool_mucertify\local\certificate::cron();

        if (!\tool_mucertify\local\certificate::is_available()) {
            $this->markTestSkipped('tool_certificate not available');
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
        $certification1 = \tool_mucertify\local\certification::update_certificate($certification1->id, $template1->get_id());
        $certification2 = \tool_mucertify\local\certification::update_certificate($certification2->id, $template1->get_id());

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
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period2->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period3->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now + 100),
        ];
        $period3 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period4->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
            'timerevoked' => $now,
        ];
        $period4 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $dateoverrides = [
            'id' => $period5->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
        ];
        $period5 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
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
        $period6 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $certification2 = \tool_mucertify\local\certification::archive($certification2->id);

        \tool_mucertify\local\certificate::cron();

        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period3 = $DB->get_record('tool_mucertify_period', ['id' => $period3->id], '*', MUST_EXIST);
        $period4 = $DB->get_record('tool_mucertify_period', ['id' => $period4->id], '*', MUST_EXIST);
        $period5 = $DB->get_record('tool_mucertify_period', ['id' => $period5->id], '*', MUST_EXIST);
        $period6 = $DB->get_record('tool_mucertify_period', ['id' => $period6->id], '*', MUST_EXIST);

        $this->assertNotEmpty($period1->certificateissueid);
        $this->assertNotEmpty($period2->certificateissueid);
        $this->assertNotEmpty($period3->certificateissueid);
        $this->assertNull($period4->certificateissueid);
        $this->assertNull($period5->certificateissueid);
        $this->assertNull($period6->certificateissueid);

        \tool_mucertify\local\certificate::cron();

        $period1x = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $period2x = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period3x = $DB->get_record('tool_mucertify_period', ['id' => $period3->id], '*', MUST_EXIST);
        $period4 = $DB->get_record('tool_mucertify_period', ['id' => $period4->id], '*', MUST_EXIST);
        $period5 = $DB->get_record('tool_mucertify_period', ['id' => $period5->id], '*', MUST_EXIST);
        $period6 = $DB->get_record('tool_mucertify_period', ['id' => $period6->id], '*', MUST_EXIST);
        $this->assertSame($period1->certificateissueid, $period1x->certificateissueid);
        $this->assertSame($period2->certificateissueid, $period2x->certificateissueid);
        $this->assertSame($period3->certificateissueid, $period3x->certificateissueid);
        $this->assertNull($period4->certificateissueid);
        $this->assertNull($period5->certificateissueid);
        $this->assertNull($period6->certificateissueid);

        // Test removing of invalid certificates.
        $DB->delete_records('tool_certificate_issues', ['id' => $period1->certificateissueid]);
        $DB->set_field('tool_mucertify_period', 'timerevoked', $now, ['id' => $period2->id]);
        $DB->set_field('tool_mucertify_period', 'certificateissueid', '9999999999', ['id' => $period3->certificateissueid]);

        \tool_mucertify\local\certificate::cron();

        $period1x = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $period2x = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period3x = $DB->get_record('tool_mucertify_period', ['id' => $period3->id], '*', MUST_EXIST);
        $period4 = $DB->get_record('tool_mucertify_period', ['id' => $period4->id], '*', MUST_EXIST);
        $period5 = $DB->get_record('tool_mucertify_period', ['id' => $period5->id], '*', MUST_EXIST);
        $period6 = $DB->get_record('tool_mucertify_period', ['id' => $period6->id], '*', MUST_EXIST);
        $this->assertNotEmpty($period1x->certificateissueid);
        $this->assertNotEquals($period1->certificateissueid, $period1x->certificateissueid);
        $this->assertTrue($DB->record_exists('tool_certificate_issues', ['id' => $period1x->certificateissueid]));
        $this->assertSame(null, $period2x->certificateissueid);
        $this->assertFalse($DB->record_exists('tool_certificate_issues', ['id' => $period2->certificateissueid]));
        $this->assertTrue($DB->record_exists('tool_certificate_issues', ['id' => $period3x->certificateissueid]));
        $this->assertNull($period4->certificateissueid);
        $this->assertNull($period5->certificateissueid);
        $this->assertNull($period6->certificateissueid);

        $DB->delete_records('tool_mucertify_period', ['id' => $period1->id]);
        \tool_mucertify\local\certificate::cron();
        $this->assertFalse($DB->record_exists('tool_certificate_issues', ['id' => $period1->certificateissueid]));

        $dateoverrides = [
            'id' => $period4->id,
            'timewindowstart' => (string)($now - 1500),
            'timefrom' => (string)($now - 1000),
            'timeuntil' => (string)($now + 1000),
            'timecertified' => (string)($now - 10),
            'timerevoked' => null,
        ];
        $period4 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        ob_start();
        (new \tool_mucertify\task\cron())->execute();
        ob_end_clean();
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period3 = $DB->get_record('tool_mucertify_period', ['id' => $period3->id], '*', MUST_EXIST);
        $period4 = $DB->get_record('tool_mucertify_period', ['id' => $period4->id], '*', MUST_EXIST);
        $period5 = $DB->get_record('tool_mucertify_period', ['id' => $period5->id], '*', MUST_EXIST);
        $period6 = $DB->get_record('tool_mucertify_period', ['id' => $period6->id], '*', MUST_EXIST);
        $this->assertNull($period2->certificateissueid);
        $this->assertNotEmpty($period3->certificateissueid);
        $this->assertNotEmpty($period4->certificateissueid);
        $this->assertNull($period5->certificateissueid);
        $this->assertNull($period6->certificateissueid);
    }
}
