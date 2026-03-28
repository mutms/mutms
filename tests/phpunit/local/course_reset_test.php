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
 * Program course reset test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\course_reset
 */
final class course_reset_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_constants(): void {
        $this->assertSame(0, \tool_muprog\local\course_reset::RESETTYPE_NONE);
        $this->assertSame(1, \tool_muprog\local\course_reset::RESETTYPE_DEALLOCATE);
        $this->assertSame(2, \tool_muprog\local\course_reset::RESETTYPE_STANDARD);
        $this->assertSame(3, \tool_muprog\local\course_reset::RESETTYPE_FULL);
    }

    public function test_purge_standard(): void {
        global $DB, $CFG;

        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $DB->set_field('modules', 'visible', 1, []);
        $modules = $DB->get_records_menu('modules', [], 'id ASC', 'id, name');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $program = $generator->create_program([]);
        $item1 = $generator->create_program_item(['programid' => $program->id, 'courseid' => $course1->id]);
        $item2 = $generator->create_program_item(['programid' => $program->id, 'courseid' => $course2->id]);

        $params = ['course' => $course1->id];

        foreach ($modules as $module) {
            if (!file_exists("$CFG->dirroot/mod/$module/tests/generator/lib.php")) {
                // Totally unsupported modules without data generator.
                continue;
            }
            $this->getDataGenerator()->create_module($module, $params, []);
        }

        \tool_muprog\local\course_reset::purge_standard($user1, $program->id);
    }

    public function test_purge_full(): void {
        global $DB, $CFG;

        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $DB->set_field('modules', 'visible', 1, []);
        $modules = $DB->get_records_menu('modules', [], 'id ASC', 'id, name');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $program = $generator->create_program([]);
        $item1 = $generator->create_program_item(['programid' => $program->id, 'courseid' => $course1->id]);
        $item2 = $generator->create_program_item(['programid' => $program->id, 'courseid' => $course2->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $params = ['course' => $course1->id];

        foreach ($modules as $module) {
            if (!file_exists("$CFG->dirroot/mod/$module/tests/generator/lib.php")) {
                // Totally unsupported modules without data generator.
                continue;
            }
            $this->getDataGenerator()->create_module($module, $params, []);
        }

        \tool_muprog\local\course_reset::purge_full($user1, $program->id);
    }
}
