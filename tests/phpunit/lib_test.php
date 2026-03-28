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

namespace tool_mutrain\phpunit;

/**
 * Training credits lib.php tests.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers \tool_mutrain_pre_course_category_delete()
     */
    public function test_tool_mutrain_pre_course_category_delete(): void {
        global $DB;

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $this->assertSame($category1->id, $category2->parent);

        $framework1 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework2 = $generator->create_framework(['contextid' => $catcontext2->id]);

        $this->assertSame((string)$catcontext1->id, $framework1->contextid);
        $this->assertSame('0', $framework1->archived);
        $this->assertSame((string)$catcontext2->id, $framework2->contextid);
        $this->assertSame('0', $framework2->archived);

        $category2->delete_full(false);
        $framework2 = $DB->get_record('tool_mutrain_framework', ['id' => $framework2->id], '*', MUST_EXIST);
        $this->assertSame((string)$catcontext1->id, $framework2->contextid);
        $this->assertSame('1', $framework2->archived);
        $framework1 = $DB->get_record('tool_mutrain_framework', ['id' => $framework1->id], '*', MUST_EXIST);
        $this->assertSame((string)$catcontext1->id, $framework1->contextid);
        $this->assertSame('0', $framework1->archived);
    }
}
