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

namespace mod_mubook\phpunit;

use mod_mubook\local\chapter;
use mod_mubook\local\content;

/**
 * Interactive book generator test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create_instance(): void {
        $course1 = $this->getDataGenerator()->create_course();

        $this->setAdminUser();

        $this->setCurrentTimeStart();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course1->id]);
        $this->assertSame($course1->id, $mubook1->course);
        $this->assertSame('Interactive book 1', $mubook1->name);
        $this->assertSame('Test mubook 1', $mubook1->intro);
        $this->assertSame('0', $mubook1->introformat);
        $this->assertSame('1', $mubook1->numbering);
        $this->assertSame('1', $mubook1->markdownhtml);
        $this->assertSame('html', $mubook1->contentdefault);
        $this->assertTimeCurrent($mubook1->timecreated);
        $this->assertTimeCurrent($mubook1->timemodified);

        $cm1 = get_coursemodule_from_instance('mubook', $mubook1->id, $mubook1->course, false, MUST_EXIST);
        $this->assertSame((int)$cm1->id, $mubook1->cmid);

        $this->setCurrentTimeStart();
        $mubook2 = $this->getDataGenerator()->create_module('mubook', [
            'course' => $course1->id,
            'name' => 'Sample book',
            'intro' => 'Sample intro',
            'introformat' => FORMAT_HTML,
            'numbering' => '2',
            'markdownhtml' => '2',
            'contentdefault' => 'markdown',
        ]);
        $this->assertSame($course1->id, $mubook2->course);
        $this->assertSame('Sample book', $mubook2->name);
        $this->assertSame('Sample intro', $mubook2->intro);
        $this->assertSame('1', $mubook2->introformat);
        $this->assertSame('2', $mubook2->numbering);
        $this->assertSame('2', $mubook2->markdownhtml);
        $this->assertSame('markdown', $mubook2->contentdefault);
        $this->assertTimeCurrent($mubook2->timecreated);
        $this->assertTimeCurrent($mubook2->timemodified);

        $cm2 = get_coursemodule_from_instance('mubook', $mubook2->id, $mubook2->course, false, MUST_EXIST);
        $this->assertSame((int)$cm2->id, $mubook2->cmid);
    }

    public function test_create_chapter(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);

        $this->setCurrentTimeStart();
        $chapter1 = $generator->create_chapter([
            'mubookid' => $mubook->id,
        ]);
        $this->assertInstanceOf(chapter::class, $chapter1);
        $this->assertSame($mubook->id, $chapter1->mubookid);
        $this->assertSame(null, $chapter1->parentid);
        $this->assertSame('Chapter 1', $chapter1->title);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame(null, $chapter1->originjson);
        $this->assertTimeCurrent($chapter1->timecreated);
        $this->assertTimeCurrent($chapter1->timemodified);

        $chapter2 = $generator->create_chapter([
            'mubookid' => $mubook->id,
            'subchapter' => 1,
            'position' => $chapter1->id,
            'title' => 'Chapter XXX 2',
        ]);
        $this->assertSame($mubook->id, $chapter2->mubookid);
        $this->assertSame($chapter1->id, $chapter2->parentid);
        $this->assertSame('Chapter XXX 2', $chapter2->title);
        $this->assertSame('1', $chapter2->sortorder);
        $this->assertSame(null, $chapter2->originjson);

        $chapter3 = $generator->create_chapter([
            'mubookid' => $mubook->id,
        ]);
        $this->assertInstanceOf(chapter::class, $chapter3);
        $this->assertSame($mubook->id, $chapter3->mubookid);
        $this->assertSame(null, $chapter3->parentid);
        $this->assertSame('Chapter 3', $chapter3->title);
        $this->assertSame('2', $chapter3->sortorder);
        $this->assertSame(null, $chapter3->originjson);
    }

    public function test_create_chapter_content(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'html',
            'text' => '<p>Hola!</p>',
        ]);
        $this->assertInstanceOf(\mod_mubook\local\content\html::class, $content1);
        $this->assertSame('html', $content1->type);
        $this->assertSame($chapter->id, $content1->chapterid);
        $this->assertSame('1', $content1->sortorder);
        $this->assertSame('<p>Hola!</p>', $content1->data1);
        $this->assertSame(null, $content1->data2);
        $this->assertSame(null, $content1->data3);
        $this->assertSame(null, $content1->auxint1);
        $this->assertSame(null, $content1->auxint2);
        $this->assertSame(null, $content1->auxint3);
        $this->assertSame(null, $content1->unsafetrusted);
        $this->assertSame('0', $content1->hidden);
        $this->assertSame(null, $content1->groupid);
        $this->assertSame(null, $content1->originjson);

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);
        $this->assertInstanceOf(\mod_mubook\local\content\unknown::class, $content1);
        $this->assertSame('xyzunknowncyz', $content1->type);
        $this->assertSame($chapter->id, $content1->chapterid);
        $this->assertSame('2', $content1->sortorder);
        $this->assertSame(null, $content1->data1);
        $this->assertSame(null, $content1->data2);
        $this->assertSame(null, $content1->data3);
        $this->assertSame(null, $content1->auxint1);
        $this->assertSame(null, $content1->auxint2);
        $this->assertSame(null, $content1->auxint3);
        $this->assertSame(null, $content1->unsafetrusted);
        $this->assertSame('0', $content1->hidden);
        $this->assertSame(null, $content1->groupid);
        $this->assertSame(null, $content1->originjson);
    }
}
