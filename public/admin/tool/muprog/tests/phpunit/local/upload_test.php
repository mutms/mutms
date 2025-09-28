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

use tool_muprog\local\content\set;
use tool_muprog\local\content\top;

/**
 * Program helper test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\upload
 */
final class upload_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_process(): void {
        global $DB;
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
            'sources' => ['manual' => [], 'approval' => [], 'selfallocation' => []],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
            'sources' => ['manual' => [],
                'approval' => ['approval_allowrequest' => 0],
                'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10]],
        ]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        if (\tool_muprog\local\util::is_mutrain_available()) {
            /** @var \tool_mutrain_generator $traininggenerator */
            $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');
            $fielcategory = $this->getDataGenerator()->create_custom_field_category(
                ['component' => 'core_course', 'area' => 'course']
            );
            $field1 = $this->getDataGenerator()->create_custom_field(
                ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
            );
            $data = (object)[
                'name' => 'Some framework',
                'fields' => [$field1->get('id')],
            ];
            $framework1 = $traininggenerator->create_framework($data);
        } else {
            $framework1 = null;
        }

        $top0 = top::load($program0->id);
        $set0 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item0x1 = $top0->append_course($set0, $course1->id);
        if ($framework1) {
            $item0x2 = $top0->append_training($set0, $framework1->id);
        }

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['sequencetype' => 'allinorder', 'completiondelay' => 3]);
        $set1 = $top1->append_set($top1, ['fullname' => 'Another set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 3]);
        $item1x1 = $top1->append_course($set1, $course1->id, ['points' => 3]);
        if ($framework1) {
            $item1x2 = $top1->append_training($set1, $framework1->id, ['completiondelay' => 11]);
        }

        $rawprograms = \tool_muprog\local\export::export_programs('1=1', []);
        $oldprograms = unserialize(serialize($rawprograms));
        $this->assertCount(3, $rawprograms);

        \tool_muprog\local\program::delete($program0->id);
        \tool_muprog\local\program::delete($program1->id);
        \tool_muprog\local\program::delete($program2->id);

        \tool_muprog\local\upload::validate_references($rawprograms);

        $data = (object)[
            'usecategory' => 1,
            'encoding' => 'UTF-8',
        ];
        \tool_muprog\local\upload::process($data, $rawprograms);

        $rawprograms2 = \tool_muprog\local\export::export_programs('1=1', []);
        $this->assertEquals($oldprograms, $rawprograms2);

        $program0x = $DB->get_record('tool_muprog_program', ['idnumber' => $program0->idnumber], '*', MUST_EXIST);
        $this->assertSame($program0->contextid, $program0x->contextid);
        $program1x = $DB->get_record('tool_muprog_program', ['idnumber' => $program1->idnumber], '*', MUST_EXIST);
        $this->assertSame($program1->contextid, $program1x->contextid);
        $program2x = $DB->get_record('tool_muprog_program', ['idnumber' => $program2->idnumber], '*', MUST_EXIST);
        $this->assertSame($program2->contextid, $program2x->contextid);

        \tool_muprog\local\program::delete($program0x->id);
        \tool_muprog\local\program::delete($program1x->id);
        \tool_muprog\local\program::delete($program2x->id);

        \tool_muprog\local\upload::validate_references($rawprograms2);
        $data = (object)[
            'usecategory' => 0,
            'contextid' => $catcontext2->id,
            'encoding' => 'UTF-8',
        ];
        \tool_muprog\local\upload::process($data, $rawprograms2);
        $program0x = $DB->get_record('tool_muprog_program', ['idnumber' => $program0->idnumber], '*', MUST_EXIST);
        $this->assertSame($catcontext2->id, (int)$program0x->contextid);
        $program1x = $DB->get_record('tool_muprog_program', ['idnumber' => $program1->idnumber], '*', MUST_EXIST);
        $this->assertSame($catcontext2->id, (int)$program1x->contextid);
        $program2x = $DB->get_record('tool_muprog_program', ['idnumber' => $program2->idnumber], '*', MUST_EXIST);
        $this->assertSame($catcontext2->id, (int)$program2x->contextid);
    }

    public function test_decode_json_file(): void {
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
            'sources' => ['manual' => [], 'approval' => [], 'selfallocation' => []],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
            'sources' => ['manual' => [],
                'approval' => ['approval_allowrequest' => 0],
                'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10]],
        ]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        if (\tool_muprog\local\util::is_mutrain_available()) {
            /** @var \tool_mutrain_generator $traininggenerator */
            $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');
            $fielcategory = $this->getDataGenerator()->create_custom_field_category(
                ['component' => 'core_course', 'area' => 'course']
            );
            $field1 = $this->getDataGenerator()->create_custom_field(
                ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
            );
            $data = (object)[
                'name' => 'Some framework',
                'fields' => [$field1->get('id')],
            ];
            $framework1 = $traininggenerator->create_framework($data);
        } else {
            $framework1 = null;
        }

        $top0 = top::load($program0->id);
        $set0 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item0x1 = $top0->append_course($set0, $course1->id);
        if ($framework1) {
            $item0x2 = $top0->append_training($set0, $framework1->id);
        }

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['sequencetype' => 'allinorder', 'completiondelay' => 3]);
        $set1 = $top1->append_set($top1, ['fullname' => 'Another set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 3]);
        $item1x1 = $top1->append_course($set1, $course1->id, ['points' => 3]);
        if ($framework1) {
            $item1x2 = $top1->append_training($set1, $framework1->id, ['completiondelay' => 11]);
        }

        $rawprograms = \tool_muprog\local\export::export_programs('1=1', []);

        $data = (object)[
            'contextid' => 0,
            'archived' => 0,
        ];
        $file = \tool_muprog\local\export::export_json($data);

        $dir = \make_request_directory();
        $packer = get_file_packer('application/zip');
        $packer->extract_to_pathname($file, $dir);
        $jsonfile = "$dir/programs.json";

        $result = \tool_muprog\local\upload::decode_json_file($jsonfile, 'UTF-8');

        $this->assertEquals($rawprograms, $result);
    }

    public function test_decode_csv_files(): void {
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
            'sources' => ['manual' => [], 'approval' => [], 'selfallocation' => []],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
            'sources' => ['manual' => [],
                'approval' => ['approval_allowrequest' => 0],
                'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10]],
        ]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        if (\tool_muprog\local\util::is_mutrain_available()) {
            /** @var \tool_mutrain_generator $traininggenerator */
            $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');
            $fielcategory = $this->getDataGenerator()->create_custom_field_category(
                ['component' => 'core_course', 'area' => 'course']
            );
            $field1 = $this->getDataGenerator()->create_custom_field(
                ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
            );
            $data = (object)[
                'name' => 'Some framework',
                'fields' => [$field1->get('id')],
            ];
            $framework1 = $traininggenerator->create_framework($data);
        } else {
            $framework1 = null;
        }

        $top0 = top::load($program0->id);
        $set0 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item0x1 = $top0->append_course($set0, $course1->id);
        if ($framework1) {
            $item0x2 = $top0->append_training($set0, $framework1->id);
        }

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['sequencetype' => 'allinorder', 'completiondelay' => 3]);
        $set1 = $top1->append_set($top1, ['fullname' => 'Another set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 3]);
        $item1x1 = $top1->append_course($set1, $course1->id, ['points' => 3]);
        if ($framework1) {
            $item1x2 = $top1->append_training($set1, $framework1->id, ['completiondelay' => 11]);
        }

        $rawprograms = \tool_muprog\local\export::export_programs('1=1', []);
        $data = (object)[
            'contextid' => 0,
            'archived' => 0,
            'delimiter_name' => 'comma',
            'encoding' => 'UTF-8',
        ];
        $file = \tool_muprog\local\export::export_csv($data);

        $dir = \make_request_directory();
        $packer = get_file_packer('application/zip');
        $packer->extract_to_pathname($file, $dir);
        $csvfiles = [
            "$dir/programs.csv",
            "$dir/programs_contents.csv",
            "$dir/programs_sources.csv",
        ];
        foreach ($csvfiles as $csvfile) {
            $this->assertFileExists($csvfile);
        }

        $result = \tool_muprog\local\upload::decode_csv_files($csvfiles, 'UTF-8');

        $this->assertEquals($rawprograms, $result);
    }
}
