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
 * Questionnaire activity purge test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\reset\mod_questionnaire
 */
final class mod_questionnaire_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (!get_config('mod_questionnaire', 'version')) {
            $this->markTestSkipped('mod_questionnaire is not installed');
        }
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \mod_questionnaire_generator $questionnairegenerator */
        $questionnairegenerator = $this->getDataGenerator()->get_plugin_generator('mod_questionnaire');

        $this->setAdminUser();
        $questionnairegenerator->create_and_fully_populate(2, 2, 2, 1);
        [$course1, $course2] = array_values($DB->get_records_select('course', "category > 0", [], 'id ASC'));
        [$student1, $student2] = array_values($DB->get_records_select('user', "username != 'guest' AND username != 'admin'", [], 'id ASC'));

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        // No need for real testing of unsupported modules, just make sure there are no errors.

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);
    }
}
