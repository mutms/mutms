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

namespace mod_mubook\phpunit\event;

use mod_mubook\event\course_module_instance_list_viewed;

/**
 * All chapters viewed event test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\event\course_module_instance_list_viewed;
 */
final class course_module_instance_list_viewed_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_event(): void {
        /** @var \mod_mubook_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mubook');

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mubook = $this->getDataGenerator()->create_module('mubook', ['course' => $course->id]);
        $chapter = $generator->create_chapter(['mubookid' => $mubook->id]);
        $context = $chapter->get_context();
        $coursecontext = \context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $chapter = $generator->create_chapter([
            'mubookid' => $mubook->id,
        ]);
        $sink = $this->redirectEvents();
        course_module_instance_list_viewed::create_from_course($course)->trigger();
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(course_module_instance_list_viewed::class, $events[0]);

        $event = $events[0];
        $this->assertEquals($coursecontext->id, $event->contextid);
        $this->assertSame(null, $event->objectid);
        $this->assertSame($course->id, $event->courseid);
        $this->assertSame('r', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(null, $event->objecttable);
        $this->assertSame('Course module instance list viewed', $event::get_name());
        $description = $event->get_description();
        $tenanturl = new \core\url('/mod/mubook/index.php', ['id' => $course->id]);
        $this->assertSame($tenanturl->out(false), $event->get_url()->out(false));
    }
}
