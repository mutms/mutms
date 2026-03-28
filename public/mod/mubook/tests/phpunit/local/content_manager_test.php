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

use mod_mubook\local\content_manager;

/**
 * Content manager test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\local\content_manager
 */
final class content_manager_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_di(): void {
        $cman = \core\di::get(content_manager::class);
        $this->assertSame($cman, \core\di::get(content_manager::class));
    }

    public function test_get_available_classes(): void {
        $cman = \core\di::get(content_manager::class);

        $classes = $cman->get_available_classes();
        foreach ($classes as $type => $classname) {
            $this->assertSame($type, $classname::get_type());
            $this->assertSame($type, clean_param($type, PARAM_ALPHANUM));
        }

        $this->assertArrayNotHasKey('unknown', $classes);
    }

    public function test_get_types_menu(): void {
        $cman = \core\di::get(content_manager::class);

        $menu = $cman->get_types_menu(false);
        foreach ($menu as $type => $name) {
            $this->assertSame($type, clean_param($type, PARAM_ALPHANUM));
        }
        $this->assertArrayNotHasKey('unknown', $menu);
        $this->assertArrayHasKey('unsafehtml', $menu);

        $menu = $cman->get_types_menu(true);
        $this->assertArrayNotHasKey('unsafehtml', $menu);
    }

    public function test_get_class(): void {
        $cman = \core\di::get(content_manager::class);

        $this->assertSame(\mod_mubook\local\content\html::class, $cman->get_class('html'));
        $this->assertSame(null, $cman->get_class('xyzunknownxyz'));
    }

    public function test_create_instance(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $this->setUser();

        $cman = \core\di::get(content_manager::class);

        $content1 = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'html',
            'text' => '<p>Hola!</p>',
        ]);

        $content = $cman->create_instance($content1->get_record(), $chapter);
        $this->assertInstanceOf(\mod_mubook\local\content\html::class, $content);
        $this->assertSame($content1->id, $content->id);
    }

    public function test_can_create_content(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $context = $chapter->get_context();
        $this->setUser();

        $cman = \core\di::get(content_manager::class);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role(['shortname' => 'editor']);
        assign_capability('mod/mubook:editcontent', CAP_ALLOW, $editorroleid, $syscontext);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        role_assign($editorroleid, $user1->id, $context->id);

        $this->setUser($user1);
        $this->assertTrue($cman->can_create_content(null, $mubook, $context));
        $this->assertTrue($cman->can_create_content($chapter, $mubook, $context));

        $this->setUser($user2);
        $this->assertFalse($cman->can_create_content(null, $mubook, $context));
        $this->assertFalse($cman->can_create_content($chapter, $mubook, $context));
    }

    public function test_get_create_content_link(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $context = $chapter->get_context();
        $this->setUser();

        $cman = \core\di::get(content_manager::class);

        $page = new \moodle_page();
        $rbase = new \renderer_base($page, "/");

        $link = $cman->get_create_content_link($chapter, 3);
        $this->assertSame(
            "https://www.example.com/moodle/mod/mubook/management/content_create_select.php?chapterid={$chapter->id}&sortorder=3",
            $link->export_for_template($rbase)['formurl']
        );
    }
}
