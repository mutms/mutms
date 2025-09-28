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

namespace tool_muprog\phpunit\external;

/**
 * External API for get program list
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\get_programs
 */
final class get_programs_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $syscontext->id);
        role_assign($viewerroleid, $user2->id, $catcontext1->id);

        $program1 = $generator->create_program([
            'fullname' => 'hokus',
            'idnumber' => 'p1',
            'description' => 'some desc 1',
            'descriptionformat' => \FORMAT_MARKDOWN,
            'publicaccess' => 1,
            'archived' => 0,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
            'cohorts' => [$cohort1->id],
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'pokus',
            'idnumber' => 'p2',
            'description' => '<b>some desc 2</b>',
            'descriptionformat' => \FORMAT_HTML,
            'publicaccess' => 0,
            'archived' => 0,
            'contextid' => $catcontext1->id,
            'sources' => ['manual' => [], 'cohort' => []],
            'cohorts' => [$cohort1->id, $cohort2->id],
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog3',
            'idnumber' => 'p3',
            'publicaccess' => 1,
            'archived' => 1,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ]);

        $this->setUser($admin);
        $response = \tool_muprog\external\get_programs::execute([]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(3, $results);

        $result = $results[0];
        $this->assertIsArray($result);
        $result = (object)$result;
        $this->assertSame((int)$program1->id, $result->id);
        $this->assertSame((int)$program1->contextid, $result->contextid);
        $this->assertSame($program1->fullname, $result->fullname);
        $this->assertSame($program1->idnumber, $result->idnumber);
        $this->assertSame($program1->description, $result->description);
        $this->assertSame((int)$program1->descriptionformat, $result->descriptionformat);
        $this->assertSame('[]', $result->presentationjson);
        $this->assertSame(true, $result->publicaccess);
        $this->assertSame(false, $result->archived);
        $this->assertSame(false, $result->creategroups);
        $this->assertSame(null, $result->timeallocationstart);
        $this->assertSame(null, $result->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $result->startdatejson);
        $this->assertSame('{"type":"notset"}', $result->duedatejson);
        $this->assertSame('{"type":"notset"}', $result->enddatejson);
        $this->assertSame((int)$program1->timecreated, $result->timecreated);
        $this->assertSame(['manual'], $result->sources);
        $this->assertSame([], $result->cohortids);

        $result = $results[1];
        $this->assertIsArray($result);
        $result = (object)$result;
        $this->assertSame((int)$program2->id, $result->id);
        $this->assertSame((int)$catcontext1->id, $result->contextid);
        $this->assertSame($program2->fullname, $result->fullname);
        $this->assertSame($program2->idnumber, $result->idnumber);
        $this->assertSame($program2->description, $result->description);
        $this->assertSame((int)$program2->descriptionformat, $result->descriptionformat);
        $this->assertSame('[]', $result->presentationjson);
        $this->assertSame(false, $result->publicaccess);
        $this->assertSame(false, $result->archived);
        $this->assertSame(false, $result->creategroups);
        $this->assertSame(null, $result->timeallocationstart);
        $this->assertSame(null, $result->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $result->startdatejson);
        $this->assertSame('{"type":"notset"}', $result->duedatejson);
        $this->assertSame('{"type":"notset"}', $result->enddatejson);
        $this->assertSame((int)$program2->timecreated, $result->timecreated);
        $this->assertSame(['cohort', 'manual'], $result->sources);
        $this->assertSame([(int)$cohort1->id, (int)$cohort2->id], $result->cohortids);

        $result = $results[2];
        $this->assertIsArray($result);
        $result = (object)$result;
        $this->assertSame((int)$program3->id, $result->id);
        $this->assertSame((int)$program3->contextid, $result->contextid);
        $this->assertSame($program3->fullname, $result->fullname);
        $this->assertSame($program3->idnumber, $result->idnumber);
        $this->assertSame($program3->description, $result->description);
        $this->assertSame((int)$program3->descriptionformat, $result->descriptionformat);
        $this->assertSame('[]', $result->presentationjson);
        $this->assertSame(true, $result->publicaccess);
        $this->assertSame(true, $result->archived);
        $this->assertSame(false, $result->creategroups);
        $this->assertSame(null, $result->timeallocationstart);
        $this->assertSame(null, $result->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $result->startdatejson);
        $this->assertSame('{"type":"notset"}', $result->duedatejson);
        $this->assertSame('{"type":"notset"}', $result->enddatejson);
        $this->assertSame((int)$program3->timecreated, $result->timecreated);
        $this->assertSame(['manual'], $result->sources);
        $this->assertSame([], $result->cohortids);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'id', 'value' => $program1->id]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program1->id, $results[0]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'idnumber', 'value' => $program1->idnumber]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program1->id, $results[0]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'fullname', 'value' => $program1->fullname]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program1->id, $results[0]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'contextid', 'value' => $syscontext->id]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(2, $results);
        $this->assertEquals($program1->id, $results[0]['id']);
        $this->assertEquals($program3->id, $results[1]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'publicaccess', 'value' => 1]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(2, $results);
        $this->assertEquals($program1->id, $results[0]['id']);
        $this->assertEquals($program3->id, $results[1]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'archived', 'value' => 0]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(2, $results);
        $this->assertEquals($program1->id, $results[0]['id']);
        $this->assertEquals($program2->id, $results[1]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'id', 'value' => $program1->id], ['field' => 'publicaccess', 'value' => 1]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program1->id, $results[0]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'id', 'value' => $program1->id], ['field' => 'publicaccess', 'value' => true]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program1->id, $results[0]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'id', 'value' => $program1->id], ['field' => 'publicaccess', 'value' => 0]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(0, $results);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'id', 'value' => $program1->id], ['field' => 'publicaccess', 'value' => false]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(0, $results);

        $this->setUser($user1);
        $response = \tool_muprog\external\get_programs::execute([]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(3, $results);
        $this->assertEquals($program1->id, $results[0]['id']);
        $this->assertEquals($program2->id, $results[1]['id']);
        $this->assertEquals($program3->id, $results[2]['id']);

        $this->setUser($user2);
        $response = \tool_muprog\external\get_programs::execute([]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program2->id, $results[0]['id']);

        $this->setUser($user3);
        $response = \tool_muprog\external\get_programs::execute([]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(0, $results);

        $this->setUser($admin);
        try {
            \tool_muprog\external\get_programs::execute([['field' => 'arar', 'value' => 'hokus']]);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Invalid field name: arar)', $ex->getMessage());
        }
        try {
            \tool_muprog\external\get_programs::execute([['field' => 'id', 'value' => null]]);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Field value cannot be NULL: id)', $ex->getMessage());
        }
        try {
            \tool_muprog\external\get_programs::execute([['field' => 'id', 'value' => 1], ['field' => 'id', 'value' => 2]]);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Invalid duplicate field name: id)', $ex->getMessage());
        }
    }

    public function test_execute_tenants(): void {
        if (!\tool_muprog\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_coursecat::instance($tenant1->categoryid);

        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext2 = \context_coursecat::instance($tenant2->categoryid);
        $tenantsubcategory2 = $this->getDataGenerator()->create_category(['parent' => $tenant2->categoryid]);
        $tenantsubcontext2 = \context_coursecat::instance($tenantsubcategory2->id);

        $program0 = $generator->create_program([
            'fullname' => 'Prog 0',
            'sources' => ['manual' => []],
        ]);
        $program1 = $generator->create_program([
            'fullname' => 'Prog 1',
            'contextid' => $tenantcontext1->id,
            'sources' => ['manual' => []],
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'Prog 2',
            'publicaccess' => 1,
            'contextid' => $tenantcontext2->id,
            'sources' => ['manual' => []],
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog 3',
            'publicaccess' => 0,
            'contextid' => $tenantsubcontext2->id,
            'sources' => ['manual' => []],
        ]);

        $this->setAdminUser();

        $response = \tool_muprog\external\get_programs::execute([]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(4, $results);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'tenantid', 'value' => null]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program0->id, $results[0]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'tenantid', 'value' => $tenant1->id]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program1->id, $results[0]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'tenantid', 'value' => $tenant2->id]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(2, $results);
        $this->assertEquals($program2->id, $results[0]['id']);
        $this->assertEquals($program3->id, $results[1]['id']);

        $response = \tool_muprog\external\get_programs::execute([['field' => 'tenantid', 'value' => $tenant2->id], ['field' => 'publicaccess', 'value' => 1]]);
        $results = \tool_muprog\external\get_programs::clean_returnvalue(\tool_muprog\external\get_programs::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertEquals($program2->id, $results[0]['id']);
    }
}
