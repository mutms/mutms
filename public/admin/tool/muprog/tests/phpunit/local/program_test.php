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

use tool_muprog\local\program;

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
 * @covers \tool_muprog\local\program
 */
final class program_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $program = program::create($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$syscontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
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
        ];

        $this->setCurrentTimeStart();
        $program = program::create($data);
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

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Yet another program',
            'idnumber' => 'SP3',
            'contextid' => $catcontext->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('1 Feb 2030 00:00 GMT')],
            'duedate' => ['type' => 'delay', 'delay' => 'P1D'],
            'enddate' => (object)['type' => 'delay', 'delay' => 'P2M'],
        ];

        $this->setCurrentTimeStart();
        $program = program::create($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame('{"type":"date","date":1896134400}', $program->startdatejson);
        $this->assertSame('{"type":"delay","delay":"P1D"}', $program->duedatejson);
        $this->assertSame('{"type":"delay","delay":"P2M"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);
    }

    public function test_update_general(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $oldprogram = program::create($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'publicaccess' => '1',
            'cohorts' => [$cohort1->id, $cohort2->id],
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_general($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame($data->description, $program->description);
        $this->assertSame($data->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('0', $program->publicaccess);
        $this->assertSame('0', $program->archived);
        $this->assertSame($data->creategroups, $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);

        $cohorts = $DB->get_records_menu('tool_muprog_cohort', ['programid' => $program->id], 'cohortid ASC', 'id, cohortid');
        $this->assertSame([], array_values($cohorts));

        $this->assertDebuggingNotCalled();
        $data = (object)[
            'id' => $oldprogram->id,
            'archived' => 1,
        ];
        $program = program::update_general($data);
        $this->assertDebuggingCalled('Use program::archive() and program::restore() to change archived flag');
        $this->assertSame('0', $program->archived);
    }

    public function test_archive(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
            'archived' => 0,
        ];

        $program = program::create($data);
        $this->assertSame('0', $program->archived);

        $program = program::archive($program->id);
        $this->assertSame('1', $program->archived);

        $program = program::archive($program->id);
        $this->assertSame('1', $program->archived);
    }

    public function test_restore(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
            'archived' => 1,
        ];

        $program = program::create($data);
        $this->assertSame('1', $program->archived);

        $program = program::restore($program->id);
        $this->assertSame('0', $program->archived);

        $program = program::restore($program->id);
        $this->assertSame('0', $program->archived);
    }

    public function test_update_visibility(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $oldprogram = program::create($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'publicaccess' => '1',
            'cohortids' => [$cohort1->id, $cohort2->id],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_visibility($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame($oldprogram->contextid, $program->contextid);
        $this->assertSame($oldprogram->fullname, $program->fullname);
        $this->assertSame($oldprogram->idnumber, $program->idnumber);
        $this->assertSame($oldprogram->description, $program->description);
        $this->assertSame($oldprogram->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('1', $program->publicaccess);
        $this->assertSame('0', $program->archived);
        $this->assertSame('0', $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);

        $cohorts = $DB->get_records_menu('tool_muprog_cohort', ['programid' => $program->id], 'cohortid ASC', 'id, cohortid');
        $this->assertSame($data->cohortids, array_values($cohorts));
    }

    public function test_update_allocation(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $oldprogram = program::create($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'publicaccess' => '1',
            'cohorts' => [$cohort1->id, $cohort2->id],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_allocation($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame($oldprogram->contextid, $program->contextid);
        $this->assertSame($oldprogram->fullname, $program->fullname);
        $this->assertSame($oldprogram->idnumber, $program->idnumber);
        $this->assertSame($oldprogram->description, $program->description);
        $this->assertSame($oldprogram->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame($oldprogram->publicaccess, $program->publicaccess);
        $this->assertSame($oldprogram->archived, $program->archived);
        $this->assertSame($oldprogram->creategroups, $program->creategroups);
        $this->assertSame($data->timeallocationstart, $program->timeallocationstart);
        $this->assertSame($data->timeallocationend, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);

        $cohorts = $DB->get_records_menu('tool_muprog_cohort', ['programid' => $program->id], 'cohortid ASC', 'id, cohortid');
        $this->assertSame([], array_values($cohorts));
    }

    public function test_import_allocation(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program1 = $generator->create_program($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'publicaccess' => '1',
            'cohorts' => [$cohort1->id, $cohort2->id],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
            'sources' => [
                'manual' => [],
                'approval' => [],
                'cohort' => ['cohortids' => [$cohort2->id]],
                'selfallocation' => [],
            ],
        ];
        $program2 = $generator->create_program($data);
        $data = (object)[
            'id' => $program2->id,
            'programstart_type' => 'date',
            'programstart_date' => time() + 60 * 60,
            'programdue_type' => 'date',
            'programdue_date' => time() + 60 * 60 * 3,
            'programend_type' => 'date',
            'programend_date' => time() + 60 * 60 * 6,
        ];
        $program2 = program::update_scheduling($data);
        $scohort2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'cohort'], '*', MUST_EXIST);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
        ];
        $program1x = program::import_allocation($data);
        $this->assertSame((array)$program1, (array)$program1x);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importallocationstart' => 1,
            'importallocationend' => 1,
        ];
        $program1x = program::import_allocation($data);
        $program1->timeallocationstart = $program2->timeallocationstart;
        $program1->timeallocationend = $program2->timeallocationend;
        $this->assertSame((array)$program1, (array)$program1x);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importprogramstart' => 1,
            'importprogramdue' => 1,
            'importprogramend' => 1,
        ];
        $program1x = program::import_allocation($data);
        $program1->startdatejson = $program2->startdatejson;
        $program1->duedatejson = $program2->duedatejson;
        $program1->enddatejson = $program2->enddatejson;
        $this->assertSame((array)$program1, (array)$program1x);

        $sources1 = $DB->get_records('tool_muprog_source', ['programid' => $program1->id]);
        $this->assertCount(0, $sources1);

        $sources2 = $DB->get_records('tool_muprog_source', ['programid' => $program2->id]);
        $this->assertCount(4, $sources2);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcemanual' => 1,
        ];
        $program1x = program::import_allocation($data);
        $sources1 = $DB->get_records('tool_muprog_source', ['programid' => $program1->id]);
        $this->assertCount(1, $sources1);
        $smanual1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcemanual' => 1,
            'importsourcecohort' => 1,
            'importsourceapproval' => 1,
            'importsourceselfallocation' => 1,
        ];
        $program1x = program::import_allocation($data);
        $sources1 = $DB->get_records('tool_muprog_source', ['programid' => $program1->id]);
        $this->assertCount(4, $sources1);
        $smanual1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $scohort1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'cohort'], '*', MUST_EXIST);
        $sapproval1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        $sselfallocation1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'selfallocation'], '*', MUST_EXIST);

        $cohorts = $DB->get_records('tool_muprog_src_cohort', ['sourceid' => $scohort1->id]);
        $this->assertCount(1, $cohorts);
        $cr = reset($cohorts);
        $this->assertSame($cohort2->id, $cr->cohortid);

        $DB->delete_records('tool_muprog_src_cohort', ['sourceid' => $scohort2->id]);
        $DB->insert_record('tool_muprog_src_cohort', (object)['sourceid' => $scohort2->id, 'cohortid' => $cohort1->id]);
        $DB->insert_record('tool_muprog_src_cohort', (object)['sourceid' => $scohort2->id, 'cohortid' => $cohort3->id]);
        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcecohort' => 1,
        ];
        $program1x = program::import_allocation($data);
        $cohorts = $DB->get_records('tool_muprog_src_cohort', ['sourceid' => $scohort1->id]);
        $this->assertCount(3, $cohorts);
    }

    public function test_get_program_startdate_types(): void {
        $types = program::get_program_startdate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('allocation', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_get_program_duedate_types(): void {
        $types = program::get_program_duedate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('notset', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_get_program_enddate_types(): void {
        $types = program::get_program_enddate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('notset', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_update_scheduling(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $oldprogram = program::create($data);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'allocation',
            'programdue_type' => 'notset',
            'programend_type' => 'notset',
        ];
        $program = program::update_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'allocation']), $program->startdatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'notset']), $program->duedatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'notset']), $program->enddatejson);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'date',
            'programstart_date' => time() + 60 * 60,
            'programdue_type' => 'date',
            'programdue_date' => time() + 60 * 60 * 3,
            'programend_type' => 'date',
            'programend_date' => time() + 60 * 60 * 6,
        ];
        $program = program::update_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'date', 'date' => $data->programstart_date]), $program->startdatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'date', 'date' => $data->programdue_date]), $program->duedatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'date', 'date' => $data->programend_date]), $program->enddatejson);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'delay',
            'programstart_delay' => ['type' => 'hours', 'value' => 3],
            'programdue_type' => 'delay',
            'programdue_delay' => ['type' => 'days', 'value' => 6],
            'programend_type' => 'delay',
            'programend_delay' => ['type' => 'months', 'value' => 2],
        ];
        $program = program::update_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'delay', 'delay' => 'PT3H']), $program->startdatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'delay', 'delay' => 'P6D']), $program->duedatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'delay', 'delay' => 'P2M']), $program->enddatejson);
    }

    public function test_delete(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::create($data);

        program::delete($program->id);
        $this->assertFalse($DB->record_exists('tool_muprog_program', ['id' => $program->id]));
    }

    public function test_load_content(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::create($data);

        $top = program::load_content($program->id);
        $this->assertInstanceOf(\tool_muprog\local\content\top::class, $top);
    }

    public function test_category_pre_delete(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $this->assertSame($category1->id, $category2->parent);

        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $generator->create_program(['contextid' => $catcontext2->id]);

        $this->assertSame((string)$catcontext1->id, $program1->contextid);
        $this->assertSame((string)$catcontext2->id, $program2->contextid);

        program::pre_course_category_delete($category2->get_db_record());
        $program2 = $DB->get_record('tool_muprog_program', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$catcontext1->id, $program2->contextid);

        program::pre_course_category_delete($category1->get_db_record());
        $program1 = $DB->get_record('tool_muprog_program', ['id' => $program1->id], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $program1->contextid);
        $program2 = $DB->get_record('tool_muprog_program', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $program2->contextid);
    }
}
