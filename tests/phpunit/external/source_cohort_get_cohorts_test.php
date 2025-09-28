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
 * External API for getting cohorts that are synced with the program.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\source_cohort_get_cohorts
 */
final class source_cohort_get_cohorts_test extends \advanced_testcase {
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
        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'sources' => ['cohort' => ['cohortids' => [$cohort2->id, $cohort1->id]]],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $syscontext->id,
            'sources' => ['cohort' => ['cohortids' => [$cohort2->id]]],
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);

        $result = \tool_muprog\external\source_cohort_get_cohorts::clean_returnvalue(
            \tool_muprog\external\source_cohort_get_cohorts::execute_returns(),
            \tool_muprog\external\source_cohort_get_cohorts::execute($program1->id)
        );
        $this->assertCount(2, $result);
        $firstcohort = (object)$result[0];
        $this->assertSame((int)$cohort1->id, $firstcohort->id);
        $this->assertSame((int)$cohort1->contextid, $firstcohort->contextid);
        $this->assertSame($cohort1->name, $firstcohort->name);
        $this->assertSame($cohort1->idnumber, $firstcohort->idnumber);
        $secondcohort = (object)$result[1];
        $this->assertSame((int)$cohort2->id, $secondcohort->id);
        $this->assertSame((int)$cohort2->contextid, $secondcohort->contextid);
        $this->assertSame($cohort2->name, $secondcohort->name);
        $this->assertSame($cohort2->idnumber, $secondcohort->idnumber);

        try {
            \tool_muprog\external\source_cohort_get_cohorts::execute($program2->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (View programs management).',
                $ex->getMessage()
            );
        }

        try {
            \tool_muprog\external\source_cohort_get_cohorts::execute(-10);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $ex);
        }

        $this->setUser($user2);

        try {
            \tool_muprog\external\source_cohort_get_cohorts::execute($program2->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (View programs management).',
                $ex->getMessage()
            );
        }

        $this->setAdminUser();
        $result = \tool_muprog\external\source_cohort_get_cohorts::clean_returnvalue(
            \tool_muprog\external\source_cohort_get_cohorts::execute_returns(),
            \tool_muprog\external\source_cohort_get_cohorts::execute($program2->id)
        );
        $this->assertCount(1, $result);
        $firstcohort = (object)$result[0];
        $this->assertEquals($cohort2->id, $firstcohort->id);
    }
}
