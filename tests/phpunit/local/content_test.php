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
// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

namespace tool_muprog\phpunit\local;

use tool_muprog\local\content\course;
use tool_muprog\local\content\item;
use tool_muprog\local\content\set;
use tool_muprog\local\content\top;
use tool_muprog\local\content\training;

/**
 * Program content test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\content\top
 * @covers \tool_muprog\local\content\course
 * @covers \tool_muprog\local\content\set
 * @covers \tool_muprog\local\content\item
 */
final class content_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_load(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $top = top::load($program1->id);
        $this->assertInstanceOf(top::class, $top);
        $this->assertSame((int)$program1->id, $top->get_programid());
        $this->assertSame($program1->fullname, $top->get_fullname());
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertSame([], $top->get_children());
        $this->assertSame([], $top->get_orphaned_sets());
        $this->assertSame([], $top->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $top->get_sequencetype());
        $this->assertSame('All in any order', $top->get_sequencetype_info());
        $this->assertSame(1, $top->get_minprerequisites());
        $this->assertSame(1, $top->get_points());
        $this->assertSame(null, $top->get_minpoints());
        $this->assertSame(0, $top->get_completiondelay());
    }

    public function test_append_items(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top2 = top::load($program2->id);
        $top2->append_course($top2, $course1->id);

        $top = top::load($program1->id);
        $top->append_course($top, $course1->id);
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(1, $top->get_children());
        $this->assertSame([], $top->get_orphaned_sets());
        $this->assertSame([], $top->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $top->get_sequencetype());
        $this->assertSame('All in any order', $top->get_sequencetype_info());
        $this->assertSame(1, $top->get_minprerequisites());
        $this->assertSame(1, $top->get_points());
        $this->assertSame(null, $top->get_minpoints());
        $this->assertSame(0, $top->get_completiondelay());
        /** @var course $courseitem1 */
        $courseitem1 = $top->get_children()[0];
        $this->assertInstanceOf(course::class, $courseitem1);
        $this->assertSame((int)$program1->id, $courseitem1->get_programid());
        $this->assertSame($course1->fullname, $courseitem1->get_fullname());
        $this->assertSame(false, $courseitem1->is_problem_detected());
        $this->assertSame([], $courseitem1->get_children());
        $this->assertSame((int)$course1->id, $courseitem1->get_courseid());
        $this->assertSame(null, $courseitem1->get_previous());
        $this->assertSame(1, $courseitem1->get_points());
        $this->assertSame(0, $courseitem1->get_completiondelay());

        $top = top::load($program1->id);
        $this->assertSame(false, $top->is_problem_detected());

        $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER, 'points' => 3, 'completiondelay' => DAYSECS * 3]);
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(2, $top->get_children());
        $this->assertSame([], $top->get_orphaned_sets());
        $this->assertSame([], $top->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $top->get_sequencetype());
        $this->assertSame(2, $top->get_minprerequisites());
        /** @var set $setitem2 */
        $setitem2 = $top->get_children()[1];
        $this->assertInstanceOf(set::class, $setitem2);
        $this->assertSame((int)$program1->id, $setitem2->get_programid());
        $this->assertSame('Nice set', $setitem2->get_fullname());
        $this->assertSame(false, $setitem2->is_problem_detected());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINORDER, $setitem2->get_sequencetype());
        $this->assertSame('All in order', $setitem2->get_sequencetype_info());
        $this->assertSame(1, $setitem2->get_minprerequisites());
        $this->assertSame([], $setitem2->get_children());
        $this->assertSame(3, $setitem2->get_points());
        $this->assertSame(null, $setitem2->get_minpoints());
        $this->assertSame(DAYSECS * 3, $setitem2->get_completiondelay());

        $top->append_course($setitem2, $course2->id, ['points' => 7, 'completiondelay' => 0]);
        $top->append_course($setitem2, $course3->id, ['points' => 0, 'completiondelay' => HOURSECS * 7]);
        $this->assertCount(2, $setitem2->get_children());
        $this->assertSame(2, $setitem2->get_minprerequisites());
        /** @var course $courseitem2 */
        $courseitem2 = $setitem2->get_children()[0];
        $this->assertSame(null, $courseitem2->get_previous());
        $this->assertSame(7, $courseitem2->get_points());
        $this->assertSame(0, $courseitem2->get_completiondelay());
        /** @var course $courseitem3 */
        $courseitem3 = $setitem2->get_children()[1];
        $this->assertSame($courseitem2, $courseitem3->get_previous());
        $this->assertSame(0, $courseitem3->get_points());
        $this->assertSame(HOURSECS * 7, $courseitem3->get_completiondelay());

        $top = top::load($program1->id);
        $this->assertSame(false, $top->is_problem_detected());

        $top->append_set($top, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2, 'points' => 8]);
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(3, $top->get_children());
        $this->assertSame([], $top->get_orphaned_sets());
        $this->assertSame([], $top->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $top->get_sequencetype());
        $this->assertSame(3, $top->get_minprerequisites());
        $this->assertSame(1, $top->get_points());
        $this->assertSame(null, $top->get_minpoints());
        /** @var set $setitem4 */
        $setitem4 = $top->get_children()[2];
        $this->assertInstanceOf(set::class, $setitem4);
        $this->assertSame((int)$program1->id, $setitem4->get_programid());
        $this->assertSame('Other set', $setitem4->get_fullname());
        $this->assertSame(false, $setitem4->is_problem_detected());
        $this->assertSame(set::SEQUENCE_TYPE_ATLEAST, $setitem4->get_sequencetype());
        $this->assertSame('At least 2', $setitem4->get_sequencetype_info());
        $this->assertSame(2, $setitem4->get_minprerequisites());
        $this->assertSame(8, $setitem4->get_points());
        $this->assertSame(null, $setitem4->get_minpoints());
        $this->assertSame([], $setitem4->get_children());

        $top->append_course($setitem4, $course4->id);
        $top->append_course($setitem4, $course5->id);
        $this->assertCount(2, $setitem4->get_children());
        $this->assertSame(2, $setitem4->get_minprerequisites());
        /** @var course $courseitem2 */
        $courseitem2 = $setitem4->get_children()[0];
        $this->assertSame(null, $courseitem2->get_previous());
        /** @var course $courseitem3 */
        $courseitem3 = $setitem4->get_children()[1];
        $this->assertSame(null, $courseitem3->get_previous());

        $top = top::load($program1->id);
        $this->assertSame(false, $top->is_problem_detected());

        $top2 = top::load($program2->id);
        $this->assertSame(false, $top2->is_problem_detected());
        $this->assertCount(1, $top2->get_children());
        $this->assertSame([], $top2->get_orphaned_sets());
        $this->assertSame([], $top2->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $top2->get_sequencetype());
        $this->assertSame('All in any order', $top2->get_sequencetype_info());
        $this->assertSame(1, $top2->get_minprerequisites());
        /** @var course $courseitem1 */
        $courseitem1 = $top2->get_children()[0];
        $this->assertInstanceOf(course::class, $courseitem1);
        $this->assertSame((int)$program2->id, $courseitem1->get_programid());
        $this->assertSame($course1->fullname, $courseitem1->get_fullname());
        $this->assertSame(false, $courseitem1->is_problem_detected());
        $this->assertSame([], $courseitem1->get_children());
        $this->assertSame((int)$course1->id, $courseitem1->get_courseid());
        $this->assertSame(null, $courseitem1->get_previous());

        $top->append_set($top, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 9, 'points' => 3]);
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(4, $top->get_children());
        $this->assertSame([], $top->get_orphaned_sets());
        $this->assertSame([], $top->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $top->get_sequencetype());
        $this->assertSame(4, $top->get_minprerequisites());
        $this->assertSame(1, $top->get_points());
        $this->assertSame(null, $top->get_minpoints());
        /** @var set $setitem4 */
        $setitem4 = $top->get_children()[3];
        $this->assertInstanceOf(set::class, $setitem4);
        $this->assertSame((int)$program1->id, $setitem4->get_programid());
        $this->assertSame('Other set', $setitem4->get_fullname());
        $this->assertSame(false, $setitem4->is_problem_detected());
        $this->assertSame(set::SEQUENCE_TYPE_MINPOINTS, $setitem4->get_sequencetype());
        $this->assertSame('Minimum 9 points', $setitem4->get_sequencetype_info());
        $this->assertSame(null, $setitem4->get_minprerequisites());
        $this->assertSame(3, $setitem4->get_points());
        $this->assertSame(9, $setitem4->get_minpoints());
        $this->assertSame([], $setitem4->get_children());
    }

    public function test_append_training(): void {
        if (!\tool_muprog\local\util::is_mutrain_available()) {
            $this->markTestSkipped('mutrain not available');
        }

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_mutrain_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field3']
        );
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']
        );

        $data = (object)[
            'name' => 'Some framework',
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Other framework',
            'fields' => [$field2->get('id')],
        ];
        $framework2 = $traininggenerator->create_framework($data);

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 2]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 4, 'customfield_field2' => 23]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field2' => 11]);
        $course5 = $this->getDataGenerator()->create_course();

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $top2 = top::load($program2->id);
        $top2->append_training($top2, $framework1->id);
        $top2->append_training($top2, $framework2->id);

        /** @var training $trainingitem1 */
        $trainingitem1 = $top2->get_children()[0];
        $this->assertInstanceOf(training::class, $trainingitem1);
        $this->assertSame((int)$program2->id, $trainingitem1->get_programid());
        $this->assertSame($framework1->name, $trainingitem1->get_fullname());
        $this->assertSame(false, $trainingitem1->is_problem_detected());
        $this->assertSame([], $trainingitem1->get_children());
        $this->assertSame((int)$framework1->id, $trainingitem1->get_trainingid());
        $this->assertSame(null, $trainingitem1->get_previous());
    }

    public function test_update_set(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $top->append_course($top, $course1->id);
        $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $top->append_set($top, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        /** @var set $setitem2 */
        $setitem2 = $top->get_children()[1];
        $top->append_course($setitem2, $course2->id);
        $top->append_course($setitem2, $course3->id);
        /** @var set $setitem4 */
        $setitem4 = $top->get_children()[2];
        $top->append_course($setitem4, $course4->id);
        $top->append_course($setitem4, $course5->id);

        $top = top::load($program1->id);
        $this->assertSame(false, $top->is_problem_detected());

        $top->update_set($top, ['fullname' => 'ignored', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER, 'minprerequisites' => 10]);
        $this->assertFalse(top::load($program1->id)->is_problem_detected());
        $this->assertSame((int)$program1->id, $top->get_programid());
        $this->assertSame($program1->fullname, $top->get_fullname());
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(3, $top->get_children());
        $this->assertSame([], $top->get_orphaned_sets());
        $this->assertSame([], $top->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINORDER, $top->get_sequencetype());
        $this->assertSame('All in order', $top->get_sequencetype_info());
        $this->assertSame(3, $top->get_minprerequisites());
        /** @var set $setitem2 */
        $setitem2 = $top->get_children()[1];
        $this->assertInstanceOf(set::class, $setitem2);
        $this->assertSame(set::SEQUENCE_TYPE_ALLINORDER, $setitem2->get_sequencetype());
        $this->assertSame(2, $setitem2->get_minprerequisites());
        /** @var course $courseitem1 */
        $courseitem1 = $top->get_children()[0];
        $this->assertSame(null, $courseitem1->get_previous());
        /** @var course $courseitem2 */
        $courseitem2 = $setitem2->get_children()[0];
        $this->assertSame($courseitem1, $courseitem2->get_previous());
        /** @var course $courseitem3 */
        $courseitem3 = $setitem2->get_children()[1];
        $this->assertSame($courseitem2, $courseitem3->get_previous());

        $top->update_set($top, ['fullname' => 'ignored', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $this->assertFalse(top::load($program1->id)->is_problem_detected());
        $this->assertSame((int)$program1->id, $top->get_programid());
        $this->assertSame($program1->fullname, $top->get_fullname());
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(3, $top->get_children());
        $this->assertSame([], $top->get_orphaned_sets());
        $this->assertSame([], $top->get_orphaned_courses());
        $this->assertSame(set::SEQUENCE_TYPE_ATLEAST, $top->get_sequencetype());
        $this->assertSame('At least 2', $top->get_sequencetype_info());
        $this->assertSame(2, $top->get_minprerequisites());
        $this->assertSame(1, $top->get_points());
        $this->assertSame(null, $top->get_minpoints());
        $this->assertSame(null, $courseitem1->get_previous());
        $this->assertSame(null, $courseitem2->get_previous());
        $this->assertSame($courseitem2, $courseitem3->get_previous());

        $top->update_set($setitem2, ['fullname' => 'Very nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINANYORDER, 'minprerequisites' => 10]);
        $this->assertFalse(top::load($program1->id)->is_problem_detected());
        $this->assertSame((int)$program1->id, $setitem2->get_programid());
        $this->assertSame('Very nice set', $setitem2->get_fullname());
        $this->assertSame(false, $setitem2->is_problem_detected());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $setitem2->get_sequencetype());
        $this->assertSame('All in any order', $setitem2->get_sequencetype_info());
        $this->assertSame(2, $setitem2->get_minprerequisites());
        $this->assertCount(2, $setitem2->get_children());
        $this->assertSame(null, $courseitem1->get_previous());
        $this->assertSame(null, $courseitem2->get_previous());
        $this->assertSame(null, $courseitem3->get_previous());

        $top->update_set($setitem2, ['sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 7, 'points' => 11, 'completiondelay' => HOURSECS * 3]);
        $this->assertFalse(top::load($program1->id)->is_problem_detected());
        $this->assertSame((int)$program1->id, $setitem2->get_programid());
        $this->assertSame('Very nice set', $setitem2->get_fullname());
        $this->assertSame(false, $setitem2->is_problem_detected());
        $this->assertSame(set::SEQUENCE_TYPE_MINPOINTS, $setitem2->get_sequencetype());
        $this->assertSame('Minimum 7 points', $setitem2->get_sequencetype_info());
        $this->assertSame(null, $setitem2->get_minprerequisites());
        $this->assertSame(11, $setitem2->get_points());
        $this->assertSame(HOURSECS * 3, $setitem2->get_completiondelay());
        $this->assertSame(7, $setitem2->get_minpoints());
        $this->assertCount(2, $setitem2->get_children());

        $top->update_set($setitem2, ['points' => 88]);
        $this->assertFalse(top::load($program1->id)->is_problem_detected());
        $this->assertSame((int)$program1->id, $setitem2->get_programid());
        $this->assertSame('Very nice set', $setitem2->get_fullname());
        $this->assertSame(false, $setitem2->is_problem_detected());
        $this->assertSame(set::SEQUENCE_TYPE_MINPOINTS, $setitem2->get_sequencetype());
        $this->assertSame('Minimum 7 points', $setitem2->get_sequencetype_info());
        $this->assertSame(null, $setitem2->get_minprerequisites());
        $this->assertSame(88, $setitem2->get_points());
        $this->assertSame(HOURSECS * 3, $setitem2->get_completiondelay());
        $this->assertSame(7, $setitem2->get_minpoints());
        $this->assertCount(2, $setitem2->get_children());
    }

    public function test_update_course(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $top->append_course($top, $course1->id);
        $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $top->append_set($top, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);

        /** @var course $courseitem1 */
        $courseitem1 = $top->get_children()[0];
        $this->assertSame((int)$program1->id, $courseitem1->get_programid());
        $this->assertSame($course1->fullname, $courseitem1->get_fullname());
        $this->assertSame(false, $courseitem1->is_problem_detected());
        $this->assertSame(1, $courseitem1->get_points());

        $courseitem1 = $top->update_course($courseitem1, []);
        $this->assertSame($course1->fullname, $courseitem1->get_fullname());
        $this->assertSame(false, $courseitem1->is_problem_detected());
        $this->assertSame(1, $courseitem1->get_points());

        $courseitem1 = $top->update_course($courseitem1, ['points' => 23, 'completiondelay' => HOURSECS * 3]);
        $this->assertSame($course1->fullname, $courseitem1->get_fullname());
        $this->assertSame(false, $courseitem1->is_problem_detected());
        $this->assertSame(23, $courseitem1->get_points());
        $this->assertSame(HOURSECS * 3, $courseitem1->get_completiondelay());

        $courseitem1 = $top->update_course($courseitem1, []);
        $this->assertSame($course1->fullname, $courseitem1->get_fullname());
        $this->assertSame(false, $courseitem1->is_problem_detected());
        $this->assertSame(23, $courseitem1->get_points());
        $this->assertSame(HOURSECS * 3, $courseitem1->get_completiondelay());

        $courseitem1 = $top->update_course($courseitem1, ['points' => 0, 'completiondelay' => 0]);
        $this->assertSame($course1->fullname, $courseitem1->get_fullname());
        $this->assertSame(false, $courseitem1->is_problem_detected());
        $this->assertSame(0, $courseitem1->get_points());
        $this->assertSame(0, $courseitem1->get_completiondelay());
    }

    public function test_update_training(): void {
        if (!\tool_muprog\local\util::is_mutrain_available()) {
            $this->markTestSkipped('mutrain not available');
        }

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_mutrain_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field3']
        );
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']
        );

        $data = (object)[
            'name' => 'Some framework',
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Other framework',
            'fields' => [$field2->get('id')],
        ];
        $framework2 = $traininggenerator->create_framework($data);

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 2]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 4, 'customfield_field2' => 23]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field2' => 11]);
        $course5 = $this->getDataGenerator()->create_course();

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $top = top::load($program1->id);
        $top->append_training($top, $framework1->id);

        /** @var training $trainingitem1 */
        $trainingitem1 = $top->get_children()[0];
        $this->assertSame((int)$program1->id, $trainingitem1->get_programid());
        $this->assertSame($framework1->name, $trainingitem1->get_fullname());
        $this->assertSame(false, $trainingitem1->is_problem_detected());
        $this->assertSame(1, $trainingitem1->get_points());

        $trainingitem1 = $top->update_training($trainingitem1, []);
        $this->assertSame($framework1->name, $trainingitem1->get_fullname());
        $this->assertSame(false, $trainingitem1->is_problem_detected());
        $this->assertSame(1, $trainingitem1->get_points());

        $trainingitem1 = $top->update_training($trainingitem1, ['points' => 23, 'completiondelay' => HOURSECS * 3]);
        $this->assertSame($framework1->name, $trainingitem1->get_fullname());
        $this->assertSame(false, $trainingitem1->is_problem_detected());
        $this->assertSame(23, $trainingitem1->get_points());
        $this->assertSame(HOURSECS * 3, $trainingitem1->get_completiondelay());

        $trainingitem1 = $top->update_training($trainingitem1, []);
        $this->assertSame($framework1->name, $trainingitem1->get_fullname());
        $this->assertSame(false, $trainingitem1->is_problem_detected());
        $this->assertSame(23, $trainingitem1->get_points());
        $this->assertSame(HOURSECS * 3, $trainingitem1->get_completiondelay());

        $trainingitem1 = $top->update_training($trainingitem1, ['points' => 0, 'completiondelay' => 0]);
        $this->assertSame($framework1->name, $trainingitem1->get_fullname());
        $this->assertSame(false, $trainingitem1->is_problem_detected());
        $this->assertSame(0, $trainingitem1->get_points());
        $this->assertSame(0, $trainingitem1->get_completiondelay());
    }

    public function test_move_item(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $top->append_course($top, $course1->id);
        /** @var course $courseitem1 */
        $courseitem1 = $top->get_children()[0];
        $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        /** @var set $setitem2 */
        $setitem2 = $top->get_children()[1];
        $top->append_set($top, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        /** @var set $setitem4 */
        $setitem4 = $top->get_children()[2];
        $top->append_set($top, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINANYORDER]);
        /** @var set $setitem3 */
        $setitem3 = $top->get_children()[3];
        $top->append_set($top, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 2, 'points' => 7]);
        /** @var set $setitem5 */
        $setitem5 = $top->get_children()[4];
        $top->append_course($setitem2, $course2->id);
        /** @var course $courseitem2 */
        $courseitem2 = $setitem2->get_children()[0];
        $top->append_course($setitem2, $course3->id);
        /** @var course $courseitem3 */
        $courseitem3 = $setitem2->get_children()[1];
        $top->append_course($setitem2, $course4->id);
        /** @var course $courseitem4 */
        $courseitem4 = $setitem2->get_children()[2];
        $top->append_course($setitem2, $course5->id);
        /** @var course $courseitem5 */
        $courseitem5 = $setitem2->get_children()[3];
        $this->assertSame(false, $top->is_problem_detected());

        $this->assertTrue($top->move_item($courseitem3->get_id(), $setitem2->get_id(), 1));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(4, $setitem2->get_children());
        $this->assertSame($courseitem2, $setitem2->get_children()[0]);
        $this->assertSame($courseitem3, $setitem2->get_children()[1]);
        $this->assertSame($courseitem4, $setitem2->get_children()[2]);
        $this->assertSame($courseitem5, $setitem2->get_children()[3]);

        $this->assertTrue($top->move_item($courseitem3->get_id(), $setitem2->get_id(), 0));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(4, $setitem2->get_children());
        $this->assertSame($courseitem3, $setitem2->get_children()[0]);
        $this->assertSame($courseitem2, $setitem2->get_children()[1]);
        $this->assertSame($courseitem4, $setitem2->get_children()[2]);
        $this->assertSame($courseitem5, $setitem2->get_children()[3]);

        $this->assertTrue($top->move_item($courseitem3->get_id(), $setitem2->get_id(), 10));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(4, $setitem2->get_children());
        $this->assertSame($courseitem2, $setitem2->get_children()[0]);
        $this->assertSame($courseitem4, $setitem2->get_children()[1]);
        $this->assertSame($courseitem5, $setitem2->get_children()[2]);
        $this->assertSame($courseitem3, $setitem2->get_children()[3]);

        $this->assertTrue($top->move_item($courseitem3->get_id(), $setitem2->get_id(), 1));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(4, $setitem2->get_children());
        $this->assertSame($courseitem2, $setitem2->get_children()[0]);
        $this->assertSame($courseitem3, $setitem2->get_children()[1]);
        $this->assertSame($courseitem4, $setitem2->get_children()[2]);
        $this->assertSame($courseitem5, $setitem2->get_children()[3]);

        $this->assertCount(5, $top->get_children());
        $this->assertTrue($top->move_item($courseitem1->get_id(), $setitem2->get_id(), 1));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(4, $top->get_children());
        $this->assertCount(5, $setitem2->get_children());
        $this->assertSame($courseitem2, $setitem2->get_children()[0]);
        $this->assertSame($courseitem1, $setitem2->get_children()[1]);
        $this->assertSame($courseitem3, $setitem2->get_children()[2]);
        $this->assertSame($courseitem4, $setitem2->get_children()[3]);
        $this->assertSame($courseitem5, $setitem2->get_children()[4]);

        $this->assertTrue($top->move_item($courseitem1->get_id(), $setitem3->get_id(), 0));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(1, $setitem3->get_children());
        $this->assertSame($courseitem1, $setitem3->get_children()[0]);
        $this->assertCount(4, $setitem2->get_children());
        $this->assertSame($courseitem2, $setitem2->get_children()[0]);
        $this->assertSame($courseitem3, $setitem2->get_children()[1]);
        $this->assertSame($courseitem4, $setitem2->get_children()[2]);
        $this->assertSame($courseitem5, $setitem2->get_children()[3]);

        $this->assertTrue($top->move_item($setitem3->get_id(), $setitem2->get_id(), 2));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(1, $setitem3->get_children());
        $this->assertSame($courseitem1, $setitem3->get_children()[0]);
        $this->assertCount(5, $setitem2->get_children());
        $this->assertSame($courseitem2, $setitem2->get_children()[0]);
        $this->assertSame($courseitem3, $setitem2->get_children()[1]);
        $this->assertSame($setitem3, $setitem2->get_children()[2]);
        $this->assertSame($courseitem4, $setitem2->get_children()[3]);
        $this->assertSame($courseitem5, $setitem2->get_children()[4]);

        $this->assertTrue($top->move_item($setitem3->get_id(), $setitem5->get_id(), 0));
        $this->assertSame(false, $top->is_problem_detected());
        $this->assertCount(1, $setitem3->get_children());
        $this->assertSame($courseitem1, $setitem3->get_children()[0]);
        $this->assertCount(1, $setitem5->get_children());
        $this->assertSame(null, $setitem5->get_minprerequisites());
        $this->assertSame(7, $setitem5->get_points());
        $this->assertSame(2, $setitem5->get_minpoints());

        // Test all invalid operations.
        $this->assertDebuggingNotCalled();

        $this->assertFalse($top->move_item($top->get_id(), $setitem2->get_id(), 0));
        $this->assertDebuggingCalled('Top item cannot be moved');

        $this->assertFalse($top->move_item($setitem2->get_id(), $setitem2->get_id(), 0));
        $this->assertDebuggingCalled('Item cannot be moved to self');

        $this->assertFalse($top->move_item(-1, $setitem2->get_id(), 0));
        $this->assertDebuggingCalled('Cannot find new item');

        $this->assertFalse($top->move_item($setitem2->get_id(), -1, 0));
        $this->assertDebuggingCalled('Cannot find new parent of item');

        $this->assertFalse($top->move_item($setitem5->get_id(), $setitem3->get_id(), 0));
        $this->assertDebuggingCalled('Cannot move item to own child');

        $top2 = top::load($program2->id);
        $this->assertFalse($top->move_item($setitem2->get_id(), $top2->get_id(), 0));
        $this->assertDebuggingCalled('Cannot find new parent of item');
    }

    public function test_delete_item(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $this->assertFalse($top->is_deletable());

        $top->append_course($top, $course1->id);
        /** @var course $courseitem1 */
        $courseitem1 = $top->get_children()[0];
        $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        /** @var set $setitem1 */
        $setitem1 = $top->get_children()[1];
        $top->append_set($top, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        /** @var set $setitem4 */
        $setitem4 = $top->get_children()[2];
        $top->append_set($top, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINANYORDER]);
        /** @var set $setitem3 */
        $setitem3 = $top->get_children()[3];
        $top->append_set($top, ['fullname' => 'Extra set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 3]);
        /** @var set $setitem5 */
        $setitem5 = $top->get_children()[4];
        $top->append_course($setitem1, $course2->id);
        /** @var course $courseitem2 */
        $courseitem2 = $setitem1->get_children()[0];
        $top->append_course($setitem1, $course3->id);
        /** @var course $courseitem3 */
        $courseitem3 = $setitem1->get_children()[1];
        $top->append_course($setitem1, $course4->id);
        /** @var course $courseitem4 */
        $courseitem4 = $setitem1->get_children()[2];
        $top->append_course($setitem4, $course5->id);
        /** @var course $courseitem5 */
        $courseitem5 = $setitem4->get_children()[0];
        $this->assertSame(false, $top->is_problem_detected());

        $this->assertFalse($setitem4->is_deletable());
        $this->assertTrue($setitem3->is_deletable());
        $this->assertTrue($courseitem5->is_deletable());
        $this->assertTrue($setitem5->is_deletable());

        $this->assertFalse($top->delete_item($setitem4->get_id()));
        $this->assertTrue($top->delete_item($courseitem5->get_id()));
        $this->assertTrue($top->delete_item($setitem4->get_id()));
        $this->assertTrue($top->delete_item($setitem5->get_id()));
    }

    /**
     * Assert that cloned item has the same structure.
     * @param item $source
     * @param item $target
     * @return void
     */
    public static function assertItemCloned(item $source, item $target): void {
        self::assertSame(get_class($source), get_class($target));
        self::assertSame($source->get_fullname(), $target->get_fullname());
        self::assertSame(count($source->get_children()), count($target->get_children()));
        self::assertSame($source->get_points(), $target->get_points());
        self::assertSame($source->get_completiondelay(), $target->get_completiondelay());

        if ($source instanceof course) {
            self::assertSame($source->get_courseid(), $target->get_courseid());
        } else if ($source instanceof training) {
            self::assertSame($source->get_trainingid(), $target->get_trainingid());
        } else if ($source instanceof set) {
            self::assertSame($source->get_sequencetype_info(), $target->get_sequencetype_info());
        } else {
            throw new \coding_exception('Unexpected class');
        }

        foreach ($source->get_children() as $k => $child) {
            self::assertItemCloned($child, $target->get_children()[$k]);
        }
    }

    public function test_content_import(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);
        $program3 = $generator->create_program(['fullname' => 'abraka']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['fullname' => $top1->get_fullname(), 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $item1x0 = $top1->append_course($top1, $course1->id);
        $item1x1 = $top1->append_set($top1, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 1]);
        $item1x1x0 = $top1->append_set($item1x1, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $item1x1x1 = $top1->append_course($item1x1, $course2->id);
        $item1x1x2 = $top1->append_set($item1x1, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS,
            'points' => 2, 'minpoints' => 4, 'completiondelay' => DAYSECS]);
        $item1x1x2x0 = $top1->append_course($item1x1x2, $course3->id, ['points' => 3, 'completiondelay' => DAYSECS * 2]);
        $item1x1x2x1 = $top1->append_course($item1x1x2, $course4->id);
        $item1x1x2x3 = $top1->append_course($item1x1x2, $course5->id);

        $top2 = top::load($program2->id);
        $top2->update_set($top2, ['fullname' => $top2->get_fullname(), 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);

        $top3 = top::load($program3->id);
        $top3->update_set($top3, ['fullname' => $top3->get_fullname(), 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER, 'completiondelay' => HOURSECS]);
        $item3x0 = $top3->append_set($top3, ['fullname' => 'Repeated set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 1]);
        $item3x0x0 = $top3->append_course($item3x0, $course1->id);
        $item3x0x1 = $top3->append_course($item3x0, $course2->id);
        $item3x0x2 = $top3->append_course($item3x0, $course3->id);

        $top2->content_import((object)['id' => $program2->id, 'fromprogram' => $program1->id]);
        $top2 = top::load($program2->id);
        $this->assertSame($top1->get_sequencetype_info(), $top2->get_sequencetype_info());
        $this->assertSame($program2->fullname, $top2->get_fullname());
        $this->assertSame($top1->get_points(), $top2->get_points());
        $this->assertCount(2, $top2->get_children());
        $this->assertItemCloned($top1->get_children()[0], $top2->get_children()[0]);
        $this->assertItemCloned($top1->get_children()[1], $top2->get_children()[1]);

        $top2->content_import((object)['id' => $program2->id, 'fromprogram' => $program3->id]);
        $top2 = top::load($program2->id);
        $this->assertSame($top1->get_sequencetype_info(), $top2->get_sequencetype_info());
        $this->assertSame($program2->fullname, $top2->get_fullname());
        $this->assertCount(3, $top2->get_children());
        $this->assertItemCloned($top1->get_children()[0], $top2->get_children()[0]);
        $this->assertItemCloned($top1->get_children()[1], $top2->get_children()[1]);
        $this->assertItemCloned($top3->get_children()[0], $top2->get_children()[2]);

        \delete_course($course1->id, false);
        $program4 = $generator->create_program(['fullname' => 'dabra']);
        $top4 = top::load($program4->id);
        $top4->content_import((object)['id' => $program4->id, 'fromprogram' => $program1->id]);
        $top4 = top::load($program4->id);
        $this->assertCount(1, $top4->get_children());
        $this->assertItemCloned($top1->get_children()[1], $top4->get_children()[0]);
    }

    public function test_content_import_duplicates(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);
        $program3 = $generator->create_program(['fullname' => 'abraka']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['fullname' => $top1->get_fullname(), 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $item1x0 = $top1->append_course($top1, $course1->id);
        $item1x1 = $top1->append_set($top1, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 1]);
        $item1x1x0 = $top1->append_set($item1x1, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        $item1x1x1 = $top1->append_course($item1x1, $course2->id);
        $item1x1x2 = $top1->append_set($item1x1, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS,
            'points' => 2, 'minpoints' => 4, 'completiondelay' => DAYSECS]);
        $item1x1x2x0 = $top1->append_course($item1x1x2, $course3->id, ['points' => 3, 'completiondelay' => DAYSECS * 2]);
        $item1x1x2x1 = $top1->append_course($item1x1x2, $course4->id);
        $item1x1x2x3 = $top1->append_course($item1x1x2, $course5->id);

        $top2 = top::load($program2->id);
        $top2->content_import((object)['id' => $program2->id, 'fromprogram' => $program1->id]);
        $top2 = top::load($program2->id);
        $top2->content_import((object)['id' => $program2->id, 'fromprogram' => $program1->id]);
        $top2 = top::load($program2->id);
        $this->assertCount(3, $top2->get_children());
        $this->assertItemCloned($top1->get_children()[0], $top2->get_children()[0]);
        $this->assertItemCloned($top1->get_children()[1], $top2->get_children()[1]);
        $this->assertItemCloned($top1->get_children()[1], $top2->get_children()[2]);
    }

    public function test_content_import_training(): void {
        if (!\tool_muprog\local\util::is_mutrain_available()) {
            $this->markTestSkipped('mutrain not available');
        }

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        /** @var \tool_mutrain_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field3']
        );
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']
        );

        $data = (object)[
            'name' => 'Some framework',
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Other framework',
            'fields' => [$field2->get('id')],
        ];
        $framework2 = $traininggenerator->create_framework($data);

        $course1 = $this->getDataGenerator()->create_course(['customfield_field1' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['customfield_field1' => 2]);
        $course3 = $this->getDataGenerator()->create_course(['customfield_field1' => 4, 'customfield_field2' => 23]);
        $course4 = $this->getDataGenerator()->create_course(['customfield_field2' => 11]);
        $course5 = $this->getDataGenerator()->create_course();

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $top1 = top::load($program1->id);
        $top1->append_training($top1, $framework1->id);
        $top2 = top::load($program2->id);

        $top2->content_import((object)['id' => $program2->id, 'fromprogram' => $program1->id]);
        $top2 = top::load($program2->id);
        $this->assertSame($program2->fullname, $top2->get_fullname());
        $this->assertSame($top1->get_points(), $top2->get_points());
        $this->assertCount(1, $top2->get_children());
        $this->assertItemCloned($top1->get_children()[0], $top2->get_children()[0]);
    }

    public function test_orphaned_items(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $top->append_course($top, $course1->id);
        /** @var course $courseitem1 */
        $courseitem1 = $top->get_children()[0];
        $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        /** @var set $setitem2 */
        $setitem2 = $top->get_children()[1];
        $top->append_set($setitem2, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        /** @var set $setitem4 */
        $setitem4 = $setitem2->get_children()[0];
        $top->append_set($setitem4, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINANYORDER]);
        /** @var set $setitem3 */
        $setitem3 = $setitem4->get_children()[0];
        $top->append_course($setitem2, $course2->id);
        /** @var course $courseitem2 */
        $courseitem2 = $setitem2->get_children()[1];
        $top->append_course($setitem2, $course3->id);
        /** @var course $courseitem3 */
        $courseitem3 = $setitem2->get_children()[2];
        $top->append_course($setitem4, $course4->id);
        /** @var course $courseitem4 */
        $courseitem4 = $setitem4->get_children()[1];
        $top->append_course($setitem3, $course5->id);
        /** @var course $courseitem5 */
        $courseitem5 = $setitem3->get_children()[0];
        $this->assertSame(false, $top->is_problem_detected());

        $DB->delete_records('tool_muprog_item', ['id' => $setitem4->get_id()]);
        $this->assertDebuggingNotCalled();
        $top = top::load($program1->id);
        $this->assertTrue($top->is_problem_detected());
        $this->assertDebuggingCalled();
        $osets = $top->get_orphaned_sets();
        $this->assertCount(1, $osets);
        $this->assertArrayHasKey($setitem3->get_id(), $osets);
        $ocourses = $top->get_orphaned_courses();
        $this->assertCount(2, $ocourses);
        $this->assertArrayHasKey($courseitem4->get_id(), $ocourses);
        $this->assertArrayHasKey($courseitem5->get_id(), $ocourses);

        $this->assertTrue($top->delete_item($setitem3->get_id()));
        $this->assertTrue($top->delete_item($courseitem4->get_id()));
        $this->assertTrue($top->delete_item($courseitem5->get_id()));
        $this->assertTrue($top->is_problem_detected());

        $top = top::load($program1->id);
        $this->assertFalse($top->is_problem_detected());
        $this->assertDebuggingNotCalled();
        $this->assertCount(2, $top->get_children());
        $courseitem1 = $top->get_children()[0];
        $setitem2 = $top->get_children()[1];
        $this->assertCount(2, $setitem2->get_children());
    }

    public function test_autorepair(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['fullname' => 'pokus']);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        $top = top::load($program1->id);
        $top->append_course($top, $course1->id);
        /** @var course $courseitem1 */
        $courseitem1 = $top->get_children()[0];
        $top->append_set($top, ['fullname' => 'Nice set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINORDER]);
        /** @var set $setitem2 */
        $setitem2 = $top->get_children()[1];
        $top->append_set($setitem2, ['fullname' => 'Other set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        /** @var set $setitem4 */
        $setitem4 = $setitem2->get_children()[0];
        $top->append_set($setitem4, ['fullname' => 'Third set', 'sequencetype' => set::SEQUENCE_TYPE_ALLINANYORDER]);
        /** @var set $setitem3 */
        $setitem3 = $setitem4->get_children()[0];
        $top->append_course($setitem2, $course2->id);
        /** @var course $courseitem2 */
        $courseitem2 = $setitem2->get_children()[1];
        $top->append_course($setitem2, $course3->id);
        /** @var course $courseitem3 */
        $courseitem3 = $setitem2->get_children()[2];
        $top->append_course($setitem4, $course4->id);
        /** @var course $courseitem4 */
        $courseitem4 = $setitem4->get_children()[1];
        $top->append_course($setitem3, $course5->id);
        /** @var course $courseitem5 */
        $courseitem5 = $setitem3->get_children()[0];

        $DB->delete_records('tool_muprog_prerequisite', []);
        $DB->set_field('tool_muprog_item', 'previtemid', null, []);

        $top = top::load($program1->id);
        $this->assertTrue($top->is_problem_detected());

        $top->autorepair();
        $top = top::load($program1->id);
        $this->assertFalse($top->is_problem_detected());
        $this->assertDebuggingNotCalled();
    }
}
