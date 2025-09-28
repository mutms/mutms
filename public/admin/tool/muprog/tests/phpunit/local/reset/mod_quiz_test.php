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

use mod_quiz\quiz_settings;
use tool_muprog\local\course_reset;

/**
 * Quiz activity purge test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\reset\mod_quiz
 */
final class mod_quiz_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \mod_quiz_generator $quizgenerator */
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);

        $course1 = $this->getDataGenerator()->create_course();
        $quiz1 = $quizgenerator->create_instance(['course' => $course1->id, 'grade' => 100.0, 'sumgrades' => 2]);
        $cm1 = get_coursemodule_from_instance('quiz', $quiz1->id);
        $context1 = \context_module::instance($cm1->id);
        \quiz_add_quiz_question($saq->id, $quiz1);
        $quizobj1 = quiz_settings::create($quiz1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $quiz2 = $quizgenerator->create_instance(['course' => $course2->id, 'grade' => 100.0, 'sumgrades' => 2]);
        $cm2 = get_coursemodule_from_instance('quiz', $quiz2->id);
        $context2 = \context_module::instance($cm2->id);
        \quiz_add_quiz_question($saq->id, $quiz2);
        $quizobj2 = quiz_settings::create($quiz2->id);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id, 'student');
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, 'teacher');

        $this->setUser($student1);
        $quba1 = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj1->get_context());
        $quba1->set_preferred_behaviour($quizobj1->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = \quiz_create_attempt($quizobj1, 1, false, $timenow, false, $student1->id);
        \quiz_start_new_attempt($quizobj1, $quba1, $attempt, 1, $timenow);
        \quiz_attempt_save_started($quizobj1, $quba1, $attempt);

        $this->setUser($student2);
        $quba1 = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj1->get_context());
        $quba1->set_preferred_behaviour($quizobj1->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = \quiz_create_attempt($quizobj1, 1, false, $timenow, false, $student2->id);
        \quiz_start_new_attempt($quizobj1, $quba1, $attempt, 1, $timenow);
        \quiz_attempt_save_started($quizobj1, $quba1, $attempt);

        $this->setUser($student1);
        $quba2 = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj2->get_context());
        $quba2->set_preferred_behaviour($quizobj2->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = \quiz_create_attempt($quizobj2, 1, false, $timenow, false, $student1->id);
        \quiz_start_new_attempt($quizobj2, $quba2, $attempt, 1, $timenow);
        \quiz_attempt_save_started($quizobj2, $quba2, $attempt);

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(3, $DB->get_records('quiz_attempts', []));
        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $student1->id, 'quiz' => $cm1->instance]));
        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $student1->id, 'quiz' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $student2->id, 'quiz' => $cm1->instance]));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(2, $DB->get_records('quiz_attempts', []));
        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $student1->id, 'quiz' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('quiz_attempts', ['userid' => $student2->id, 'quiz' => $cm1->instance]));
    }
}
