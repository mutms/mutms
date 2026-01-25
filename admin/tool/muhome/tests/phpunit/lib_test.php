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
// phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing

namespace tool_muhome\phpunit;

use tool_muhome\local\page;

/**
 * Standard plugin API tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_tool_muhome_pre_course_category_delete(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $categorycontext2 = \context_coursecat::instance($category2->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $page0 = $generator->create_page([
            'contextid' => $syscontext->id,
            'uservisible' => 1,
            'status' => page::STATUS_DRAFT,
        ]);
        $page1 = $generator->create_page([
            'contextid' => $categorycontext1->id,
            'status' => page::STATUS_DRAFT,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $page2 = $generator->create_page([
            'contextid' => $categorycontext2->id,
            'status' => page::STATUS_DRAFT,
            'uservisible' => 0,
            'cohortvisible' => [$cohort3->id],
        ]);

        $category1->delete_full(false);

        $this->assertFalse($DB->record_exists('tool_muhome_page', ['id' => $page1->id]));
        $this->assertFalse($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page1->id]));
        $this->assertEquals($page0, $DB->get_record('tool_muhome_page', ['id' => $page0->id]));
        $this->assertEquals($page2, $DB->get_record('tool_muhome_page', ['id' => $page2->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id]));
    }
}
