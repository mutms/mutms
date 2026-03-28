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
// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

namespace tool_muprog\phpunit\local\content;

use tool_muprog\local\content\course;
use tool_muprog\local\content\set;
use tool_muprog\local\content\top;

/**
 * Program course test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\content\course
 */
final class course_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('course', course::get_type());
    }

    public function test_get_type_name(): void {
        $this->assertSame('Course', course::get_type_name());
    }

    public function test_is_deletable(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $this->assertFalse($top->is_deletable());

        $courseitem1 = $top->append_course($top, $course1->id);
        $setitem1 = $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $courseitem2 = $top->append_course($setitem1, $course2->id);

        $this->assertTrue($courseitem1->is_deletable());
        $this->assertTrue($courseitem2->is_deletable());
    }
}
