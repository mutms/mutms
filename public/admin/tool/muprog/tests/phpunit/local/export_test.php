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
 * @covers \tool_muprog\local\export
 */
final class export_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_format_date(): void {
        $now = time();
        $formatted = \tool_muprog\local\export::format_date($now);
        $this->assertSame($now, strtotime($formatted));

        $formatted = \tool_muprog\local\export::format_date((string)$now);
        $this->assertSame($now, strtotime($formatted));

        $this->assertNull(\tool_muprog\local\export::format_date(0));
        $this->assertNull(\tool_muprog\local\export::format_date(null));
    }

    public function test_export(): void {
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

        $programs = \tool_muprog\local\export::export_programs('id = ?', [$program0->id]);
        $this->assertDebuggingNotCalled();
        $this->assertCount(1, $programs);
        $this->assertSame($program0->idnumber, $programs[0]->idnumber);
        $this->assertSame($program0->fullname, $programs[0]->fullname);
        $this->assertSame('', $programs[0]->category);
        $this->assertSame($program0->description, $programs[0]->description);
        $this->assertSame((int)$program0->descriptionformat, $programs[0]->descriptionformat);
        $this->assertSame((int)$program0->publicaccess, $programs[0]->publicaccess);
        $this->assertSame((int)$program0->creategroups, $programs[0]->creategroups);
        $this->assertSame(null, $programs[0]->allocationstart);
        $this->assertSame(null, $programs[0]->allocationend);
        $this->assertSame(['type' => 'allocation'], (array)$programs[0]->startdate);
        $this->assertSame(['type' => 'notset'], (array)$programs[0]->duedate);
        $this->assertSame(['type' => 'notset'], (array)$programs[0]->enddate);
        $this->assertSame(
            ['itemtype' => 'set', 'completiondelay' => 0, 'sequencetype' => 'allinanyorder', 'items' => []],
            (array)$programs[0]->contents
        );
        $this->assertSame([], $programs[0]->sources);

        $programs = \tool_muprog\local\export::export_programs('id = ?', [$program1->id]);
        $this->assertDebuggingNotCalled();
        $this->assertCount(1, $programs);
        $this->assertSame('2024-08-15T22:20:01+08:00', $programs[0]->allocationstart);
        $this->assertSame('2030-01-15T23:52:02+08:00', $programs[0]->allocationend);
        $this->assertEquals([
            (object)['sourcetype' => 'approval', 'data' => (object)['allowrequest' => 1]],
            (object)['sourcetype' => 'manual'],
            (object)['sourcetype' => 'selfallocation', 'data' => (object)['allowsignup' => 1, 'maxusers' => null, 'key' => null]],
        ], $programs[0]->sources);

        $programs = \tool_muprog\local\export::export_programs('id = ?', [$program2->id]);
        $this->assertDebuggingNotCalled();
        $this->assertCount(1, $programs);
        $this->assertSame(['type' => 'date', 'date' => '2024-01-02T22:20:01+08:00'], (array)$programs[0]->startdate);
        $this->assertSame(['type' => 'delay', 'delay' => 'P20D'], (array)$programs[0]->duedate);
        $this->assertSame(['type' => 'delay', 'delay' => 'P2M'], (array)$programs[0]->enddate);
        $this->assertEquals([
            (object)['sourcetype' => 'approval', 'data' => (object)['allowrequest' => 0]],
            (object)['sourcetype' => 'manual'],
            (object)['sourcetype' => 'selfallocation', 'data' => (object)['allowsignup' => 1, 'maxusers' => 10, 'key' => 'abc']],
        ], $programs[0]->sources);

        // Add some training.
        if (!\tool_muprog\local\util::is_mutrain_available()) {
            return;
        }

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
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
            'idnumber' => 'fid1',
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);

        $top0 = top::load($program0->id);
        $set1 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item1x1 = $top0->append_course($set1, $course1->id);
        $item1x2 = $top0->append_training($set1, $framework1->id);

        $programs = \tool_muprog\local\export::export_programs('id = ?', [$program0->id]);
        $this->assertDebuggingNotCalled();
        $this->assertCount(1, $programs);

        $this->assertEquals((object)[
            'itemtype' => 'set', 'completiondelay' => 0, 'sequencetype' => 'allinanyorder', 'items' => [
                (object)[
                    'itemtype' => 'set', 'completiondelay' => 0, 'sequencetype' => 'atleast', 'items' => [
                        (object)[
                            'itemtype' => 'course', 'reference' => $course1->shortname, 'points' => 1, 'completiondelay' => 0,
                        ],
                        (object)[
                            'itemtype' => 'training', 'reference' => $framework1->idnumber, 'points' => 1, 'completiondelay' => 0,
                        ],
                    ],
                    'points' => 1, 'setname' => 'Optional set', 'minprerequisites' => 2,
                ],
            ],
        ], $programs[0]->contents);
    }

    public function test_export_json(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

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

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $top0 = top::load($program0->id);
        $set1 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item1x1 = $top0->append_course($set1, $course1->id);
        if ($framework1) {
            $item1x2 = $top0->append_training($set1, $framework1->id);
        }

        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
        ]);

        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
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

        $data = (object)[
            'programids' => [$program0->id, $program1->id, $program2->id],
        ];
        $file = \tool_muprog\local\export::export_json($data);
        $this->assertTrue(file_exists($file));
        $this->assertStringEndsWith('.zip', $file);

        $data = (object)[
            'contextid' => $catcontext1->id,
            'archived' => 0,
        ];
        $file = \tool_muprog\local\export::export_json($data);
        $this->assertTrue(file_exists($file));
        $this->assertStringEndsWith('.zip', $file);
    }

    public function test_export_csv(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

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

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $top0 = top::load($program0->id);
        $set1 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item1x1 = $top0->append_course($set1, $course1->id);
        if ($framework1) {
            $item1x2 = $top0->append_training($set1, $framework1->id);
        }

        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
        ]);

        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
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

        $data = (object)[
            'programids' => [$program0->id, $program1->id, $program2->id],
            'delimiter_name' => 'comma',
            'encoding' => 'UTF-8',
        ];
        $file = \tool_muprog\local\export::export_csv($data);
        $this->assertTrue(file_exists($file));
        $this->assertStringEndsWith('.zip', $file);

        $data = (object)[
            'contextid' => $catcontext1->id,
            'archived' => 0,
            'delimiter_name' => 'semicolon',
            'encoding' => 'ISO-8859-1',
        ];
        $file = \tool_muprog\local\export::export_csv($data);
        $this->assertTrue(file_exists($file));
        $this->assertStringEndsWith('.zip', $file);
    }
}
