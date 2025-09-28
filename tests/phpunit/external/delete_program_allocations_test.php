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

use tool_muprog\local\source\selfallocation;

/**
 * Tests for external source delete program allocation users.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\delete_program_allocations_test
 */
final class delete_program_allocations_test extends \advanced_testcase {
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

        $program1 = $generator->create_program(
            ['sources' => ['manual' => [], 'selfallocation' => []], 'publicaccess' => 1]
        );
        $source1m = $DB->get_record(
            'tool_muprog_source',
            ['programid' => $program1->id, 'type' => 'manual'],
            '*',
            MUST_EXIST
        );
        $source1s = $DB->get_record(
            'tool_muprog_source',
            ['programid' => $program1->id, 'type' => 'selfallocation'],
            '*',
            MUST_EXIST
        );
        $program2 = $generator->create_program(
            ['sources' => ['manual' => [], 'cohort' => ['cohortids' => [$cohort1->id]]], 'contextid' => $catcontext1->id]
        );
        $source2 = $DB->get_record(
            'tool_muprog_source',
            ['programid' => $program2->id, 'type' => 'manual'],
            '*',
            MUST_EXIST
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id, $user2->id]);
        $this->setUser($user3);
        selfallocation::signup($program1->id, $source1s->id);
        $this->setUser(null);
        \tool_muprog\local\source\manual::allocate_users($program2->id, $source2->id, [$user1->id, $user3->id]);
        \cohort_add_member($cohort1->id, $user4->id);

        $allocatorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:allocate', CAP_ALLOW, $allocatorroleid, $syscontext);
        role_assign($allocatorroleid, $user1->id, $syscontext->id);
        role_assign($allocatorroleid, $user2->id, $catcontext1->id);

        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user2->id, 'programid' => $program1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user4->id, 'programid' => $program2->id]));

        $this->setUser($user1);
        $result = \tool_muprog\external\delete_program_allocations::clean_returnvalue(
            \tool_muprog\external\delete_program_allocations::execute_returns(),
            \tool_muprog\external\delete_program_allocations::execute($program1->id, [$user1->id, $user3->id, $user5->id])
        );
        $this->assertSame([(int)$user1->id, (int)$user3->id], $result);
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user2->id, 'programid' => $program1->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user4->id, 'programid' => $program2->id]));

        $this->setUser($user2);
        $result = \tool_muprog\external\delete_program_allocations::clean_returnvalue(
            \tool_muprog\external\delete_program_allocations::execute_returns(),
            \tool_muprog\external\delete_program_allocations::execute($program2->id, [$user1->id])
        );
        $this->assertSame([(int)$user1->id], $result);
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program1->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user2->id, 'programid' => $program1->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program1->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user4->id, 'programid' => $program2->id]));

        $this->setUser($user2);
        try {
            \tool_muprog\external\delete_program_allocations::execute($program1->id, [$user2->id]);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user2->id, 'programid' => $program1->id]));

        $this->setUser($user2);
        try {
            \tool_muprog\external\delete_program_allocations::execute($program2->id, [$user2->id, $user4->id]);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
        }
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['userid' => $user1->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user3->id, 'programid' => $program2->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['userid' => $user4->id, 'programid' => $program2->id]));
    }
}
