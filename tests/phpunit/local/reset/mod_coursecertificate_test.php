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

namespace tool_muprog\phpunit\local\reset;

use tool_muprog\local\course_reset;

/**
 * coursecertificate activity purge test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\reset\mod_coursecertificate
 */
final class mod_coursecertificate_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (!get_config('mod_coursecertificate', 'version')) {
            $this->markTestSkipped('mod_coursecertificate is not installed');
        }
        if (!get_config('tool_certificate', 'version')) {
            $this->markTestSkipped('tool_certificate is not installed');
        }
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \tool_certificate_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('tool_certificate');

        $certificate1 = $toolgenerator->create_template((object)['name' => 'Certificate 1']);

        $course1 = $this->getDataGenerator()->create_course();
        $expirydate = strtotime('+5 day');
        $record = [
            'course' => $course1->id,
            'template' => $certificate1->get_id(),
            'expirydatetype' => \tool_certificate\certificate::DATE_EXPIRATION_ABSOLUTE,
            'expirydateoffset' => $expirydate,
        ];
        $coursecertificate1 = $this->getDataGenerator()->create_module('coursecertificate', $record);
        $cm1 = get_coursemodule_from_instance('coursecertificate', $coursecertificate1->id);
        $context1 = \context_module::instance($cm1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $record = [
            'course' => $course2->id,
            'template' => $certificate1->get_id(),
            'expirydatetype' => \tool_certificate\certificate::DATE_EXPIRATION_ABSOLUTE,
            'expirydateoffset' => $expirydate,
        ];
        $coursecertificate2 = $this->getDataGenerator()->create_module('coursecertificate', $record);
        $cm2 = get_coursemodule_from_instance('coursecertificate', $coursecertificate2->id);
        $context2 = \context_module::instance($cm2->id);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id, 'student');
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, 'teacher');

        $coursecertificate1->automaticsend = '1';
        $DB->update_record('coursecertificate', $coursecertificate1);
        $coursecertificate2->automaticsend = '1';
        $DB->update_record('coursecertificate', $coursecertificate2);

        $task = new \mod_coursecertificate\task\issue_certificates_task();
        ob_start();
        $task->execute();
        ob_end_clean();

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(3, $DB->get_records('tool_certificate_issues', []));
        $this->assertCount(3, $DB->get_records('tool_certificate_issues', ['archived' => 0]));
        $this->assertCount(1, $DB->get_records(
            'tool_certificate_issues',
            ['templateid' => $certificate1->get_id(), 'courseid' => $course1->id, 'userid' => $student1->id]
        ));
        $this->assertCount(1, $DB->get_records(
            'tool_certificate_issues',
            ['templateid' => $certificate1->get_id(), 'courseid' => $course2->id, 'userid' => $student1->id]
        ));
        $this->assertCount(1, $DB->get_records(
            'tool_certificate_issues',
            ['templateid' => $certificate1->get_id(), 'courseid' => $course1->id, 'userid' => $student2->id]
        ));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(2, $DB->get_records('tool_certificate_issues', ['archived' => 0]));
        $this->assertCount(1, $DB->get_records('tool_certificate_issues', ['archived' => 1]));
        $this->assertCount(1, $DB->get_records(
            'tool_certificate_issues',
            ['templateid' => $certificate1->get_id(), 'courseid' => $course1->id, 'userid' => $student1->id, 'archived' => 1]
        ));
        $this->assertCount(1, $DB->get_records(
            'tool_certificate_issues',
            ['templateid' => $certificate1->get_id(), 'courseid' => $course2->id, 'userid' => $student1->id, 'archived' => 0]
        ));
        $this->assertCount(1, $DB->get_records(
            'tool_certificate_issues',
            ['templateid' => $certificate1->get_id(), 'courseid' => $course1->id, 'userid' => $student2->id, 'archived' => 0]
        ));
    }
}
