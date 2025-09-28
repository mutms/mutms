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
 * Choice activity purge test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\reset\mod_choice
 */
final class mod_choice_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \mod_choice_generator $choicegenerator */
        $choicegenerator = $this->getDataGenerator()->get_plugin_generator('mod_choice');

        $course1 = $this->getDataGenerator()->create_course();
        $choice1 = $choicegenerator->create_instance([
            'course' => $course1->id,
            'option' => ['one', 'two', 'three'],
        ]);
        $cm1 = get_coursemodule_from_instance('choice', $choice1->id);
        $context1 = \context_module::instance($cm1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $choice2 = $choicegenerator->create_instance([
            'course' => $course2->id,
            'option' => ['11', '22', '33'],
        ]);
        $cm2 = get_coursemodule_from_instance('choice', $choice2->id);
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
        $choicewithoptions = \choice_get_choice($choice1->id);
        $optionids = array_keys($choicewithoptions->option);
        \choice_user_submit_response($optionids[1], $choice1, $student1->id, $course1, $cm1);

        $this->setUser($student2);
        \choice_user_submit_response($optionids[2], $choice1, $student2->id, $course1, $cm1);

        $this->setUser($student1);
        $choicewithoptions = \choice_get_choice($choice2->id);
        $optionids = array_keys($choicewithoptions->option);
        \choice_user_submit_response($optionids[1], $choice2, $student1->id, $course2, $cm2);

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(3, $DB->get_records('choice_answers', []));
        $this->assertCount(1, $DB->get_records('choice_answers', ['userid' => $student1->id, 'choiceid' => $cm1->instance]));
        $this->assertCount(1, $DB->get_records('choice_answers', ['userid' => $student1->id, 'choiceid' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('choice_answers', ['userid' => $student2->id, 'choiceid' => $cm1->instance]));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(2, $DB->get_records('choice_answers', []));
        $this->assertCount(1, $DB->get_records('choice_answers', ['userid' => $student1->id, 'choiceid' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('choice_answers', ['userid' => $student2->id, 'choiceid' => $cm1->instance]));
    }
}
