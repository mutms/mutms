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
 * External API for removing cohort from the list or cohorts that are synced with the program.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\source_cohort_delete_cohort_test
 */
final class source_cohort_delete_cohort_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        global $DB;
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'sources' => ['cohort' => ['cohortids' => [$cohort1->id, $cohort2->id, $cohort3->id]]],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $syscontext->id,
            'sources' => ['cohort' => ['cohortids' => [$cohort2->id, $cohort3->id]]],
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);

        $result = \tool_muprog\external\source_cohort_delete_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_delete_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_delete_cohort::execute($program1->id, $cohort2->id)
        );
        $this->assertCount(2, $result);
        $this->assertEquals($cohort1->id, $result[0]['id']);
        $this->assertEquals($cohort3->id, $result[1]['id']);

        $result = \tool_muprog\external\source_cohort_delete_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_delete_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_delete_cohort::execute($program1->id, $cohort2->id)
        );
        $this->assertCount(2, $result);

        $result = \tool_muprog\external\source_cohort_delete_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_delete_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_delete_cohort::execute($program1->id, -10)
        );
        $this->assertCount(2, $result);

        try {
            \tool_muprog\external\source_cohort_delete_cohort::execute($program2->id, $cohort1->id);
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
            \tool_muprog\external\source_cohort_delete_cohort::execute($program1->id, $cohort1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }

        $this->setAdminUser();

        $result = \tool_muprog\external\source_cohort_delete_cohort::clean_returnvalue(
            \tool_muprog\external\source_cohort_delete_cohort::execute_returns(),
            \tool_muprog\external\source_cohort_delete_cohort::execute($program2->id, $cohort2->id)
        );
        $this->assertCount(1, $result);
        $this->assertEquals($cohort3->id, $result[0]['id']);
    }
}
