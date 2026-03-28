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

use tool_muprog\local\content\set;
use tool_muprog\local\content\top;

/**
 * Program set test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\content\set
 */
final class set_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('set', set::get_type());
    }

    public function test_get_type_name(): void {
        $this->assertSame('Set', set::get_type_name());
    }

    public function test_is_deletable(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $this->assertFalse($top->is_deletable());

        $courseitem1 = $top->append_course($top, $course1->id);
        $setitem1 = $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $setitem2 = $top->append_set($top, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $setitem3 = $top->append_set($top, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINANYORDER]);
        $courseitem2 = $top->append_course($setitem1, $course2->id);
        $courseitem3 = $top->append_course($setitem2, $course3->id);

        $this->assertFalse($setitem1->is_deletable());
        $this->assertFalse($setitem2->is_deletable());
        $this->assertTrue($setitem3->is_deletable());
    }
}
