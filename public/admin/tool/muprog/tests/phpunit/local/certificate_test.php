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

/**
 * Program certificate issuing test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\certificate
 */
final class certificate_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        if (!get_config('tool_certificate', 'version')) {
            $this->markTestSkipped('Certificate tool is not installed, cannot test it integration');
        }
        $this->resetAfterTest();
    }

    public function test_is_available(): void {
        $this->assertTrue(\tool_muprog\local\certificate::is_available());

        \set_config('version', 2022031619, 'tool_certificate');
        $this->assertFalse(\tool_muprog\local\certificate::is_available());

        \unset_config('version', 'tool_certificate');
        $this->assertFalse(\tool_muprog\local\certificate::is_available());
    }

    public function test_update_certificate(): void {
        global $DB;

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_certificate_generator $certificategenerator */
        $certificategenerator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $program = $programgenerator->create_program();
        $template1 = $certificategenerator->create_template(['name' => 'Cert temp 1']);
        $template2 = $certificategenerator->create_template(['name' => 'Cert temp 2']);

        $data = [
            'id' => $program->id,
            'templateid' => $template1->get_id(),
            'expirydatetype' => 0,
            'expirydateabsolute' => 0,
            'expirydaterelative' => 0,
        ];
        $this->setCurrentTimeStart();
        $cert = \tool_muprog\local\certificate::update_program_certificate($data);
        $this->assertSame($program->id, $cert->programid);
        $this->assertSame((string)$template1->get_id(), $cert->templateid);
        $this->assertSame('0', $cert->expirydatetype);
        $this->assertSame(null, $cert->expirydateoffset);
        $this->assertTimeCurrent($cert->timecreated);

        $data = [
            'id' => $program->id,
            'templateid' => $template2->get_id(),
            'expirydatetype' => 1,
            'expirydateabsolute' => time() + 30,
            'expirydaterelative' => 0,
        ];
        $cert = \tool_muprog\local\certificate::update_program_certificate($data);
        $this->assertSame($program->id, $cert->programid);
        $this->assertSame((string)$template2->get_id(), $cert->templateid);
        $this->assertSame('1', $cert->expirydatetype);
        $this->assertSame((string)$data['expirydateabsolute'], $cert->expirydateoffset);

        $data = [
            'id' => $program->id,
            'templateid' => $template2->get_id(),
            'expirydatetype' => 2,
            'expirydateabsolute' => time() + 30,
            'expirydaterelative' => 60 * 60 * 24,
        ];
        $cert = \tool_muprog\local\certificate::update_program_certificate($data);
        $this->assertSame($program->id, $cert->programid);
        $this->assertSame((string)$template2->get_id(), $cert->templateid);
        $this->assertSame('2', $cert->expirydatetype);
        $this->assertSame((string)$data['expirydaterelative'], $cert->expirydateoffset);
    }

    public function test_delete_program_certificate(): void {
        global $DB;

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_certificate_generator $certificategenerator */
        $certificategenerator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $program = $programgenerator->create_program();
        $template = $certificategenerator->create_template(['name' => 'Cert temp 1']);

        $data = [
            'id' => $program->id,
            'templateid' => $template->get_id(),
            'expirydatetype' => 0,
            'expirydateabsolute' => 0,
            'expirydaterelative' => 0,
        ];
        $cert = \tool_muprog\local\certificate::update_program_certificate($data);

        $this->assertTrue($DB->record_exists('tool_muprog_cert', ['id' => $cert->id]));
        \tool_muprog\local\certificate::delete_program_certificate($program->id);
        $this->assertFalse($DB->record_exists('tool_muprog_cert', ['id' => $cert->id]));
    }

    public function test_issue(): void {
        global $DB;

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_certificate_generator $certificategenerator */
        $certificategenerator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $program = $programgenerator->create_program();
        $template = $certificategenerator->create_template(['name' => 'Cert temp 1']);

        $data = [
            'id' => $program->id,
            'templateid' => $template->get_id(),
            'expirydatetype' => 0,
            'expirydateabsolute' => 0,
            'expirydaterelative' => 0,
        ];
        $cert = \tool_muprog\local\certificate::update_program_certificate($data);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $allocation1 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program->id]);
        $allocation2 = $programgenerator->create_program_allocation(['userid' => $user2->id, 'programid' => $program->id]);

        $allocation1->timecompleted = time();
        $allocation1 = \tool_muprog\local\source\base::allocation_update($allocation1);

        $this->assertTrue(\tool_muprog\local\certificate::issue($program->id, $user1->id));
        $this->assertSame(1, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation1->id]));
        $issue1 = $DB->get_record('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation1->id], '*', MUST_EXIST);
        $this->assertSame($issue1->timecompleted, $allocation1->timecompleted);
        $i1 = $DB->get_record('tool_certificate_issues', ['id' => $issue1->issueid], '*', MUST_EXIST);

        $this->assertFalse(\tool_muprog\local\certificate::issue($program->id, $user1->id));

        $allocation1->timecompleted = $allocation1->timecompleted + 11;
        $allocation1 = \tool_muprog\local\source\base::allocation_update($allocation1);
        $this->assertFalse(\tool_muprog\local\certificate::issue($program->id, $user1->id));
        $this->assertSame(1, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation1->id]));

        // Not completed.
        $this->assertFalse(\tool_muprog\local\certificate::issue($program->id, $user2->id));

        // Archived allocation.
        $allocation2->timecompleted = time();
        $allocation2 = \tool_muprog\local\source\base::allocation_update($allocation2);
        $allocation2 = \tool_muprog\local\source\base::allocation_archive($allocation2->id);
        $this->assertFalse(\tool_muprog\local\certificate::issue($program->id, $user2->id));

        // Program archived.
        $allocation2 = \tool_muprog\local\source\base::allocation_restore($allocation2->id);
        $program = \tool_muprog\local\program::archive($program->id);
        $this->assertFalse(\tool_muprog\local\certificate::issue($program->id, $user2->id));

        // Not allocated.
        $this->assertFalse(\tool_muprog\local\certificate::issue($program->id, $user3->id));

        $this->assertSame(0, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation2->id]));
        $this->assertSame(0, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation2->id]));
    }

    public function test_cron(): void {
        global $DB;

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_certificate_generator $certificategenerator */
        $certificategenerator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $program = $programgenerator->create_program();
        $template = $certificategenerator->create_template(['name' => 'Cert temp 1']);

        $data = [
            'id' => $program->id,
            'templateid' => $template->get_id(),
            'expirydatetype' => 0,
            'expirydateabsolute' => 0,
            'expirydaterelative' => 0,
        ];
        $cert = \tool_muprog\local\certificate::update_program_certificate($data);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $allocation1 = $programgenerator->create_program_allocation(['userid' => $user1->id, 'programid' => $program->id]);
        $allocation2 = $programgenerator->create_program_allocation(['userid' => $user2->id, 'programid' => $program->id]);
        $allocation3 = $programgenerator->create_program_allocation(['userid' => $user3->id, 'programid' => $program->id]);

        $source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual'], '*', MUST_EXIST);

        $allocation1->timecompleted = time();
        $allocation1 = \tool_muprog\local\source\base::allocation_update($allocation1);

        \tool_muprog\local\certificate::cron();
        $this->assertSame(1, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation1->id]));
        $this->assertSame(0, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation2->id]));
        $issue1 = $DB->get_record('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation1->id], '*', MUST_EXIST);
        $i1 = $DB->get_record('tool_certificate_issues', ['id' => $issue1->issueid], '*', MUST_EXIST);
        $this->assertSame('0', $i1->archived);

        \tool_muprog\local\source\manual::allocation_delete($program, $source, $allocation1);
        \tool_muprog\local\certificate::cron();
        $this->assertSame(0, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation1->id]));
        $this->assertSame(0, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation2->id]));

        $allocation2->timecompleted = time();
        $allocation2 = \tool_muprog\local\source\base::allocation_update($allocation2);
        $allocation3->timecompleted = time();
        $allocation3 = \tool_muprog\local\source\base::allocation_update($allocation3);

        \tool_muprog\local\certificate::cron();
        $this->assertSame(1, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation2->id]));
        $this->assertSame(1, $DB->count_records('tool_muprog_cert_issue', ['programid' => $program->id, 'allocationid' => $allocation3->id]));
    }
}
