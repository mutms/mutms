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

namespace tool_muprog\phpunit\local\source;

use tool_muprog\local\program;

/**
 * Manual allocation source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\selfallocation
 */
final class selfallocation_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('selfallocation', \tool_muprog\local\source\selfallocation::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->assertTrue(\tool_muprog\local\source\selfallocation::is_new_allowed($program));
        \set_config('source_selfallocation_allownew', 0, 'tool_muprog');
        $this->assertFalse(\tool_muprog\local\source\selfallocation::is_new_allowed($program));
    }

    public function test_can_user_request(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'selfallocation' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'selfallocation'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'selfallocation' => []]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'selfallocation'], '*', MUST_EXIST);

        $program3 = $generator->create_program(['sources' => ['manual' => [], 'selfallocation' => []], 'archived' => 1]);
        $source3m = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source3a = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'selfallocation'], '*', MUST_EXIST);

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        \cohort_add_member($cohort1->id, $user1->id);

        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        // Must not be archived.

        $program1 = program::archive($program1->id);
        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));
        $program1 = program::restore($program1->id);

        // Real user required.

        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $guest->id));

        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, 0));

        // Allocation start-end observed.

        $this->setUser($user1);
        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => time() + 100, 'timeallocationend' => null]);
        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => null, 'timeallocationend' => time() - 100]);
        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => time() - 100, 'timeallocationend' => time() + 100]);
        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => null, 'timeallocationend' => null]);

        // Must be visible.

        $program1 = program::update_visibility((object)['id' => $program1->id,
            'publicaccess' => 1]);
        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_visibility((object)['id' => $program1->id,
            'publicaccess' => 0, 'cohortids' => [$cohort1->id]]);
        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_visibility((object)['id' => $program1->id,
            'publicaccess' => 0, 'cohortids' => []]);
        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_visibility((object)['id' => $program1->id,
            'publicaccess' => 1, 'cohortids' => [$cohort1->id]]);
        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        // Allocated already.

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id]);
        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user1->id));

        // Max users.

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user3->id]);
        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user2->id));

        $source1a = \tool_muprog\local\source\selfallocation::update_source((object)[
            'programid' => $program1->id,
            'type' => 'selfallocation',
            'enable' => 1,
            'selfallocation_maxusers' => 2,
        ]);
        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user2->id));

        $source1a = \tool_muprog\local\source\selfallocation::update_source((object)[
            'programid' => $program1->id,
            'type' => 'selfallocation',
            'enable' => 1,
            'selfallocation_maxusers' => 3,
        ]);
        $this->assertTrue(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user2->id));

        // Disabled new allocations.

        $source1a = \tool_muprog\local\source\selfallocation::update_source((object)[
            'programid' => $program1->id,
            'type' => 'selfallocation',
            'enable' => 1,
            'selfallocation_allowsignup' => 0,
        ]);
        $this->assertFalse(\tool_muprog\local\source\selfallocation::can_user_request($program1, $source1a, $user2->id));
    }

    public function test_signup(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'selfallocation' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'selfallocation'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $allocation = \tool_muprog\local\source\selfallocation::signup($program1->id, $source1a->id);
        $this->assertSame($user1->id, $allocation->userid);
        $this->assertSame($program1->id, $allocation->programid);
        $this->assertSame($source1a->id, $allocation->sourceid);

        $allocation2 = \tool_muprog\local\source\selfallocation::signup($program1->id, $source1a->id);
        $this->assertEquals($allocation, $allocation2);
    }

    public function test_is_import_allowed(): void {

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['selfallocation' => []]]);
        $program2 = $generator->create_program(['sources' => []]);
        $program3 = $generator->create_program(['sources' => []]);
        $program4 = $generator->create_program(['sources' => ['selfallocation' => []]]);

        \set_config('source_selfallocation_allownew', '1', 'tool_muprog');

        $this->assertTrue(\tool_muprog\local\source\selfallocation::is_import_allowed($program1, $program3));
        $this->assertFalse(\tool_muprog\local\source\selfallocation::is_import_allowed($program2, $program3));
        $this->assertTrue(\tool_muprog\local\source\selfallocation::is_import_allowed($program1, $program4));
        $this->assertFalse(\tool_muprog\local\source\selfallocation::is_import_allowed($program2, $program4));

        \set_config('source_selfallocation_allownew', '0', 'tool_muprog');

        $this->assertFalse(\tool_muprog\local\source\selfallocation::is_import_allowed($program1, $program3));
        $this->assertFalse(\tool_muprog\local\source\selfallocation::is_import_allowed($program2, $program3));
        $this->assertTrue(\tool_muprog\local\source\selfallocation::is_import_allowed($program1, $program4));
        $this->assertFalse(\tool_muprog\local\source\selfallocation::is_import_allowed($program2, $program4));
    }

    public function test_import_source_data(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['selfallocation' => []]]);
        $program2 = $generator->create_program(['sources' => ['selfallocation' => []]]);
        $program3 = $generator->create_program(['sources' => []]);

        $source1 = \tool_muprog\local\source\selfallocation::update_source((object)[
            'programid' => $program1->id,
            'type' => 'selfallocation',
            'enable' => 1,
            'selfallocation_maxusers' => 2,
        ]);
        $source2 = \tool_muprog\local\source\selfallocation::update_source((object)[
            'programid' => $program2->id,
            'type' => 'selfallocation',
            'enable' => 1,
            'selfallocation_maxusers' => 10,
            'selfallocation_key' => 'somesecret',
        ]);

        $source3 = \tool_muprog\local\source\selfallocation::import_source_data($program1->id, $program3->id);
        $this->assertSame($program3->id, $source3->programid);
        $this->assertSame('selfallocation', $source3->type);
        $this->assertSame($source1->datajson, $source3->datajson);
        $this->assertSame($source1->auxint1, $source3->auxint1);
        $this->assertSame($source1->auxint2, $source3->auxint2);
        $this->assertSame($source1->auxint3, $source3->auxint3);

        $source3 = \tool_muprog\local\source\selfallocation::import_source_data($program2->id, $program3->id);
        $this->assertSame($program3->id, $source3->programid);
        $this->assertSame('selfallocation', $source3->type);
        $this->assertSame($source2->datajson, $source3->datajson);
        $this->assertSame($source2->auxint1, $source3->auxint1);
        $this->assertSame($source2->auxint2, $source3->auxint2);
        $this->assertSame($source2->auxint3, $source3->auxint3);
    }
}
