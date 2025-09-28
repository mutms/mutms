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

namespace tool_muprog\phpunit;

use tool_muprog\local\content\course;
use tool_muprog\local\content\set;
use tool_muprog\local\program;

/**
 * Program generator test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create_program(): void {
        global $DB;

        $syscontext = \context_system::instance();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $this->assertInstanceOf('tool_muprog_generator', $generator);

        $this->setCurrentTimeStart();
        $program = $generator->create_program([]);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$syscontext->id, $program->contextid);
        $this->assertSame('Program 1', $program->fullname);
        $this->assertSame('prg1', $program->idnumber);
        $this->assertSame('', $program->description);
        $this->assertSame('1', $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('0', $program->publicaccess);
        $this->assertSame('0', $program->archived);
        $this->assertSame('0', $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);

        $sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id]);
        $this->assertCount(0, $sources);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'publicaccess' => '1',
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
            'sources' => ['manual' => []],
            'cohorts' => [$cohort1->id, $cohort2->name],
        ];

        $this->setCurrentTimeStart();
        $program = $generator->create_program($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame($data->description, $program->description);
        $this->assertSame($data->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame($data->publicaccess, $program->publicaccess);
        $this->assertSame($data->archived, $program->archived);
        $this->assertSame($data->creategroups, $program->creategroups);
        $this->assertSame($data->timeallocationstart, $program->timeallocationstart);
        $this->assertSame($data->timeallocationend, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);

        $sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id]);
        $this->assertCount(1, $sources);
        $source = reset($sources);
        $this->assertSame('manual', $source->type);
        $cs = $DB->get_records('tool_muprog_cohort', ['programid' => $program->id], 'cohortid ASC');
        $this->assertCount(2, $cs);
        $cs = array_values($cs);
        $this->assertSame($cohort1->id, $cs[0]->cohortid);
        $this->assertSame($cohort2->id, $cs[1]->cohortid);

        $category2 = $this->getDataGenerator()->create_category(['name' => 'Cat 2', 'idnumber' => 'CT2']);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $program = $generator->create_program(['category' => $category2->name]);
        $this->assertSame((string)$catcontext2->id, $program->contextid);

        $program = $generator->create_program(['category' => $category2->idnumber]);
        $this->assertSame((string)$catcontext2->id, $program->contextid);

        $data = (object)[
            'cohortids' => "$cohort1->name, $cohort2->id",
        ];
        $program = $generator->create_program($data);
        $cs = $DB->get_records('tool_muprog_cohort', ['programid' => $program->id]);
        $this->assertCount(2, $cs);
        $this->assertCount(2, $cs);
        $cs = array_values($cs);
        $this->assertSame($cohort1->id, $cs[0]->cohortid);
        $this->assertSame($cohort2->id, $cs[1]->cohortid);

        $data = (object)[
            'sources' => 'manual, cohort',
        ];
        $program = $generator->create_program($data);
        $sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id], 'type ASC');
        $this->assertCount(2, $sources);
        $sources = array_values($sources);
        $this->assertSame('cohort', $sources[0]->type);
        $this->assertSame('manual', $sources[1]->type);
    }

    public function test_create_program_item(): void {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $this->assertInstanceOf('tool_muprog_generator', $generator);

        $program = $generator->create_program([]);

        $record = [
            'programid' => $program->id,
            'courseid' => $course1->id,
        ];
        $item1 = $generator->create_program_item($record);
        $this->assertInstanceOf(course::class, $item1);
        $this->assertSame($program->id, (string)$item1->get_programid());
        $this->assertSame($course1->id, (string)$item1->get_courseid());

        $record = [
            'program' => $program->fullname,
            'course' => $course2->fullname,
        ];
        $item2 = $generator->create_program_item($record);
        $this->assertInstanceOf(course::class, $item2);
        $this->assertSame($program->id, (string)$item2->get_programid());
        $this->assertSame($course2->id, (string)$item2->get_courseid());

        $record = [
            'program' => $program->fullname,
            'fullname' => 'First set',
        ];
        /** @var set $item3 */
        $item3 = $generator->create_program_item($record);
        $this->assertInstanceOf(set::class, $item3);
        $this->assertSame($program->id, (string)$item3->get_programid());
        $this->assertSame('First set', $item3->get_fullname());
        $this->assertSame(1, $item3->get_minprerequisites());
        $this->assertSame(set::SEQUENCE_TYPE_ALLINANYORDER, $item3->get_sequencetype());
        $top = program::load_content($program->id);
        $this->assertSame($item3->get_id(), $top->get_children()[2]->get_id());

        $record = [
            'programid' => $program->id,
            'fullname' => 'Second set',
            'parent' => 'First set',
            'minprerequisites' => 3,
            'sequencetype' => set::SEQUENCE_TYPE_ATLEAST,
        ];
        /** @var set $item4 */
        $item4 = $generator->create_program_item($record);
        $this->assertInstanceOf(set::class, $item4);
        $this->assertSame($program->id, (string)$item4->get_programid());
        $this->assertSame('Second set', $item4->get_fullname());
        $this->assertSame(3, $item4->get_minprerequisites());
        $this->assertSame(set::SEQUENCE_TYPE_ATLEAST, $item4->get_sequencetype());
        $top = program::load_content($program->id);
        $this->assertSame($item4->get_id(), $top->get_children()[2]->get_children()[0]->get_id());

        $record = [
            'programid' => $program->id,
            'courseid' => $course3->id,
            'parent' => 'First set',
        ];
        $item5 = $generator->create_program_item($record);
        $this->assertInstanceOf(course::class, $item5);
        $this->assertSame($program->id, (string)$item5->get_programid());
        $this->assertSame($course3->id, (string)$item5->get_courseid());
        $top = program::load_content($program->id);
        $this->assertSame($item5->get_id(), $top->get_children()[2]->get_children()[1]->get_id());

        if (!\tool_muprog\local\util::is_mutrain_available()) {
            return;
        }

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

        $record = [
            'programid' => $program->id,
            'trainingid' => $framework1->id,
            'parent' => 'First set',
        ];
        $item6 = $generator->create_program_item($record);
        $this->assertInstanceOf(\tool_muprog\local\content\training::class, $item6);
        $this->assertSame($program->id, (string)$item6->get_programid());
        $this->assertSame($framework1->id, (string)$item6->get_trainingid());
        $top = program::load_content($program->id);
        $this->assertSame($item6->get_id(), $top->get_children()[2]->get_children()[2]->get_id());

        $record = [
            'programid' => $program->id,
            'training' => $framework2->name,
            'parent' => 'First set',
        ];
        $item7 = $generator->create_program_item($record);
        $this->assertInstanceOf(\tool_muprog\local\content\training::class, $item7);
        $this->assertSame($program->id, (string)$item7->get_programid());
        $this->assertSame($framework2->id, (string)$item7->get_trainingid());
        $top = program::load_content($program->id);
        $this->assertSame($item7->get_id(), $top->get_children()[2]->get_children()[3]->get_id());
    }

    public function test_create_program_allocation(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $this->assertInstanceOf('tool_muprog_generator', $generator);

        $program = $generator->create_program([]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $allocation1 = $generator->create_program_allocation(['programid' => $program->id, 'userid' => $user1->id]);
        $source = $DB->get_record('tool_muprog_source', ['type' => 'manual', 'programid' => $program->id]);
        $this->assertSame($user1->id, $allocation1->userid);
        $this->assertSame($program->id, $allocation1->programid);
        $this->assertSame($source->id, $allocation1->sourceid);

        $allocation2 = $generator->create_program_allocation(['program' => $program->fullname, 'user' => $user2->username]);
        $this->assertSame($user2->id, $allocation2->userid);
        $this->assertSame($program->id, $allocation2->programid);
        $this->assertSame($source->id, $allocation2->sourceid);

        $now = time();
        $data = [
            'program' => $program->fullname,
            'user' => $user3->username,
            'timeallocated' => $now - 100,
            'timestart' => $now - 50,
            'timedue' => $now + 200,
            'timeend' => $now + 300,
        ];
        $allocation3 = $generator->create_program_allocation($data);
        $this->assertEquals($data['timeallocated'], $allocation3->timeallocated);
        $this->assertEquals($data['timestart'], $allocation3->timestart);
        $this->assertEquals($data['timedue'], $allocation3->timedue);
        $this->assertEquals($data['timeend'], $allocation3->timeend);
    }
}
