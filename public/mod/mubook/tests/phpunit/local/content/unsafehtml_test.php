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

namespace mod_mubook\phpunit\local\content;

use mod_mubook\local\content;
use mod_mubook\local\content\unsafehtml;

/**
 * Unsafe raw html content test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.unsafehtml GNU GPL v3 or later
 *
 * @covers \mod_mubook\local\content\unsafehtml
 */
final class unsafehtml_test extends \advanced_testcase {
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
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        /** @var unsafehtml $content1 */
        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unsafehtml',
            'text' => '<div>Hola!</div>',
        ]);

        $record = $DB->get_record('mubook_content', ['id' => $content1->id], '*', MUST_EXIST);
        $content = new unsafehtml($record, $chapter, $mubook, $chapter->get_context());
        $this->assertEquals($record, $content->get_record());
    }

    public function test_get_type(): void {
        $this->assertSame('unsafehtml', unsafehtml::get_type());
    }

    public function test_get_name(): void {
        $this->assertSame('Unsafe raw HTML', unsafehtml::get_name());
    }

    public function test_get_identification(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        /** @var unsafehtml $content1 */
        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unsafehtml',
            'text' => '<div>Hola!</div>',
        ]);

        $this->assertSame('1 - Unsafe raw HTML - Hola!', $content1->get_identification());
    }

    public function test_is_unsafe(): void {
        $this->assertTrue(unsafehtml::is_unsafe());
    }

    public function test_get_file_areas(): void {
        $this->assertSame(['content'], unsafehtml::get_file_areas());
    }

    public function test_get_fileserving_base(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $context = $chapter->get_context();
        $this->setUser();

        /** @var unsafehtml $content1 */
        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unsafehtml',
            'text' => '<div>Hola!</div>',
        ]);

        $result = $content1->get_fileserving_base('content');
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/$context->id/mod_mubook/content/$content1->id/",
            $result
        );
    }

    public function test_create(): void {
        global $CFG;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $context = $chapter->get_context();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->setCurrentTimeStart();
        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
        ]);
        $this->assertInstanceOf(unsafehtml::class, $content1);
        $this->assertSame('unsafehtml', $content1->type);
        $this->assertSame($chapter->id, $content1->chapterid);
        $this->assertSame('1', $content1->sortorder);
        $this->assertSame('', $content1->data1);
        $this->assertSame(null, $content1->data2);
        $this->assertSame(null, $content1->data3);
        $this->assertSame(null, $content1->auxint1);
        $this->assertSame(null, $content1->auxint2);
        $this->assertSame(null, $content1->auxint3);
        $this->assertSame('0', $content1->unsafetrusted);
        $this->assertSame('0', $content1->hidden);
        $this->assertSame(null, $content1->groupid);
        $this->assertSame(null, $content1->originjson);
        $this->assertTimeCurrent($content1->timecreated);
        $this->assertTimeCurrent($content1->timemodified);

        $content2 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test',
            'hidden' => 1,
            'sortorder' => 1,
            'unsafetrusted' => 1,
        ]);
        $this->assertSame('unsafehtml', $content2->type);
        $this->assertSame($chapter->id, $content2->chapterid);
        $this->assertSame('1', $content2->sortorder);
        $this->assertSame('test', $content2->data1);
        $this->assertSame(null, $content2->data2);
        $this->assertSame(null, $content2->data3);
        $this->assertSame(null, $content2->auxint1);
        $this->assertSame(null, $content2->auxint2);
        $this->assertSame(null, $content2->auxint3);
        $this->assertSame('1', $content2->unsafetrusted);
        $this->assertSame('1', $content2->hidden);
        $this->assertSame(null, $content2->groupid);
        $this->assertSame(null, $content2->originjson);
        $content1 = self::refetch_content($content1);
        $this->assertSame('2', $content1->sortorder);

        $content3 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'text' => 'test',
            'hidden' => 0,
            'sortorder' => 4,
            'unsafetrusted' => 0,
        ]);
        $this->assertSame('unsafehtml', $content3->type);
        $this->assertSame($chapter->id, $content3->chapterid);
        $this->assertSame('3', $content3->sortorder);
        $this->assertSame('test', $content3->data1);
        $this->assertSame(null, $content3->data2);
        $this->assertSame(null, $content3->data3);
        $this->assertSame(null, $content3->auxint1);
        $this->assertSame(null, $content3->auxint2);
        $this->assertSame(null, $content3->auxint3);
        $this->assertSame('0', $content3->unsafetrusted);
        $this->assertSame('0', $content3->hidden);
        $this->assertSame(null, $content3->groupid);
        $this->assertSame(null, $content3->originjson);
        $content1 = self::refetch_content($content1);
        $content2 = self::refetch_content($content2);
        $this->assertSame('2', $content1->sortorder);
        $this->assertSame('1', $content2->sortorder);

        $fs = get_file_storage();
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($user->id);
        $filerecord = [
            'component' => 'user',
            'filearea' => 'draft',
            'contextid' => $usercontext->id,
            'itemid' => $draftitemid,
            'filename' => 'logo.png',
            'filepath' => '/',
        ];
        $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/pix/moodlelogo.png');
        $content4 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'text' => 'editor test',
            'files' => $draftitemid,
            'hidden' => 0,
            'sortorder' => 4,
        ]);
        $this->assertSame('unsafehtml', $content4->type);
        $this->assertSame($chapter->id, $content4->chapterid);
        $this->assertSame('4', $content4->sortorder);
        $this->assertSame('editor test', $content4->data1);
        $this->assertSame(null, $content4->data2);
        $this->assertSame(null, $content4->data3);
        $this->assertSame(null, $content4->auxint1);
        $this->assertSame(null, $content4->auxint2);
        $this->assertSame(null, $content4->auxint3);
        $this->assertSame('0', $content4->unsafetrusted);
        $this->assertSame('0', $content4->hidden);
        $this->assertSame(null, $content4->groupid);
        $this->assertSame(null, $content4->originjson);

        $files = $fs->get_area_files($context->id, 'mod_mubook', 'content', $content4->id, 'id ASC', false);
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('logo.png', $file->get_filename());
    }

    public function test_update(): void {
        global $CFG;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $context = $chapter->get_context();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);
        $content2 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 2',
            'hidden' => 1,
            'sortorder' => 2,
        ]);
        $content3 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 3',
            'hidden' => 0,
            'sortorder' => 3,
        ]);
        $content4 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 4',
            'hidden' => 0,
            'sortorder' => 4,
        ]);

        $content1 = $content1->update((object)[
            'id' => $content1->id,
            'text' => 'fancy!',
            'hidden' => 1,
        ]);
        $content1 = self::refetch_content($content1);
        $this->assertSame('fancy!', $content1->data1);
        $this->assertSame('1', $content1->hidden);

        $content1 = $content1->update((object)[
            'id' => $content1->id,
            'text' => 'fancier!',
            'hidden' => 0,
            'unsafetrusted' => 1,
        ]);
        $content1 = self::refetch_content($content1);
        $this->assertSame('fancier!', $content1->data1);
        $this->assertSame('0', $content1->hidden);
        $this->assertSame('1', $content1->unsafetrusted);

        // Test moving of content.

        $content2 = $content2->update((object)[
            'id' => $content2->id,
            'sortorder' => 3,
        ]);
        $this->assertSame('3', $content2->sortorder);
        $content1 = self::refetch_content($content1);
        $this->assertSame('1', $content1->sortorder);
        $content3 = self::refetch_content($content3);
        $this->assertSame('2', $content3->sortorder);
        $content4 = self::refetch_content($content4);
        $this->assertSame('4', $content4->sortorder);

        $content2 = $content2->update((object)[
            'id' => $content2->id,
            'sortorder' => 1,
        ]);
        $this->assertSame('1', $content2->sortorder);
        $content1 = self::refetch_content($content1);
        $this->assertSame('2', $content1->sortorder);
        $content3 = self::refetch_content($content3);
        $this->assertSame('3', $content3->sortorder);
        $content4 = self::refetch_content($content4);
        $this->assertSame('4', $content4->sortorder);

        $content2 = $content2->update((object)[
            'id' => $content2->id,
            'sortorder' => 4,
        ]);
        $this->assertSame('4', $content2->sortorder);
        $content1 = self::refetch_content($content1);
        $this->assertSame('1', $content1->sortorder);
        $content3 = self::refetch_content($content3);
        $this->assertSame('2', $content3->sortorder);
        $content4 = self::refetch_content($content4);
        $this->assertSame('3', $content4->sortorder);

        // Test file attachments.

        $fs = get_file_storage();
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($user->id);
        $filerecord = [
            'component' => 'user',
            'filearea' => 'draft',
            'contextid' => $usercontext->id,
            'itemid' => $draftitemid,
            'filename' => 'logo.png',
            'filepath' => '/',
        ];
        $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/pix/moodlelogo.png');
        $content1 = $content1->update((object)[
            'id' => $content1->id,
            'text' => 'extras',
            'files' => $draftitemid,
        ]);
        $files = $fs->get_area_files($context->id, 'mod_mubook', 'content', $content1->id, 'id ASC', false);
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('logo.png', $file->get_filename());

        $fs = get_file_storage();
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($user->id);
        $filerecord = [
            'component' => 'user',
            'filearea' => 'draft',
            'contextid' => $usercontext->id,
            'itemid' => $draftitemid,
            'filename' => 'logox.png',
            'filepath' => '/',
        ];
        $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/pix/moodlelogo.png');
        $content1 = $content1->update((object)[
            'id' => $content1->id,
            'text' => 'extras',
            'files' => $draftitemid,
        ]);
        $files = $fs->get_area_files($context->id, 'mod_mubook', 'content', $content1->id, 'id ASC', false);
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('logox.png', $file->get_filename());
    }

    public function test_delete(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);
        $content2 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 2',
            'hidden' => 1,
            'sortorder' => 2,
        ]);
        $content3 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 3',
            'hidden' => 0,
            'sortorder' => 3,
        ]);
        $content4 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 4',
            'hidden' => 0,
            'sortorder' => 4,
        ]);

        $content3->delete();
        $this->assertFalse($DB->record_exists('mubook_content', ['id' => $content3->id]));
        $content1 = self::refetch_content($content1);
        $this->assertSame('1', $content1->sortorder);
        $content2 = self::refetch_content($content2);
        $this->assertSame('2', $content2->sortorder);
        $content4 = self::refetch_content($content4);
        $this->assertSame('3', $content4->sortorder);
    }

    public function test_get_record(): void {
        global $DB;

        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);

        $record = $DB->get_record('mubook_content', ['id' => $content1->id]);
        $this->assertEquals($record, $content1->get_record());
    }

    public function test_get_chapter(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);

        $this->assertEquals($chapter->get_record(), $content1->get_chapter()->get_record());
    }

    public function test_get_mubook(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        unset($mubook->cmid);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);

        $this->assertEquals($mubook, $content1->get_mubook());
    }

    public function test_get_context(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $context = $chapter->get_context();
        $this->setUser();

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);

        $this->assertEquals($context->id, $content1->get_context()->id);
    }

    public function test_can_create(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        assign_capability('mod/mubook:editcontent', CAP_ALLOW, $editorroleid, $syscontext);
        $unsaferoleid = $this->getDataGenerator()->create_role(['shortname' => 'unsafe']);
        assign_capability('mod/mubook:usexss', CAP_ALLOW, $unsaferoleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);
        role_assign($editorroleid, $user3->id, $context->id);
        role_assign($unsaferoleid, $user3->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $this->setUser($user1);
        $this->assertFalse(unsafehtml::can_create($chapter, $mubook, $context));

        $this->setUser($user2);
        $this->assertFalse(unsafehtml::can_create($chapter, $mubook, $context));

        $this->setUser($user3);
        $this->assertTrue(unsafehtml::can_create($chapter, $mubook, $context));

        $this->setAdminUser();
        $this->assertTrue(unsafehtml::can_create($chapter, $mubook, $context));
    }

    public function test_get_create_url(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        $url = unsafehtml::get_create_url($chapter, 3);
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/content_create.php?chapterid={$chapter->id}&sortorder=3&type=unsafehtml",
            $url->out(false)
        );
    }

    public function test_get_create_form_classname(): void {
        $this->assertSame('\\mod_mubook\\local\\content\\form\\unsafehtml_create', unsafehtml::get_create_form_classname());
    }

    public function test_can_update(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        $hideroleid = $this->getDataGenerator()->create_role(['shortname' => 'hiddenviwer']);
        assign_capability('mod/mubook:editcontent', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('mod/mubook:viewhiddencontent', CAP_ALLOW, $hideroleid, $syscontext);
        $unsaferoleid = $this->getDataGenerator()->create_role(['shortname' => 'unsafe']);
        assign_capability('mod/mubook:usexss', CAP_ALLOW, $unsaferoleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);
        role_assign($editorroleid, $user3->id, $context->id);
        role_assign($hideroleid, $user3->id, $context->id);
        role_assign($editorroleid, $user4->id, $context->id);
        role_assign($hideroleid, $user4->id, $context->id);
        role_assign($unsaferoleid, $user4->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);
        $content2 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 2',
            'hidden' => 1,
            'sortorder' => 2,
        ]);

        $this->setUser($user1);
        $this->assertFalse($content1->can_update());
        $this->assertFalse($content2->can_update());

        $this->setUser($user2);
        $this->assertFalse($content1->can_update());
        $this->assertFalse($content2->can_update());

        $this->setUser($user3);
        $this->assertFalse($content1->can_update());
        $this->assertFalse($content2->can_update());

        $this->setUser($user4);
        $this->assertTrue($content1->can_update());
        $this->assertTrue($content2->can_update());

        $this->setAdminUser();
        $this->assertTrue($content1->can_update());
        $this->assertTrue($content2->can_update());
    }

    public function test_get_update_url(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);

        $url = $content1->get_update_url();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/content_update.php?id={$content1->id}",
            $url->out(false)
        );
    }

    public function test_get_update_form_classname(): void {
        $this->assertSame('\\mod_mubook\\local\\content\\form\\unsafehtml_update', unsafehtml::get_update_form_classname());
    }

    public function test_can_delete(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        $hideroleid = $this->getDataGenerator()->create_role(['shortname' => 'hiddenviwer']);
        assign_capability('mod/mubook:editcontent', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('mod/mubook:viewhiddencontent', CAP_ALLOW, $hideroleid, $syscontext);
        $unsaferoleid = $this->getDataGenerator()->create_role(['shortname' => 'unsafe']);
        assign_capability('mod/mubook:usexss', CAP_ALLOW, $unsaferoleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);
        role_assign($editorroleid, $user3->id, $context->id);
        role_assign($hideroleid, $user3->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);
        $content2 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 2',
            'hidden' => 1,
            'sortorder' => 2,
        ]);

        $this->setUser($user1);
        $this->assertTrue($content1->can_delete());
        $this->assertFalse($content2->can_delete());

        $this->setUser($user2);
        $this->assertFalse($content1->can_delete());
        $this->assertFalse($content2->can_delete());

        $this->setUser($user3);
        $this->assertTrue($content1->can_delete());
        $this->assertTrue($content2->can_delete());

        $this->setAdminUser();
        $this->assertTrue($content1->can_delete());
        $this->assertTrue($content2->can_delete());
    }

    public function test_get_delete_link(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);

        $page = new \moodle_page();
        $rbase = new \renderer_base($page, "/");

        $link = $content1->get_delete_link();
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/content_delete.php?id={$content1->id}",
            $link->export_for_template($rbase)['formurl']
        );
    }

    public function test_can_view(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $this->setUser();

        $syscontext = \context_system::instance();
        $hideroleid = $this->getDataGenerator()->create_role(['shortname' => 'hiddenviwer']);
        assign_capability('mod/mubook:viewhiddencontent', CAP_ALLOW, $hideroleid, $syscontext);
        $unsaferoleid = $this->getDataGenerator()->create_role(['shortname' => 'unsafe']);
        assign_capability('mod/mubook:usexss', CAP_ALLOW, $unsaferoleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        role_assign($hideroleid, $user1->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $content1 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 1',
            'hidden' => 0,
            'sortorder' => 1,
        ]);
        $content2 = unsafehtml::create((object)[
            'chapterid' => $chapter->id,
            'data1' => 'test 2',
            'hidden' => 1,
            'sortorder' => 2,
        ]);

        $this->setUser($user1);
        $this->assertTrue($content1->can_view());
        $this->assertTrue($content2->can_view());

        $this->setUser($user2);
        $this->assertTrue($content1->can_view());
        $this->assertFalse($content2->can_view());

        $this->setAdminUser();
        $this->assertTrue($content1->can_view());
        $this->assertTrue($content2->can_view());
    }

    /**
     * Create new instance of content with current record.
     *
     * @param content $content
     * @return content
     */
    protected static function refetch_content(content $content): content {
        global $DB;

        $cman = \core\di::get(\mod_mubook\local\content_manager::class);

        $record = $DB->get_record('mubook_content', ['id' => $content->id], '*', MUST_EXIST);
        return $cman->create_instance($record, $content->get_chapter());
    }
}
