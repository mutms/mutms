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

/**
 * Certification event test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\event\period_certified
 */
final class period_certified_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_period_certified(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $program = $programgenerator->create_program(['sources' => 'mucertify', 'archived' => 0]);
        $programsource = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'mucertify']);
        $certification = $generator->create_certification([
            'sources' => 'manual',
            'programid1' => $program->id,
            'contextid' => $syscontext->id,
        ]);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user->id], []);
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $top = \tool_muprog\local\program::load_content($program->id);
        $allocation = $DB->get_record('tool_muprog_allocation', ['sourceid' => $programsource->id, 'userid' => $user->id], '*', MUST_EXIST);
        $period = $DB->get_record('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user->id]);

        $this->setAdminUser();
        $sink = $this->redirectEvents();
        \tool_muprog\local\allocation::update_item_completion((object)[
            'allocationid' => $allocation->id,
            'itemid' => $top->get_id(),
            'timecompleted' => time(),
            'evidencetimecompleted' => time(),
            'evidencedetails' => 'test',
        ]);
        $events = $sink->get_events();
        $sink->close();
        $this->assertInstanceOf(\tool_muprog\event\allocation_completed::class, $events[0]);
        $this->assertInstanceOf(\core\event\calendar_event_deleted::class, $events[1]);
        $this->assertCount(2, $events);

        $sink = $this->redirectEvents();
        \tool_mucertify\local\event_observer::allocation_completed($events[0]);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\tool_mucertify\event\period_certified::class, $event);
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($period->id, $event->objectid);
        $this->assertSame($user->id, $event->relateduserid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_PARTICIPATING, $event->edulevel);
        $this->assertSame('tool_mucertify_period', $event->objecttable);
        $this->assertSame('User was certified', $event::get_name());
        $description = $event->get_description();
        $certificationurl = new \moodle_url('/admin/tool/mucertify/management/period.php', ['id' => $period->id]);
        $this->assertSame($certificationurl->out(false), $event->get_url()->out(false));
    }
}
