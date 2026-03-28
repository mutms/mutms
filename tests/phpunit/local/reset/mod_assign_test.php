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
 * Assignment activity purge test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\reset\mod_assign
 */
final class mod_assign_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \mod_assign_generator $assigngenerator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');

        $course1 = $this->getDataGenerator()->create_course();
        $instance1 = $assigngenerator->create_instance([
            'course' => $course1->id,
            'assignsubmission_onlinetext_enabled' => true,
            'assignfeedback_comments_enabled' => true,
        ]);
        $cm1 = get_coursemodule_from_instance('assign', $instance1->id);
        $context1 = \context_module::instance($cm1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $instance2 = $assigngenerator->create_instance([
            'course' => $course2->id,
            'assignsubmission_onlinetext_enabled' => true,
            'assignfeedback_comments_enabled' => true,
        ]);
        $cm2 = get_coursemodule_from_instance('assign', $instance2->id);
        $context2 = \context_module::instance($cm2->id);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id, 'student');
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, 'teacher');

        $this->setUser($student1);
        $assign1 = new \assign($context1, $cm1, $course1);
        $submission = new \stdClass();
        $submission->assignment = $assign1->get_instance()->id;
        $submission->userid = $student1->id;
        $submission->timecreated = time();
        $submission->onlinetext_editor = ['text' => 'I am student 1 in course 1', 'format' => \FORMAT_MOODLE];
        $notices = [];
        $assign1->save_submission($submission, $notices);

        $this->setUser($student1);
        $assign2 = new \assign($context2, $cm2, $course2);
        $submission = new \stdClass();
        $submission->assignment = $assign2->get_instance()->id;
        $submission->userid = $student1->id;
        $submission->timecreated = time();
        $submission->onlinetext_editor = ['text' => 'I am student 1 in course 2', 'format' => \FORMAT_MOODLE];
        $notices = [];
        $assign2->save_submission($submission, $notices);

        $this->setUser($student2);
        $assign1 = new \assign($context1, $cm1, $course1);
        $submission = new \stdClass();
        $submission->assignment = $assign1->get_instance()->id;
        $submission->userid = $student2->id;
        $submission->timecreated = time();
        $submission->onlinetext_editor = ['text' => 'I am student 2 in course 1', 'format' => \FORMAT_MOODLE];
        $notices = [];
        $assign1->save_submission($submission, $notices);

        $this->setUser($teacher);
        $data = new \stdClass();
        $data->attemptnumber = 1;
        $data->grade = '3.14';
        $data->assignfeedbackcomments_editor = ['text' => 'OK', 'format' => \FORMAT_MOODLE];
        $assign1 = new \assign($context1, $cm1, $course1);
        $assign1->save_grade($student1->id, $data);

        $data = new \stdClass();
        $data->attemptnumber = 1;
        $data->grade = '4.14';
        $data->assignfeedbackcomments_editor = ['text' => 'perfect', 'format' => \FORMAT_MOODLE];
        $assign2 = new \assign($context2, $cm2, $course2);
        $assign2->save_grade($student1->id, $data);

        $data = new \stdClass();
        $data->attemptnumber = 1;
        $data->grade = '1.14';
        $data->assignfeedbackcomments_editor = ['text' => 'Nearly OK', 'format' => \FORMAT_MOODLE];
        $assign1 = new \assign($context1, $cm1, $course1);
        $assign1->save_grade($student2->id, $data);

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(6, $DB->get_records('assign_submission', []));
        $this->assertCount(3, $DB->get_records('assign_grades', []));
        $this->assertCount(1, $DB->get_records('assign_grades', ['userid' => $student1->id, 'assignment' => $cm1->instance]));
        $this->assertCount(1, $DB->get_records('assign_grades', ['userid' => $student1->id, 'assignment' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('assign_grades', ['userid' => $student2->id, 'assignment' => $cm1->instance]));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(4, $DB->get_records('assign_submission', []));
        $this->assertCount(2, $DB->get_records('assign_submission', ['userid' => $student1->id, 'assignment' => $cm2->instance]));
        $this->assertCount(2, $DB->get_records('assign_submission', ['userid' => $student2->id, 'assignment' => $cm1->instance]));
        $this->assertCount(2, $DB->get_records('assign_grades', []));
        $this->assertCount(1, $DB->get_records('assign_grades', ['userid' => $student2->id, 'assignment' => $cm1->instance]));
        $this->assertCount(1, $DB->get_records('assign_grades', ['userid' => $student2->id, 'assignment' => $cm1->instance]));
    }
}
