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

namespace tool_muprog\phpunit;

use tool_muprog\local\calendar;
use tool_muprog\local\source\manual;

/**
 * Program lib.php tests.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers \tool_muprog_pre_course_category_delete()
     */
    public function test_tool_muprog_pre_course_category_delete(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $this->assertSame($category1->id, $category2->parent);

        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $generator->create_program(['contextid' => $catcontext2->id]);

        $this->assertSame((string)$catcontext1->id, $program1->contextid);
        $this->assertSame((string)$catcontext2->id, $program2->contextid);

        $category2->delete_full(false);
        $program2 = $DB->get_record('tool_muprog_program', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$catcontext1->id, $program2->contextid);
    }

    /**
     * @covers \tool_muprog_core_calendar_provide_event_action()
     */
    public function test_tool_muprog_core_calendar_provide_event_action(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/admin/tool/muprog/lib.php');

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);

        $event = $DB->get_record('event', ['component' => 'tool_muprog', 'instance' => $allocation1->id], '*', MUST_EXIST);
        $this->assertSame(calendar::EVENTTYPE_START, $event->eventtype);

        $this->setUser($user1);
        $calendarevent = \calendar_event::load($event);
        $calendarevent->instance = '0'; // Replicate core bug where instance is missing.
        $factory = new \core_calendar\action_factory();

        $result = \tool_muprog_core_calendar_provide_event_action($calendarevent, $factory);
        $this->assertInstanceOf('core_calendar\local\event\value_objects\action', $result);
        $this->assertSame('View', $result->get_name());
        $this->assertSame("$CFG->wwwroot/admin/tool/muprog/my/program.php?id=" . $program1->id, $result->get_url()->out());
        $this->assertSame(1, $result->get_item_count());
    }

    /**
     * @covers \tool_muprog_get_tagged_programs()
     */
    public function test_tool_muprog_get_tagged_programs(): void {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/muprog/lib.php');

        $syscontext = \context_system::instance();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $program2 = $generator->create_program(['fullname' => 'pokus', 'sources' => ['manual' => []]]);

        \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $program1->id, $syscontext, ['foo', 'bar']);
        \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $program2->id, $syscontext, ['bar']);
        $tags1 = \core_tag_tag::get_item_tags('tool_muprog', 'tool_muprog_program', $program1->id);
        $this->assertCount(2, $tags1);
        $tags2 = \core_tag_tag::get_item_tags('tool_muprog', 'tool_muprog_program', $program2->id);
        $this->assertCount(1, $tags2);
        $bar = reset($tags2);

        $result = tool_muprog_get_tagged_programs($bar);
        $this->assertInstanceOf(\core_tag\output\tagindex::class, $result);
    }
}
