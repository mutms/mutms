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
use mod_mubook\local\content\unknown;

/**
 * Unknown content test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.unknown GNU GPL v3 or later
 *
 * @covers \mod_mubook\local\content\unknown
 */
final class unknown_test extends \advanced_testcase {
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

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);

        $record = $DB->get_record('mubook_content', ['id' => $content1->id], '*', MUST_EXIST);
        $content = new unknown($record, $chapter, $mubook, $chapter->get_context());
        $this->assertEquals($record, $content->get_record());
    }

    public function test_get_type(): void {
        $this->assertSame('unknown', unknown::get_type());
    }

    public function test_get_name(): void {
        $this->assertSame('Unknown content type', unknown::get_name());
    }

    public function test_get_identification(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        /** @var unknown $content1 */
        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);

        $this->assertSame('1 - Unknown content type (xyzunknowncyz)', $content1->get_identification());
    }

    public function test_is_unsafe(): void {
        $this->assertFalse(unknown::is_unsafe());
    }

    public function test_get_file_areas(): void {
        $this->assertSame([], unknown::get_file_areas());
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

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);
        $content2 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);
        $content3 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);
        $content4 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
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

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
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

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
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

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
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

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
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
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $this->setUser($user1);
        $this->assertFalse(unknown::can_create($chapter, $mubook, $context));

        $this->setUser($user2);
        $this->assertFalse(unknown::can_create($chapter, $mubook, $context));

        $this->setAdminUser();
        $this->assertFalse(unknown::can_create($chapter, $mubook, $context));
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
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);
        role_assign($editorroleid, $user3->id, $context->id);
        role_assign($hideroleid, $user3->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);
        $content2 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
            'hidden' => 1,
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

        $this->setAdminUser();
        $this->assertFalse($content1->can_update());
        $this->assertFalse($content2->can_update());
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
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);
        role_assign($editorroleid, $user3->id, $context->id);
        role_assign($hideroleid, $user3->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);
        $content2 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
            'hidden' => 1,
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

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
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
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        assign_capability('mod/mubook:editcontent', CAP_ALLOW, $editorroleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        role_assign($hideroleid, $user1->id, $context->id);
        role_assign($hideroleid, $user3->id, $context->id);
        role_assign($editorroleid, $user3->id, $context->id);
        role_assign($editorroleid, $user2->id, $context->id);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
        ]);
        $content2 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'unknown',
            'hidden' => 1,
        ]);

        $this->setUser($user1);
        $this->assertFalse($content1->can_view());
        $this->assertFalse($content2->can_view());

        $this->setUser($user2);
        $this->assertTrue($content1->can_view());
        $this->assertFalse($content2->can_view());

        $this->setUser($user3);
        $this->assertTrue($content1->can_view());
        $this->assertTrue($content2->can_view());

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
