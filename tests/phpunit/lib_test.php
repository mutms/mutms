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

namespace mod_mubook\phpunit;

/**
 * Interactive book core API tests.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_mubook_delete_instance(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter1->id,
            'type' => 'markdown',
            'text' => 'Hola!',

        ]);
        $content2 = $generator->create_chapter_content([
            'chapterid' => $chapter1->id,
            'type' => 'markdown',
            'text' => 'hey!',
        ]);
        $content3 = $generator->create_chapter_content([
            'chapterid' => $chapter2->id,
            'type' => 'markdown',
            'text' => 'grr!',
        ]);

        mubook_delete_instance($mubook1->id);

        $this->assertFalse($DB->record_exists('mubook', ['id' => $mubook1->id]));
        $this->assertFalse($DB->record_exists('mubook_chapter', ['id' => $chapter1->id]));
        $this->assertFalse($DB->record_exists('mubook_content', ['id' => $content1->id]));
        $this->assertFalse($DB->record_exists('mubook_content', ['id' => $content2->id]));

        $this->assertTrue($DB->record_exists('mubook', ['id' => $mubook2->id]));
        $this->assertTrue($DB->record_exists('mubook_chapter', ['id' => $chapter2->id]));
        $this->assertTrue($DB->record_exists('mubook_content', ['id' => $content3->id]));
    }

    public function test_mubook_supports(): void {
        $this->assertFalse(mubook_supports(FEATURE_GROUPINGS));
        $this->assertFalse(mubook_supports(FEATURE_GROUPS));
    }
}
