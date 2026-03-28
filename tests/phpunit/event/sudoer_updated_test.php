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

namespace tool_musudo\phpunit\event;

use tool_musudo\event\sudoer_updated;
use tool_musudo\local\sudoer;

/**
 * Sudoer updated event test.
 *
 * @group      MuTMS
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\event\sudoer_updated
 */
final class sudoer_updated_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_event(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $admin = get_admin();
        $this->setAdminUser();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $sink = $this->redirectEvents();
        $sudoer1 = sudoer::update((object)[
            'id' => $sudoer1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);

        $this->assertInstanceOf(sudoer_updated::class, $event);
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($sudoer1->id, $event->objectid);
        $this->assertSame($sudoer1->userid, $event->relateduserid);
        $this->assertSame($admin->id, $event->userid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('tool_musudo_sudoer', $event->objecttable);
        $this->assertSame('Privileged user updated', $event::get_name());
        $description = $event->get_description();
        $url = new \moodle_url('/admin/tool/musudo/index.php', []);
        $this->assertSame($url->out(false), $event->get_url()->out(false));
    }
}
