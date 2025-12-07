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
// phpcs:disable moodle.Files.LineLength.MaxExceeded

namespace mod_mubook\phpunit\local;

use mod_mubook\local\toc;
use mod_mubook\local\chapter;

/**
 * Chapter test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\local\chapter
 */
final class chapter_test extends \advanced_testcase {
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
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $ch = $generator->create_chapter(['mubookid' => $mubook->id]);

        $record = $DB->get_record('mubook_chapter', ['id' => $ch->id], '*', MUST_EXIST);
        $chapter = new chapter($record, $mubook, $context);
        $this->assertEquals($record, $chapter->get_record());
        $this->assertSame($mubook, $chapter->get_mubook());
        $this->assertSame($context, $chapter->get_context());
    }

    public function test_create(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);

        $this->setUser();

        $this->setCurrentTimeStart();
        $chapter1 = chapter::create((object)[
            'mubookid' => $mubook1->id,
            'title' => 'First chapter',
        ]);
        $this->assertInstanceOf(chapter::class, $chapter1);
        $this->assertSame($mubook1->id, $chapter1->mubookid);
        $this->assertSame(null, $chapter1->parentid);
        $this->assertSame('First chapter', $chapter1->title);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame(null, $chapter1->originjson);
        $this->assertTimeCurrent($chapter1->timecreated);
        $this->assertTimeCurrent($chapter1->timemodified);

        $chapter3 = chapter::create((object)[
            'mubookid' => $mubook1->id,
            'title' => 'Third chapter',
            'position' => $chapter1->id,
        ]);
        $chapter1 = self::refetch_chapter($chapter1);
        $this->assertSame($mubook1->id, $chapter3->mubookid);
        $this->assertSame(null, $chapter3->parentid);
        $this->assertSame('Third chapter', $chapter3->title);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame('2', $chapter3->sortorder);

        $chapter2 = chapter::create((object)[
            'mubookid' => $mubook1->id,
            'title' => 'Second chapter',
            'position' => $chapter1->id,
        ]);
        $chapter1 = self::refetch_chapter($chapter1);
        $chapter3 = self::refetch_chapter($chapter3);
        $this->assertSame($mubook1->id, $chapter2->mubookid);
        $this->assertSame(null, $chapter2->parentid);
        $this->assertSame('Second chapter', $chapter2->title);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame('2', $chapter2->sortorder);
        $this->assertSame('3', $chapter3->sortorder);

        $subchapter1x3 = chapter::create((object)[
            'mubookid' => $mubook1->id,
            'title' => 'Sub chapter 1x3',
            'subchapter' => 1,
            'position' => $chapter1->id,
        ]);
        $this->assertSame($mubook1->id, $subchapter1x3->mubookid);
        $this->assertSame($chapter1->id, $subchapter1x3->parentid);
        $this->assertSame('Sub chapter 1x3', $subchapter1x3->title);
        $this->assertSame('1', $subchapter1x3->sortorder);
        $chapter1 = self::refetch_chapter($chapter1);
        $chapter2 = self::refetch_chapter($chapter2);
        $chapter3 = self::refetch_chapter($chapter3);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame('2', $chapter2->sortorder);
        $this->assertSame('3', $chapter3->sortorder);

        $subchapter1x1 = chapter::create((object)[
            'mubookid' => $mubook1->id,
            'title' => 'Sub chapter 1x1',
            'subchapter' => 1,
            'position' => $chapter1->id,
        ]);
        $this->assertSame($mubook1->id, $subchapter1x1->mubookid);
        $this->assertSame($chapter1->id, $subchapter1x1->parentid);
        $this->assertSame('Sub chapter 1x1', $subchapter1x1->title);
        $this->assertSame('1', $subchapter1x1->sortorder);
        $chapter1 = self::refetch_chapter($chapter1);
        $subchapter1x3 = self::refetch_chapter($subchapter1x3);
        $chapter2 = self::refetch_chapter($chapter2);
        $chapter3 = self::refetch_chapter($chapter3);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame('2', $subchapter1x3->sortorder);
        $this->assertSame('2', $chapter2->sortorder);
        $this->assertSame('3', $chapter3->sortorder);

        $subchapter1x2 = chapter::create((object)[
            'mubookid' => $mubook1->id,
            'title' => 'Sub chapter 1x2',
            'subchapter' => 1,
            'position' => $subchapter1x1->id,
        ]);
        $this->assertSame($mubook1->id, $subchapter1x2->mubookid);
        $this->assertSame($chapter1->id, $subchapter1x2->parentid);
        $this->assertSame('Sub chapter 1x2', $subchapter1x2->title);
        $this->assertSame('2', $subchapter1x2->sortorder);
        $chapter1 = self::refetch_chapter($chapter1);
        $subchapter1x1 = self::refetch_chapter($subchapter1x1);
        $subchapter1x3 = self::refetch_chapter($subchapter1x3);
        $chapter2 = self::refetch_chapter($chapter2);
        $chapter3 = self::refetch_chapter($chapter3);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame('1', $subchapter1x1->sortorder);
        $this->assertSame('3', $subchapter1x3->sortorder);
        $this->assertSame('2', $chapter2->sortorder);
        $this->assertSame('3', $chapter3->sortorder);
    }

    public function test_magic_methods(): void {
        global $DB;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);

        $record = $DB->get_record('mubook_chapter', ['id' => $chapter->id], '*', MUST_EXIST);
        foreach ((array)$record as $k => $v) {
            $this->assertSame($chapter->{$k}, $v);
        }
    }

    public function test_update(): void {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mubook1 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $mubook2 = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);

        $this->setUser();

        $oldchapter1 = chapter::create((object)[
            'mubookid' => $mubook1->id,
            'title' => 'First chapter',
        ]);
        $this->setCurrentTimeStart();
        $chapter1 = chapter::update((object)[
            'id' => $oldchapter1->id,
            'title' => 'Prvni kapitola',
        ]);
        $this->assertSame($oldchapter1->id, $chapter1->id);
        $this->assertSame($mubook1->id, $chapter1->mubookid);
        $this->assertSame(null, $chapter1->parentid);
        $this->assertSame('Prvni kapitola', $chapter1->title);
        $this->assertSame('1', $chapter1->sortorder);
        $this->assertSame(null, $chapter1->originjson);
        $this->assertSame($oldchapter1->timecreated, $chapter1->timecreated);
        $this->assertTimeCurrent($chapter1->timemodified);
    }

    public function test_delete(): void {
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

        $this->assertCount(9, $DB->get_records('mubook_chapter', ['mubookid' => $mubook1->id]));

        chapter::delete($orphaned1->id, false);
        $this->assertCount(8, $DB->get_records('mubook_chapter', ['mubookid' => $mubook1->id]));
        $this->assertFalse($DB->record_exists('mubook_chapter', ['id' => $orphaned1->id]));

        chapter::delete($chapter1->id, true);
        $this->assertCount(4, $DB->get_records('mubook_chapter', ['mubookid' => $mubook1->id]));
        $this->assertFalse($DB->record_exists('mubook_chapter', ['id' => $chapter1->id]));
        $this->assertFalse($DB->record_exists('mubook_chapter', ['id' => $subchapter1x1->id]));
        $this->assertFalse($DB->record_exists('mubook_chapter', ['id' => $subchapter1x2->id]));
        $this->assertFalse($DB->record_exists('mubook_chapter', ['id' => $subchapter1x3->id]));
        $this->assertTrue($DB->record_exists('mubook_chapter', ['id' => $orphaned2->id]));

        chapter::delete($chapter3->id, false);
        $this->assertCount(3, $DB->get_records('mubook_chapter', ['mubookid' => $mubook1->id]));
        $this->assertFalse($DB->record_exists('mubook_chapter', ['id' => $chapter3->id]));
        $this->assertTrue($DB->record_exists('mubook_chapter', ['id' => $orphaned2->id]));
        $this->assertTrue($DB->record_exists('mubook_chapter', ['id' => $chapter2->id]));
        $this->assertTrue($DB->record_exists('mubook_chapter', ['id' => $subchapter3x1->id]));
        $this->assertTrue($DB->record_exists('mubook_chapter', ['id' => $chapterx->id]));
    }

    public function test_move(): void {
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

        $subchapter1x1 = chapter::move($subchapter1x1->id, true, $subchapter1x2->id);
        $subchapter1x1 = self::refetch_chapter($subchapter1x1);
        $subchapter1x2 = self::refetch_chapter($subchapter1x2);
        $subchapter1x3 = self::refetch_chapter($subchapter1x3);
        $this->assertSame($chapter1->id, $subchapter1x1->parentid);
        $this->assertSame($chapter1->id, $subchapter1x2->parentid);
        $this->assertSame($chapter1->id, $subchapter1x3->parentid);
        $this->assertSame('1', $subchapter1x2->sortorder);
        $this->assertSame('2', $subchapter1x1->sortorder);
        $this->assertSame('3', $subchapter1x3->sortorder);

        $subchapter1x1 = chapter::move($subchapter1x1->id, true, $chapter1->id);
        $subchapter1x1 = self::refetch_chapter($subchapter1x1);
        $subchapter1x2 = self::refetch_chapter($subchapter1x2);
        $subchapter1x3 = self::refetch_chapter($subchapter1x3);
        $this->assertSame($chapter1->id, $subchapter1x1->parentid);
        $this->assertSame($chapter1->id, $subchapter1x2->parentid);
        $this->assertSame($chapter1->id, $subchapter1x3->parentid);
        $this->assertSame('1', $subchapter1x1->sortorder);
        $this->assertSame('2', $subchapter1x2->sortorder);
        $this->assertSame('3', $subchapter1x3->sortorder);

        $subchapter2x2 = chapter::move($subchapter1x2->id, true, $chapter2->id);
        $subchapter1x1 = self::refetch_chapter($subchapter1x1);
        $subchapter1x3 = self::refetch_chapter($subchapter1x3);
        $this->assertSame($subchapter1x2->id, $subchapter2x2->id);
        $this->assertSame($chapter2->id, $subchapter2x2->parentid);
        $this->assertSame('1', $subchapter2x2->sortorder);
        $this->assertSame('1', $subchapter1x1->sortorder);
        $this->assertSame('2', $subchapter1x3->sortorder);

        $subchapter2x1 = chapter::move($orphaned1->id, true, $chapter2->id);
        $subchapter2x2 = self::refetch_chapter($subchapter2x2);
        $this->assertSame($orphaned1->id, $subchapter2x1->id);
        $this->assertSame($chapter2->id, $subchapter2x1->parentid);
        $this->assertSame('1', $subchapter2x1->sortorder);
        $this->assertSame('2', $subchapter2x2->sortorder);

        $subchapter2x3 = chapter::move($orphaned2->id, true, $subchapter2x2->id);
        $subchapter2x1 = self::refetch_chapter($subchapter2x1);
        $subchapter2x2 = self::refetch_chapter($subchapter2x2);
        $this->assertSame($orphaned2->id, $subchapter2x3->id);
        $this->assertSame($chapter2->id, $subchapter2x3->parentid);
        $this->assertSame('1', $subchapter2x1->sortorder);
        $this->assertSame('2', $subchapter2x2->sortorder);
        $this->assertSame('3', $subchapter2x3->sortorder);

        $chapter4 = chapter::move($subchapter2x1->id, false, 0);
        $this->assertSame($subchapter2x1->id, $chapter4->id);
        $this->assertSame(null, $chapter4->parentid);
        $this->assertSame('1', $chapter4->sortorder);
        $chapter1 = self::refetch_chapter($chapter1);
        $chapter2 = self::refetch_chapter($chapter2);
        $chapter3 = self::refetch_chapter($chapter3);
        $subchapter2x2 = self::refetch_chapter($subchapter2x2);
        $subchapter2x3 = self::refetch_chapter($subchapter2x3);
        $this->assertSame('2', $chapter1->sortorder);
        $this->assertSame('3', $chapter2->sortorder);
        $this->assertSame('4', $chapter3->sortorder);
        $this->assertSame('2', $subchapter2x3->sortorder);
        $this->assertSame('1', $subchapter2x2->sortorder);
        $this->assertSame('2', $subchapter2x3->sortorder);
    }

    public function test_get_record(): void {
        global $DB;

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $this->setUser();

        $chapter = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);

        $expected = $DB->get_record('mubook_chapter', ['id' => $chapter->id], '*', MUST_EXIST);
        $record = $chapter->get_record();
        $this->assertInstanceOf(\stdClass::class, $record);
        $this->assertEquals($expected, $record);
    }

    public function test_get_mubook(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        unset($mubook->cmid);
        $this->setUser();

        $chapter = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);

        $this->assertEquals($mubook, $chapter->get_mubook());
    }

    public function test_context(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $this->setUser();

        $chapter = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);

        $expected = \context_module::instance($cm->id);
        $context = $chapter->get_context();

        $this->assertInstanceOf(\context_module::class, $context);
        $this->assertSame($expected->id, $context->id);
    }

    public function test_can_create(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        assign_capability('mod/mubook:editchapter', CAP_ALLOW, $editorroleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);

        $this->setUser($user1);
        $this->assertTrue(chapter::can_create($mubook, $context));

        $this->setUser($user2);
        $this->assertFalse(chapter::can_create($mubook, $context));

        $this->setAdminUser();
        $this->assertTrue(chapter::can_create($mubook, $context));
    }

    public function test_can_update(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $chapter = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        assign_capability('mod/mubook:editchapter', CAP_ALLOW, $editorroleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);

        $this->setUser($user1);
        $this->assertTrue($chapter->can_update());

        $this->setUser($user2);
        $this->assertFalse($chapter->can_update());

        $this->setAdminUser();
        $this->assertTrue($chapter->can_update());
    }

    public function test_can_move(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $chapter = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        assign_capability('mod/mubook:editchapter', CAP_ALLOW, $editorroleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);

        $this->setUser($user1);
        $this->assertTrue($chapter->can_move());

        $this->setUser($user2);
        $this->assertFalse($chapter->can_move());

        $this->setAdminUser();
        $this->assertTrue($chapter->can_move());
    }

    public function test_can_delete(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $chapter = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        assign_capability('mod/mubook:editchapter', CAP_ALLOW, $editorroleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);

        $this->setUser($user1);
        $this->assertTrue($chapter->can_delete());

        $this->setUser($user2);
        $this->assertFalse($chapter->can_delete());

        $this->setAdminUser();
        $this->assertTrue($chapter->can_delete());
    }

    public function test_get_create_link(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $chapter1 = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
        ]);
        $chapter2 = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter',
            'position' => $chapter1->id,
        ]);

        $page = new \moodle_page();
        $rbase = new \renderer_base($page, "/");

        $link = chapter::get_create_link($mubook, 0, false);
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_create.php?mubookid={$mubook->id}&subchapter=0&position=0",
            $link->export_for_template($rbase)['formurl']
        );

        $link = chapter::get_create_link($mubook, $chapter2->id, false, $chapter1->id);
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_create.php?mubookid={$mubook->id}&subchapter=0&position={$chapter2->id}&fromcreatechapterid={$chapter1->id}",
            $link->export_for_template($rbase)['formurl']
        );

        $link = chapter::get_create_link($mubook, $chapter2->id, true, $chapter1->id);
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_create.php?mubookid={$mubook->id}&subchapter=1&position={$chapter2->id}&fromcreatechapterid={$chapter1->id}",
            $link->export_for_template($rbase)['formurl']
        );
    }

    public function test_get_update_link(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

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

        $page = new \moodle_page();
        $rbase = new \renderer_base($page, "/");

        $link = $chapter1->get_update_link();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_update.php?id={$chapter1->id}",
            $link->export_for_template($rbase)['formurl']
        );

        $link = $chapter2->get_update_link();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_update.php?id={$chapter2->id}",
            $link->export_for_template($rbase)['formurl']
        );
    }

    public function test_get_move_link(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

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

        $page = new \moodle_page();
        $rbase = new \renderer_base($page, "/");

        $link = $chapter1->get_move_link();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_move.php?id={$chapter1->id}",
            $link->export_for_template($rbase)['formurl']
        );

        $link = $chapter2->get_move_link();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_move.php?id={$chapter2->id}",
            $link->export_for_template($rbase)['formurl']
        );
    }

    public function test_get_delete_link(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

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

        $page = new \moodle_page();
        $rbase = new \renderer_base($page, "/");

        $link = $chapter1->get_delete_link();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_delete.php?id={$chapter1->id}",
            $link->export_for_template($rbase)['formurl']
        );

        $link = $chapter2->get_delete_link();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/chapter_delete.php?id={$chapter2->id}",
            $link->export_for_template($rbase)['formurl']
        );
    }

    public function test_format_title(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $chapter1 = chapter::create((object)[
            'mubookid' => $mubook->id,
            'title' => 'First chapter &<div>',
        ]);

        $this->assertSame('First chapter &#38;', $chapter1->format_title());
    }

    public function test_get_contents(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter1 = $generator->create_chapter(['mubookid' => $mubook->id]);
        $chapter2 = $generator->create_chapter(['mubookid' => $mubook->id]);

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

        $chapter1 = self::refetch_chapter($chapter1);
        $toc = new toc($mubook);

        $contents = $chapter1->get_contents();
        $this->assertSame([(int)$content1->id, (int)$content2->id], array_keys($contents));
        $this->assertSame('Hola!', $contents[$content1->id]->data1);
        $this->assertSame('hey!', $contents[$content2->id]->data1);
    }

    /**
     * Create new instance of chapter with current record.
     *
     * @param chapter $chapter
     * @return chapter
     */
    protected static function refetch_chapter(chapter $chapter): chapter {
        global $DB;

        $record = $DB->get_record('mubook_chapter', ['id' => $chapter->id], '*', MUST_EXIST);
        return new chapter($record, $chapter->get_mubook(), $chapter->get_context());
    }
}
