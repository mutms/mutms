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
 * Certification period deleted test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\event\period_deleted
 */
final class period_deleted_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_period_deleted(): void {
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
        $period = $DB->get_record('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user->id]);

        $sink = $this->redirectEvents();
        \tool_mucertify\local\period::delete($period->id);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(\tool_mucertify\event\period_deleted::class, $events[0]);
        $this->assertInstanceOf(\core\event\calendar_event_deleted::class, $events[1]);

        $event = $events[0];
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($period->id, $event->objectid);
        $this->assertSame($user->id, $event->relateduserid);
        $this->assertSame('d', $event->crud);
        $this->assertSame($event::LEVEL_PARTICIPATING, $event->edulevel);
        $this->assertSame('tool_mucertify_period', $event->objecttable);
        $this->assertSame('Certification period deleted', $event::get_name());
        $description = $event->get_description();
        $certificationurl = new \moodle_url('/admin/tool/mucertify/management/assignment.php', ['id' => $assignment->id]);
        $this->assertSame($certificationurl->out(false), $event->get_url()->out(false));
    }
}
