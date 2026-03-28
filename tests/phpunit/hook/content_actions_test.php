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

namespace mod_mubook\phpunit\hook;

use mod_mubook\hook\content_actions;

/**
 * Chapter content actions hook test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\hook\content_actions;
 */
final class content_actions_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_hook(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $syscontext = \context_system::instance();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'editingteacher');

        $called = null;
        $callback = function ($hook) use (&$called): void {
            $called = true;
        };
        $manager = \core\di::get(\core\hook\manager::class);
        $manager->phpunit_redirect_hook(content_actions::class, $callback);

        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $content = $generator->create_chapter_content([
            'chapterid' => $chapter->id,
            'type' => 'html',
            'text' => '<p>Hola!</p>',
        ]);

        $toc = new \mod_mubook\local\toc($mubook);

        $viewpageurl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $chapter->id]);

        $this->setUser($user1);
        $called = false;
        $actions = new content_actions($content, $chapter, $toc, $viewpageurl, false);
        $this->assertFalse($called);
        $this->assertFalse($actions->dropdown->has_items());

        $called = false;
        $actions = new content_actions($content, $chapter, $toc, $viewpageurl, true);
        $this->assertTrue($called);
        $this->assertFalse($actions->dropdown->has_items());

        $this->setUser($user2);
        $called = false;
        $actions = new content_actions($content, $chapter, $toc, $viewpageurl, false);
        $this->assertFalse($called);
        $this->assertFalse($actions->dropdown->has_items());

        $called = false;
        $actions = new content_actions($content, $chapter, $toc, $viewpageurl, true);
        $this->assertTrue($called);
        $this->assertTrue($actions->dropdown->has_items());
    }
}
