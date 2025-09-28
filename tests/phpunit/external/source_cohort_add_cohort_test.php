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
 * External API for adding cohort to the list or cohorts that are synced with the program.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\source_cohort_add_cohort
 */
final class source_cohort_add_cohort_test extends \advanced_testcase {
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
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(['contextid' => $catcontext1->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['contextid' => $catcontext2->id]);
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'sources' => ['cohort' => []],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $syscontext->id,
            'sources' => ['cohort' => []],
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $catcontext1->id);

        $cohortroleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $cohortroleid, $syscontext);
        role_assign($cohortroleid, $user1->id, $catcontext1->id);
        role_assign($cohortroleid, $user1->id, $catcontext2->id);

        $this->setUser($user1);

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort1->id)
        );
        $this->assertCount(1, $result);
        $this->assertEquals($cohort1->id, $result[0]['id']);

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort2->id)
        );
        $this->assertCount(2, $result);
        $this->assertEquals($cohort1->id, $result[0]['id']);
        $this->assertEquals($cohort2->id, $result[1]['id']);

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort2->id)
        );
        $this->assertCount(2, $result);
        $this->assertEquals($cohort1->id, $result[0]['id']);
        $this->assertEquals($cohort2->id, $result[1]['id']);

        try {
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort3->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (View site-wide cohorts).',
                $ex->getMessage()
            );
        }

        try {
            \tool_muprog\external\source_cohort_add_cohort::execute($program2->id, $cohort1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }

        $this->setUser($user2);

        try {
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort3->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }
    }

    public function test_execute_tenants(): void {
        global $DB;
        if (!\tool_muprog\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        \tool_mutenancy\local\tenancy::activate();

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext2 = \context_coursecat::instance($tenant2->categoryid);
        $program0 = $generator->create_program([
            'fullname' => 'Prog 0',
            'sources' => ['cohort' => []],
        ]);
        $program1 = $generator->create_program([
            'fullname' => 'Prog 1',
            'contextid' => $tenantcontext1->id,
            'sources' => ['cohort' => []],
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'Prog 2',
            'contextid' => $tenantcontext2->id,
            'sources' => ['cohort' => []],
        ]);

        $cohort0 = $this->getDataGenerator()->create_cohort(['contextid' => $syscontext->id]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcontext1->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['contextid' => $tenantcontext2->id]);

        $this->setAdminUser();

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program0->id, $cohort0->id)
        );
        $this->assertCount(1, $result);
        $this->assertEquals($cohort0->id, $result[0]['id']);

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program0->id, $cohort1->id)
        );
        $this->assertCount(2, $result);
        $this->assertEquals($cohort0->id, $result[0]['id']);
        $this->assertEquals($cohort1->id, $result[1]['id']);

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program0->id, $cohort2->id)
        );
        $this->assertCount(3, $result);
        $this->assertEquals($cohort0->id, $result[0]['id']);
        $this->assertEquals($cohort1->id, $result[1]['id']);
        $this->assertEquals($cohort2->id, $result[2]['id']);

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort0->id)
        );
        $this->assertCount(1, $result);
        $this->assertEquals($cohort0->id, $result[0]['id']);

        $result = \tool_muprog\external\source_cohort_add_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_add_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort1->id)
        );
        $this->assertCount(2, $result);
        $this->assertEquals($cohort0->id, $result[0]['id']);
        $this->assertEquals($cohort1->id, $result[1]['id']);

        try {
            \tool_muprog\external\source_cohort_add_cohort::execute($program1->id, $cohort2->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Tenant mismatch', $ex->debuginfo);
        }
    }
}
