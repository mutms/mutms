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

namespace tool_mucertify\phpunit\event;

use tool_mucertify\local\certification;

/**
 * Certification archived event test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\event\certification_archived
 */
final class certification_archived_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_updates(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some certification',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $admin = get_admin();
        $this->setAdminUser();
        $certification = certification::create($data);

        $sink = $this->redirectEvents();
        $certification = certification::archive($certification->id);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\tool_mucertify\event\certification_archived::class, $event);
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($certification->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('tool_mucertify_certification', $event->objecttable);
        $this->assertSame('Certification archived', $event::get_name());
        $description = $event->get_description();
        $certificationurl = new \moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
        $this->assertSame($certificationurl->out(false), $event->get_url()->out(false));
    }
}
