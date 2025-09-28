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

namespace tool_muprog\phpunit\event;

/**
 * User allocated event test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\event\allocation_created
 */
final class allocation_created_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_event(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ];
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setAdminUser();
        $program = $generator->create_program($data);
        $source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual']);

        $this->setAdminUser();
        $sink = $this->redirectEvents();
        \tool_muprog\local\source\manual::allocate_users($program->id, $source->id, [$user->id]);
        $events = $sink->get_events();
        $sink->close();

        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id]);

        $this->assertCount(2, $events);
        $this->assertInstanceOf('tool_muprog\event\allocation_created', $events[0]);
        $this->assertInstanceOf('core\event\calendar_event_created', $events[1]);
        $event = $events[0];
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($allocation->id, $event->objectid);
        $this->assertSame($admin->id, $event->userid);
        $this->assertSame($user->id, $event->relateduserid);
        $this->assertSame('c', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('tool_muprog_allocation', $event->objecttable);
        $this->assertSame('User allocated to program', $event::get_name());
        $description = $event->get_description();
        $programurl = new \moodle_url('/admin/tool/muprog/management/allocation.php', ['id' => $allocation->id]);
        $this->assertSame($programurl->out(false), $event->get_url()->out(false));
    }
}
