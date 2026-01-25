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

namespace tool_mucertify\phpunit\external;

use tool_mucertify\external\get_certifications;
use tool_mulib\local\mulib;

/**
 * Test get_certifications web service.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\external\get_certifications
 */
final class get_certifications_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $program1 = $programgenerator->create_program([
            'sources' => ['mucertify' => []],
        ]);
        $program2 = $programgenerator->create_program([
            'sources' => ['mucertify' => []],
        ]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user0->id, $syscontext->id);
        role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $certification1 = $generator->create_certification([
            'fullname' => 'First certification',
            'idnumber' => 'CT1',
            'contextid' => $syscontext->id,
            'sources' => 'manual',
            'publicaccess' => 1,
            'programid1' => $program1->id,
            'programid2' => $program2->id,
            'recertify' => 604800,
        ]);
        $certification2 = $generator->create_certification([
            'fullname' => 'Second certification',
            'idnumber' => 'CT2',
            'contextid' => $catcontext1->id,
            'sources' => 'manual',
            'publicaccess' => 0,
            'cohorts' => [$cohort1->id, $cohort2->id],
            'programid1' => $program2->id,
        ]);

        $this->setUser($user0);

        $response = get_certifications::execute([]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(2, $results);

        $result = (object)$results[0];
        $this->assertSame((int)$certification1->id, $result->id);
        $this->assertSame($certification1->fullname, $result->fullname);
        $this->assertSame($certification1->idnumber, $result->idnumber);
        $this->assertSame(true, $result->publicaccess);
        $this->assertSame(false, $result->archived);
        $this->assertSame((int)$program1->id, $result->programid1);
        $this->assertSame((int)$program2->id, $result->programid2);
        $this->assertSame(null, $result->templateid);
        $this->assertSame(604800, $result->recertify);
        $this->assertStringStartsWith('{', $result->periodsjson);
        $this->assertSame((int)$certification1->timecreated, $result->timecreated);
        $this->assertSame(['manual'], $result->sources);
        $this->assertSame([], $result->cohortids);

        $result = (object)$results[1];
        $this->assertSame((int)$certification2->id, $result->id);
        $this->assertSame(false, $result->publicaccess);
        $this->assertSame(false, $result->archived);
        $this->assertSame((int)$program2->id, $result->programid1);
        $this->assertSame(null, $result->programid2);
        $this->assertSame(null, $result->recertify);
        $this->assertStringStartsWith('{', $result->periodsjson);
        $this->assertSame(['manual'], $result->sources);
        $this->assertEquals([$cohort1->id, $cohort2->id], $result->cohortids);

        $this->setUser($user1);

        $response = get_certifications::execute([]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);

        $result = (object)$results[0];
        $this->assertSame((int)$certification2->id, $result->id);

        $this->setUser($user2);

        $response = get_certifications::execute([]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(0, $results);

        $this->setUser($user0);

        $response = get_certifications::execute([['field' => 'contextid', 'value' => $catcontext1->id]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification2->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'id', 'value' => $certification2->id]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification2->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'fullname', 'value' => $certification2->fullname]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification2->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'idnumber', 'value' => $certification2->idnumber]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification2->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'publicaccess', 'value' => 0]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification2->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'archived', 'value' => 1]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(0, $results);
    }

    public function test_execute_tenant(): void {
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenantcontext2 = \context_coursecat::instance($tenant2->categoryid);
        $syscontext = \context_system::instance();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program([
            'sources' => ['mucertify' => []],
        ]);
        $program2 = $programgenerator->create_program([
            'sources' => ['mucertify' => []],
        ]);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user0->id, $syscontext->id);
        role_assign($viewerroleid, $user1->id, $syscontext->id);

        $certification0 = $generator->create_certification([
            'contextid' => $syscontext->id,
        ]);
        $certification1 = $generator->create_certification([
            'contextid' => $tenantcontext1->id,
        ]);
        $certification2 = $generator->create_certification([
            'contextid' => $tenantcontext2->id,
        ]);

        $this->setUser($user0);

        $response = get_certifications::execute([]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(3, $results);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => null]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification0->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => 0]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification0->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => $tenant1->id]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification1->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => $tenant2->id]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification2->id, $results[0]['id']);

        $this->setUser($user1);

        $response = get_certifications::execute([]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(2, $results);
        $this->assertSame((int)$certification0->id, $results[0]['id']);
        $this->assertSame((int)$certification1->id, $results[1]['id']);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => null]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification0->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => 0]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification0->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => $tenant1->id]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(1, $results);
        $this->assertSame((int)$certification1->id, $results[0]['id']);

        $response = get_certifications::execute([['field' => 'tenantid', 'value' => $tenant2->id]]);
        $results = get_certifications::clean_returnvalue(get_certifications::execute_returns(), $response);
        $this->assertCount(0, $results);
    }
}
