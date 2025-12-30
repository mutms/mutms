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

namespace block_muprogmyoverview\phpunit\event;

use block_muprogmyoverview\event\my_programs_viewed;

/**
 * My programs page viewed event test.
 *
 * @group      MuTMS
 * @package    block_muprogmyoverview
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \block_muprogmyoverview\event\my_programs_viewed
 */
final class my_programs_viewed_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_event_trigget(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $syscontext = \context_system::instance();
        $event = my_programs_viewed::create(['context' => $syscontext]);
        $event->trigger();

        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame(null, $event->objectid);
        $this->assertSame('r', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(null, $event->objecttable);
        $this->assertSame('My programs page viewed', $event::get_name());
        $description = $event->get_description();
        $this->assertSame(null, $event->get_url());
    }
}
