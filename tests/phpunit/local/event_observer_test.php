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

namespace tool_muprog\phpunit\local;

use tool_muprog\local\source\manual;

/**
 * Program helper test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
final class event_observer_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @return void
     *
     * @covers \tool_muprog\local\event_observer::course_updated()
     */
    public function test_course_updated(): void {
        global $CFG;
        require_once("$CFG->dirroot/course/lib.php");

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program([]);
        $program2 = $generator->create_program([]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $top = \tool_muprog\local\program::load_content($program1->id);
        $top->append_course($top, $course1->id);
        $top->append_course($top, $course2->id);
        $item1 = $top->get_children()[0];
        $this->assertSame($course1->fullname, $item1->get_fullname());

        $course1->fullname = 'Fancy course';
        \update_course($course1);

        $top = \tool_muprog\local\program::load_content($program1->id);
        $item1 = $top->get_children()[0];
        $this->assertSame($course1->fullname, $item1->get_fullname());
    }

    /**
     * @return void
     *
     * @covers \tool_muprog\local\event_observer::course_deleted()
     */
    public function test_course_deleted(): void {
        global $CFG;
        require_once("$CFG->dirroot/course/lib.php");

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program([]);
        $program2 = $generator->create_program([]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $top = \tool_muprog\local\program::load_content($program1->id);
        $top->append_course($top, $course1->id);
        $top->append_course($top, $course2->id);

        // There is not much we can do when course is deleted,
        // so just make sure theere are no errors.
        \delete_course($course1->id, false);
        $top = \tool_muprog\local\program::load_content($program1->id);
        $item1 = $top->get_children()[0];
        $this->assertSame($course1->fullname, $item1->get_fullname());
    }

    /**
     * @return void
     *
     * @covers \tool_muprog\local\event_observer::course_category_deleted()
     */
    public function test_course_category_deleted(): void {
        global $CFG, $DB;
        require_once("$CFG->dirroot/course/lib.php");

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category2record = $category2->get_db_record();
        $catcontext2 = \context_coursecat::instance($category2->id);
        $this->assertSame($category1->id, $category2->parent);

        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $generator->create_program(['contextid' => $catcontext2->id]);

        $this->assertSame((string)$catcontext1->id, $program1->contextid);
        $this->assertSame((string)$catcontext2->id, $program2->contextid);

        // Hack the category delete the bad way to rpevent execution of pre-delete hook,
        // This might explode if anything else is not expecting this madness.
        $DB->delete_records('course_categories', ['id' => $category2->id]);

        $event = \core\event\course_category_deleted::create([
            'objectid' => $category2->id,
            'context' => $catcontext2,
            'other' => ['name' => $category2->name],
        ]);
        $event->add_record_snapshot('course_categories', $category2record);
        $event->set_coursecat($category2);
        $event->trigger();

        $program2 = $DB->get_record('tool_muprog_program', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $program2->contextid);
        $program1 = $DB->get_record('tool_muprog_program', ['id' => $program1->id], '*', MUST_EXIST);
        $this->assertSame((string)$catcontext1->id, $program1->contextid);
    }

    /**
     * @return void
     *
     * @covers \tool_muprog\local\event_observer::user_deleted()
     */
    public function test_user_deleted(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $context1 = \context_course::instance($course1->id);
        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $top1 = \tool_muprog\local\program::load_content($program1->id);
        $item1 = $top1->append_course($top1, $course1->id);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);

        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $data = (object)[
            'allocationid' => $allocation1->id,
            'timecompleted' => time(),
            'itemid' => $item1->get_id(),
            'evidencetimecompleted' => null,
        ];
        \tool_muprog\local\allocation::update_item_completion($data);

        $data = (object)[
            'allocationid' => $allocation2->id,
            'timecompleted' => time(),
            'itemid' => $item1->get_id(),
            'evidencetimecompleted' => null,
        ];
        \tool_muprog\local\allocation::update_item_completion($data);

        \delete_user($user1);

        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_completion', ['allocationid' => $allocation1->id, 'itemid' => $item1->get_id()]));

        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_completion', ['allocationid' => $allocation2->id, 'itemid' => $item1->get_id()]));
    }

    /**
     * @return void
     *
     * @covers \tool_muprog\local\event_observer::course_completed()
     */
    public function test_course_completed(): void {
        global $DB, $CFG;
        require_once("$CFG->libdir/completionlib.php");
        $CFG->enablecompletion = true;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $context1 = \context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $context2 = \context_course::instance($course2->id);
        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $top1 = \tool_muprog\local\program::load_content($program1->id);
        $top1->update_set($top1, ['fullname' => '', 'sequencetype' => \tool_muprog\local\content\set::SEQUENCE_TYPE_ALLINORDER]);
        $item1 = $top1->append_course($top1, $course1->id);
        $item2 = $top1->append_course($top1, $course2->id);
        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);

        $this->assertTrue(is_enrolled($context1, $user1, '', false));
        $this->assertTrue(is_enrolled($context1, $user1, '', true));
        $this->assertTrue(is_enrolled($context2, $user1, '', false));
        $this->assertFalse(is_enrolled($context2, $user1, '', true));

        $this->setUser($user1);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertNull($allocation1->timecompleted);
        $completion1 = $DB->get_record('tool_muprog_completion', ['itemid' => $item1->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertFalse($completion1);
        $completion2 = $DB->get_record('tool_muprog_completion', ['itemid' => $item2->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertFalse($completion2);

        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $user1->id]);
        $this->setCurrentTimeStart();
        $ccompletion->mark_complete();
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertNull($allocation1->timecompleted);
        $completion1 = $DB->get_record('tool_muprog_completion', ['itemid' => $item1->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertTimeCurrent($completion1->timecompleted);
        $completion2 = $DB->get_record('tool_muprog_completion', ['itemid' => $item2->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertFalse($completion2);

        $this->assertTrue(is_enrolled($context1, $user1, '', false));
        $this->assertTrue(is_enrolled($context1, $user1, '', true));
        $this->assertTrue(is_enrolled($context2, $user1, '', false));
        $this->assertTrue(is_enrolled($context2, $user1, '', true));

        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $user1->id]);
        $this->setCurrentTimeStart();
        $ccompletion->mark_complete();
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertTimeCurrent($allocation1->timecompleted);
        $completion2 = $DB->get_record('tool_muprog_completion', ['itemid' => $item2->get_id(), 'allocationid' => $allocation1->id]);
        $this->assertTimeCurrent($completion2->timecompleted);
    }

    /**
     * @return void
     *
     * @covers \tool_muprog\local\event_observer::group_deleted()
     */
    public function test_group_deleted(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course([]);
        $course2 = $this->getDataGenerator()->create_course([]);

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $top1 = \tool_muprog\local\program::load_content($program1->id);
        $item1x1 = $top1->append_course($top1, $course1->id);
        $item1x2 = $top1->append_course($top1, $course2->id);

        $program1 = \tool_muprog\local\program::update_general((object)[
            'id' => $program1->id,
            'creategroups' => 1,
        ]);
        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $groups = $DB->get_records('groups', [], 'id ASC');
        $this->assertCount(2, $groups);
        $groups = array_values($groups);
        $this->assertSame($program1->fullname, $groups[0]->name);
        $this->assertSame($course1->id, $groups[0]->courseid);
        $this->assertSame($program1->fullname, $groups[1]->name);
        $this->assertSame($course2->id, $groups[1]->courseid);

        \groups_delete_group($groups[0]);
        \tool_muprog\local\allocation::fix_enrol_instances($program1->id, null);
        $newgroups = $DB->get_records('groups', [], 'id ASC');
        $this->assertCount(2, $newgroups);
        $newgroups = array_values($newgroups);
        $this->assertSame($program1->fullname, $newgroups[1]->name);
        $this->assertSame($course1->id, $newgroups[1]->courseid);
        $this->assertSame($program1->fullname, $newgroups[0]->name);
        $this->assertSame($course2->id, $newgroups[0]->courseid);
        $this->assertEquals($groups[1]->id, $newgroups[0]->id);
        $this->assertNotEquals($groups[0]->id, $newgroups[1]->id);
    }
}
