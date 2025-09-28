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
 * Certification deleted event test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\event\certification_deleted
 */
final class certification_deleted_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some certification',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $admin = get_admin();
        $certification = certification::create($data);

        $this->setAdminUser();
        $sink = $this->redirectEvents();
        certification::delete($certification->id);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\tool_mucertify\event\certification_deleted::class, $event);
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($certification->id, $event->objectid);
        $this->assertSame('d', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('tool_mucertify_certification', $event->objecttable);
        $this->assertSame('Certification deleted', $event::get_name());
        $description = $event->get_description();
        $certificationurl = new \moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => \SYSCONTEXTID]);
        $this->assertSame($certificationurl->out(false), $event->get_url()->out(false));
    }
}
