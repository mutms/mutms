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

namespace mod_mubook\phpunit\local;

use mod_mubook\local\toc;
use mod_mubook\local\chapter;

/**
 * TOC test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\local\toc
 */
final class toc_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_constructor(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $toc = new toc($mubook1);
        $this->assertCount(7, $toc->get_chapters());
        $this->assertCount(2, $toc->get_orphaned_chapters());

        $toc = new toc($mubook2);
        $this->assertCount(0, $toc->get_chapters());
        $this->assertCount(0, $toc->get_orphaned_chapters());
    }

    public function test_get_chapters(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $toc = new toc($mubook1);

        $chapters = $toc->get_chapters();
        $this->assertCount(7, $chapters);

        $expected = [$chapter1->id, $subchapter1x1->id, $subchapter1x2->id, $subchapter1x3->id, $chapter2->id, $chapter3->id, $subchapter3x1->id];
        $expected = array_map('intval', $expected);
        $this->assertSame(
            $expected,
            array_keys($chapters)
        );
        foreach ($chapters as $k => $chapter) {
            $this->assertInstanceOf(chapter::class, $chapter);
            $this->assertEquals($k, $chapter->id);
        }
    }

    public function test_get_mubook(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);

        $toc = new toc($mubook1);
        $this->assertSame($mubook1, $toc->get_mubook());

        $toc = new toc($mubook2);
        $this->assertSame($mubook2, $toc->get_mubook());
    }

    public function test_get_context(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $toc = new toc($mubook1);
        $context = $toc->get_context();
        $this->assertInstanceOf(\core\context\module::class, $context);
        $cm = get_coursemodule_from_id('mubook', $context->instanceid, 0, false, MUST_EXIST);
        $this->assertEquals($mubook1->id, $cm->instance);
    }

    public function test_get_orphaned_chapters(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $toc = new toc($mubook1);
        $orphaned = $toc->get_orphaned_chapters();
        $this->assertCount(2, $orphaned);
        $this->assertSame($orphaned1->id, $orphaned[$orphaned1->id]->id);
        $this->assertSame($orphaned2->id, $orphaned[$orphaned2->id]->id);
        $this->assertInstanceOf(chapter::class, $orphaned[$orphaned1->id]);
        $this->assertInstanceOf(chapter::class, $orphaned[$orphaned2->id]);
    }

    public function test_is_orphaned_chapter(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $toc = new toc($mubook1);

        $this->assertFalse($toc->is_orphaned_chapter($chapter1->id));
        $this->assertFalse($toc->is_orphaned_chapter($subchapter1x2->id));
        $this->assertTrue($toc->is_orphaned_chapter($orphaned1->id));
        $this->assertTrue($toc->is_orphaned_chapter($orphaned2->id));
        $this->assertFalse($toc->is_orphaned_chapter($chapterx->id)); // Other book!
    }

    public function test_get_chapter(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $toc = new toc($mubook1);

        $chapter = $toc->get_chapter($chapter1->id);
        $this->assertEquals($chapter1->get_record(), $chapter->get_record());
        $chapter = $toc->get_chapter($subchapter1x2->id);
        $this->assertSame($subchapter1x2->id, $chapter->id);
        $chapter = $toc->get_chapter($orphaned1->id);
        $this->assertSame($orphaned1->id, $chapter->id);
        $chapter = $toc->get_chapter($chapterx->id, IGNORE_MISSING);
        $this->assertSame(null, $chapter);
        try {
            $chapter = $toc->get_chapter($chapterx->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('invalid book chapter', $ex->debuginfo);
        }
    }

    public function test_get_first_chapter(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $toc = new toc($mubook1);

        $chapter = $toc->get_first_chapter();
        $this->assertSame($chapter1->id, $chapter->id);
        $this->assertInstanceOf(chapter::class, $chapter);

        $toc = new toc($mubook2);

        $chapter = $toc->get_first_chapter();
        $this->assertSame(null, $chapter);
    }

    public function test_get_last_chapter(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $toc = new toc($mubook1);

        $chapter = $toc->get_last_chapter();
        $this->assertSame($subchapter3x1->id, $chapter->id);
        $this->assertInstanceOf(chapter::class, $chapter);

        $toc = new toc($mubook2);

        $chapter = $toc->get_last_chapter();
        $this->assertSame(null, $chapter);
    }

    public function test_get_last_subchapter(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $toc = new toc($mubook1);

        $chapter = $toc->get_last_subchapter($chapter1->id);
        $this->assertSame($subchapter1x3->id, $chapter->id);
        $this->assertInstanceOf(chapter::class, $chapter);

        $chapter = $toc->get_last_subchapter($chapter2->id);
        $this->assertSame(null, $chapter);

        $chapter = $toc->get_last_subchapter($subchapter1x2->id);
        $this->assertSame(null, $chapter);

        $chapter = $toc->get_last_subchapter($chapterx->id);
        $this->assertSame(null, $chapter);
    }

    public function test_get_previous_chapter(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $toc = new toc($mubook1);
        $previous = null;
        foreach ($toc->get_chapters() as $chapter) {
            $this->assertSame($previous, $toc->get_previous_chapter($chapter->id));
            $previous = $chapter;
        }

        $this->assertNull($toc->get_previous_chapter($orphaned1->id));
        $this->assertNull($toc->get_previous_chapter($orphaned2->id));
    }

    public function test_get_next_chapter(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $toc = new toc($mubook1);
        $previous = null;
        foreach ($toc->get_chapters() as $chapter) {
            if ($previous !== null) {
                $this->assertSame($chapter, $toc->get_next_chapter($previous->id));
            }
            $previous = $chapter;
        }
        $this->assertNull($toc->get_next_chapter($chapter->id));
        $this->assertNull($toc->get_next_chapter($orphaned1->id));
        $this->assertNull($toc->get_next_chapter($orphaned2->id));
    }

    public function test_get_numbering_menu(): void {
        $menu = toc::get_numbering_menu();
        $this->assertSame(
            [
                0 => 'None',
                1 => '1, 1.1, 1.2',
                2 => '1., 1.1., 1.2.',
            ],
            $menu
        );
    }

    public function test_get_chapter_numbers(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $toc = new toc($mubook1);

        $this->assertSame([1], $toc->get_chapter_numbers($chapter1->id));
        $this->assertSame([1, 1], $toc->get_chapter_numbers($subchapter1x1->id));
        $this->assertSame([1, 2], $toc->get_chapter_numbers($subchapter1x2->id));
        $this->assertSame([1, 3], $toc->get_chapter_numbers($subchapter1x3->id));
        $this->assertSame([2], $toc->get_chapter_numbers($chapter2->id));
        $this->assertSame([3], $toc->get_chapter_numbers($chapter3->id));
        $this->assertSame([3, 1], $toc->get_chapter_numbers($subchapter3x1->id));
        $this->assertSame([], $toc->get_chapter_numbers($orphaned1->id));
        $this->assertSame([], $toc->get_chapter_numbers($orphaned2->id));
        $this->assertSame([], $toc->get_chapter_numbers($chapterx->id));
    }

    public function test_format_chapter_numbers(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id, 'numbering' => 1]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        $toc = new toc($mubook1);
        $this->assertSame('1', $toc->format_chapter_numbers($chapter1->id));
        $this->assertSame('1.3', $toc->format_chapter_numbers($subchapter1x3->id));
        $this->assertSame('2', $toc->format_chapter_numbers($chapter2->id));
        $this->assertSame(null, $toc->format_chapter_numbers($orphaned1->id));

        $DB->set_field('mubook', 'numbering', 0, ['id' => $mubook1->id]);
        $mubook1 = $DB->get_record('mubook', ['id' => $mubook1->id]);
        $toc = new toc($mubook1);
        $this->assertSame(null, $toc->format_chapter_numbers($chapter1->id));
        $this->assertSame(null, $toc->format_chapter_numbers($subchapter1x3->id));
        $this->assertSame(null, $toc->format_chapter_numbers($chapter2->id));
        $this->assertSame(null, $toc->format_chapter_numbers($orphaned1->id));

        $DB->set_field('mubook', 'numbering', 2, ['id' => $mubook1->id]);
        $mubook1 = $DB->get_record('mubook', ['id' => $mubook1->id]);
        $toc = new toc($mubook1);
        $this->assertSame('1.', $toc->format_chapter_numbers($chapter1->id));
        $this->assertSame('1.3.', $toc->format_chapter_numbers($subchapter1x3->id));
        $this->assertSame('2.', $toc->format_chapter_numbers($chapter2->id));
        $this->assertSame(null, $toc->format_chapter_numbers($orphaned1->id));
    }

    public function test_get_numbered_chapter_title(): void {
        global $DB;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', [
            'course' => $course->id,
            'numbering' => '0',
        ]);

        $chapter1 = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);
        $chapter2 = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'Sub chapter',
            'subchapter' => 1,
            'position' => $chapter1->id,
        ]);

        $toc = new toc($mubook);
        $this->assertSame('First chapter', $toc->get_numbered_chapter_title($chapter1->id));
        $this->assertSame('Sub chapter', $toc->get_numbered_chapter_title($chapter2->id));

        $DB->set_field('mubook', 'numbering', 1, ['id' => $mubook->id]);
        $mubook = $DB->get_record('mubook', ['id' => $mubook->id]);
        $toc = new toc($mubook);
        $this->assertSame('1 First chapter', $toc->get_numbered_chapter_title($chapter1->id));
        $this->assertSame('1.1 Sub chapter', $toc->get_numbered_chapter_title($chapter2->id));

        $DB->set_field('mubook', 'numbering', 2, ['id' => $mubook->id]);
        $mubook = $DB->get_record('mubook', ['id' => $mubook->id]);
        $toc = new toc($mubook);
        $this->assertSame('1. First chapter', $toc->get_numbered_chapter_title($chapter1->id));
        $this->assertSame('1.1. Sub chapter', $toc->get_numbered_chapter_title($chapter2->id));
    }

    public function test_fix_sortorders(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id, 'numbering' => 1]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter1 = $generator->create_chapter(['mubookid' => $mubook1->id]);
        $subchapter1x2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter1->id]);
        $subchapter1x3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $subchapter1x2->id]);
        $chapter3 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $subchapter3x1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'subchapter' => true, 'position' => $chapter3->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter1->id]);
        $orphaned1 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $orphaned2 = $generator->create_chapter(['mubookid' => $mubook1->id, 'position' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'parentid', 99999999, ['id' => $orphaned1->id]);
        $DB->set_field('mubook_chapter', 'parentid', $subchapter1x2->id, ['id' => $orphaned2->id]);

        $chapterx = $generator->create_chapter(['mubookid' => $mubook2->id]);

        // No changes if everything ok.
        $writes = $DB->perf_get_writes();
        $oldchapters = $DB->get_records('mubook_chapter', [], 'id ASC');
        toc::fix_sortorders($mubook1->id);
        $newchapters = $DB->get_records('mubook_chapter', [], 'id ASC');
        $this->assertEquals($oldchapters, $newchapters);
        $this->assertSame(0, $DB->perf_get_writes() - $writes);

        $DB->set_field('mubook_chapter', 'sortorder', 1111, ['id' => $subchapter1x3->id]);
        $DB->set_field('mubook_chapter', 'sortorder', 2222, ['id' => $chapter3->id]);
        $DB->set_field('mubook_chapter', 'sortorder', 0, ['id' => $chapter1->id]);
        toc::fix_sortorders($mubook1->id);
        $newchapters = $DB->get_records('mubook_chapter', [], 'id ASC');
        $this->assertEquals($oldchapters, $newchapters);
    }
}
