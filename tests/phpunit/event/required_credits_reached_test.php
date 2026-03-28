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

namespace tool_mutrain\phpunit\event;

use tool_mutrain\local\framework;

/**
 * Required credits reached event test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\event\required_credits_reached
 */
final class required_credits_reached_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_event(): void {
        global $DB;

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );

        $course1 = $this->getDataGenerator()->create_course(
            ['customfield_field1' => 20, 'enablecompletion' => 1]
        );
        $course2 = $this->getDataGenerator()->create_course(
            ['customfield_field1' => 40, 'enablecompletion' => 1]
        );

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);

        $framework = $generator->create_framework([
            'fields' => [$field1->get('id')],
            'requiredcredits' => 35,
        ]);

        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $ccompletion->mark_complete();
        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $user2->id]);
        $ccompletion->mark_complete();

        $syscontext = \context_system::instance();

        $DB->delete_records('tool_mutrain_credit', []);

        $this->setUser($user0);

        $sink = $this->redirectEvents();
        framework::sync_credits($user1->id, $framework->id);
        $this->assertSame([], $sink->get_events());

        framework::sync_credits($user2->id, $framework->id);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = $events[0];
        $credit = $DB->get_record('tool_mutrain_credit', ['userid' => $user2->id, 'frameworkid' => $framework->id]);

        $this->assertInstanceOf(\tool_mutrain\event\required_credits_reached::class, $event);

        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($credit->id, $event->objectid);
        $this->assertSame($user0->id, $event->userid);
        $this->assertSame($user2->id, $event->relateduserid);
        $this->assertSame('c', $event->crud);
        $this->assertSame($event::LEVEL_PARTICIPATING, $event->edulevel);
        $this->assertSame('tool_mutrain_credit', $event->objecttable);
        $this->assertSame('User reached required credits', $event::get_name());
        $description = $event->get_description();
        $url = new \core\url('/admin/tool/mutrain/management/framework.php', ['id' => $framework->id]);
        $this->assertSame($url->out(false), $event->get_url()->out(false));
    }
}
