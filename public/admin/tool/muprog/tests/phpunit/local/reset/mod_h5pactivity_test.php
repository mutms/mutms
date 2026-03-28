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
 * h5pactivity activity purge test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\reset\mod_h5pactivity
 */
final class mod_h5pactivity_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (!get_config('mod_h5pactivity', 'version')) {
            $this->markTestSkipped('mod_h5pactivity is not installed');
        }
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \mod_h5pactivity_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_h5pactivity');

        $this->setAdminUser();
        $course1 = $this->getDataGenerator()->create_course();
        $h5pactivity1 = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course1->id]);
        $cm1 = get_coursemodule_from_instance('h5pactivity', $h5pactivity1->id);
        $context1 = \context_module::instance($cm1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $h5pactivity2 = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course2->id]);
        $cm2 = get_coursemodule_from_instance('h5pactivity', $h5pactivity2->id);
        $context2 = \context_module::instance($cm2->id);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id, 'student');
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, 'teacher');

        $generator->create_content($h5pactivity1, ['cmid' => $cm1->id, 'userid' => $student1->id]);
        $generator->create_content($h5pactivity1, ['cmid' => $cm1->id, 'userid' => $student2->id]);
        $generator->create_content($h5pactivity2, ['cmid' => $cm2->id, 'userid' => $student1->id]);

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(3, $DB->get_records('h5pactivity_attempts', []));
        $this->assertCount(9, $DB->get_records('h5pactivity_attempts_results', []));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(2, $DB->get_records('h5pactivity_attempts', []));
        $this->assertCount(1, $DB->get_records('h5pactivity_attempts', ['h5pactivityid' => $h5pactivity1->id, 'userid' => $student2->id]));
        $this->assertCount(1, $DB->get_records('h5pactivity_attempts', ['h5pactivityid' => $h5pactivity2->id, 'userid' => $student1->id]));
        $attempt1 = $DB->get_record('h5pactivity_attempts', ['h5pactivityid' => $h5pactivity1->id, 'userid' => $student2->id]);
        $attempt2 = $DB->get_record('h5pactivity_attempts', ['h5pactivityid' => $h5pactivity2->id, 'userid' => $student1->id]);
        $this->assertCount(6, $DB->get_records('h5pactivity_attempts_results', []));
        $this->assertCount(3, $DB->get_records('h5pactivity_attempts_results', ['attemptid' => $attempt1->id]));
        $this->assertCount(3, $DB->get_records('h5pactivity_attempts_results', ['attemptid' => $attempt2->id]));
    }
}
