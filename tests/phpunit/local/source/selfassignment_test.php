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

namespace tool_mucertify\phpunit\local\source;

use tool_mucertify\local\certification;

/**
 * Self-assignment source test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\source\selfassignment
 */
final class selfassignment_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('selfassignment', \tool_mucertify\local\source\selfassignment::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        $certification = $generator->create_certification();

        $this->assertTrue(\tool_mucertify\local\source\selfassignment::is_new_allowed($certification));
        set_config('source_selfassignment_allownew', 0, 'tool_mucertify');
        $this->assertFalse(\tool_mucertify\local\source\selfassignment::is_new_allowed($certification));
    }

    public function test_can_user_request(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => [], 'selfassignment' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'selfassignment'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => [], 'selfassignment' => []]]);
        $source2m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'selfassignment'], '*', MUST_EXIST);

        $certification3 = $generator->create_certification(['sources' => ['manual' => [], 'selfassignment' => []], 'archived' => 1]);
        $source3m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source3a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification3->id, 'type' => 'selfassignment'], '*', MUST_EXIST);

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        cohort_add_member($cohort1->id, $user1->id);

        $this->assertTrue(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));

        // Must not be archived.

        $certification1 = certification::archive($certification1->id);
        $this->assertFalse(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));
        $certification1 = certification::restore($certification1->id);

        // Real user required.

        $this->assertTrue(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));

        $this->assertFalse(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $guest->id));

        $this->assertFalse(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, 0));

        // Must be visible.

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 1]);
        $this->assertTrue(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 0, 'cohortids' => [$cohort1->id]]);
        $this->assertTrue(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 0, 'cohortids' => []]);
        $this->assertFalse(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 1, 'cohortids' => [$cohort1->id]]);
        $this->assertTrue(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));

        // Assigned already.

        \tool_mucertify\local\source\manual::assign_users($certification1->id, $source1m->id, [$user1->id]);
        $this->assertFalse(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user1->id));

        // Max users.

        \tool_mucertify\local\source\manual::assign_users($certification1->id, $source1m->id, [$user3->id]);
        $this->assertTrue(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user2->id));

        $source1a = \tool_mucertify\local\source\selfassignment::update_source((object)[
            'certificationid' => $certification1->id,
            'type' => 'selfassignment',
            'enable' => 1,
            'selfassignment_maxusers' => 2,
        ]);
        $this->assertFalse(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user2->id));

        $source1a = \tool_mucertify\local\source\selfassignment::update_source((object)[
            'certificationid' => $certification1->id,
            'type' => 'selfassignment',
            'enable' => 1,
            'selfassignment_maxusers' => 3,
        ]);
        $this->assertTrue(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user2->id));

        // Disabled new assignments.

        $source1a = \tool_mucertify\local\source\selfassignment::update_source((object)[
            'certificationid' => $certification1->id,
            'type' => 'selfassignment',
            'enable' => 1,
            'selfassignment_allowsignup' => 0,
        ]);
        $this->assertFalse(\tool_mucertify\local\source\selfassignment::can_user_request($certification1, $source1a, $user2->id));
    }

    public function test_signup(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => [], 'selfassignment' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'selfassignment'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $assignment = \tool_mucertify\local\source\selfassignment::signup($certification1->id, $source1a->id);
        $this->assertSame($user1->id, $assignment->userid);
        $this->assertSame($certification1->id, $assignment->certificationid);
        $this->assertSame($source1a->id, $assignment->sourceid);

        $assignment2 = \tool_mucertify\local\source\selfassignment::signup($certification1->id, $source1a->id);
        $this->assertEquals($assignment, $assignment2);
    }
}
