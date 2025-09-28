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
use tool_mucertify\local\source\approval;

/**
 * Approval assignment source test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\source\approval
 */
final class approval_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('approval', approval::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        $certification = $generator->create_certification();

        $this->assertTrue(approval::is_new_allowed($certification));
    }

    public function test_can_user_request(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'approval'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $certification3 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []], 'archived' => 1]);
        $source3m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source3a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification3->id, 'type' => 'approval'], '*', MUST_EXIST);

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        cohort_add_member($cohort1->id, $user1->id);

        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user1->id));

        // Must not be archived.

        $certification1 = certification::archive($certification1->id);
        $this->assertFalse(approval::can_user_request($certification1, $source1a, $user1->id));
        $certification1 = certification::restore($certification1->id);

        // Real user required.

        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user1->id));

        $this->assertFalse(approval::can_user_request($certification1, $source1a, $guest->id));

        $this->assertFalse(approval::can_user_request($certification1, $source1a, 0));

        // Must be visible.

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 1]);
        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user1->id));

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 0, 'cohortids' => [$cohort1->id]]);
        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user1->id));

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 0, 'cohortids' => []]);
        $this->assertFalse(approval::can_user_request($certification1, $source1a, $user1->id));

        $certification1 = certification::update_visibility((object)['id' => $certification1->id,
            'publicaccess' => 1, 'cohortids' => [$cohort1->id]]);
        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user1->id));

        // Assigned already.

        \tool_mucertify\local\source\manual::assign_users($certification1->id, $source1m->id, [$user1->id]);
        $this->assertFalse(approval::can_user_request($certification1, $source1a, $user1->id));

        // Not rejected or pending.

        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user2->id));
        $this->setUser($user2);

        $request = approval::request($certification1->id, $source1a->id);
        $this->assertFalse(approval::can_user_request($certification1, $source1a, $user2->id));

        approval::reject_request($request->id, 'oh well');
        $this->assertFalse(approval::can_user_request($certification1, $source1a, $user2->id));

        approval::delete_request($request->id);
        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user2->id));

        // Disabled requests.

        $source1a = approval::update_source((object)[
            'certificationid' => $certification1->id,
            'type' => 'approval',
            'enable' => 1,
            'approval_allowrequest' => 0,
        ]);
        $this->assertFalse(approval::can_user_request($certification1, $source1a, $user2->id));
    }

    public function test_request(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'approval'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $request = approval::request($certification1->id, $source1a->id);
        $this->assertSame($source1a->id, $request->sourceid);
        $this->assertSame($user1->id, $request->userid);

        $request = approval::request($certification1->id, $source1a->id);
        $this->assertNull($request);
    }

    public function test_approve_request(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'approval'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $request = approval::request($certification1->id, $source1a->id);

        $this->setUser($user2);
        $assignment = approval::approve_request($request->id);
        $this->assertSame($certification1->id, $assignment->certificationid);
        $this->assertSame($source1a->id, $assignment->sourceid);
        $this->assertSame($user1->id, $assignment->userid);
        $this->assertFalse($DB->record_exists('tool_mucertify_request', ['sourceid' => $source1a->id, 'userid' => $user1->id]));
    }

    public function test_reject_request(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'approval'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $request = approval::request($certification1->id, $source1a->id);

        $this->setUser($user2);
        $this->setCurrentTimeStart();
        approval::reject_request($request->id, 'sorry mate');
        $request = $DB->get_record('tool_mucertify_request', ['sourceid' => $source1a->id, 'userid' => $user1->id]);
        $this->assertSame($source1a->id, $request->sourceid);
        $this->assertSame($user1->id, $request->userid);
        $this->assertTimeCurrent($request->timerejected);
        $this->assertFalse(approval::can_user_request($certification1, $source1a, $user1->id));
    }

    public function test_delete_request(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []], 'publicaccess' => 1]);
        $source1m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification1->id, 'type' => 'approval'], '*', MUST_EXIST);

        $certification2 = $generator->create_certification(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $request = approval::request($certification1->id, $source1a->id);

        $this->setUser($user2);
        approval::delete_request($request->id);
        $this->assertTrue(approval::can_user_request($certification1, $source1a, $user1->id));
    }

    public function test_is_new_allowed(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        $certification = $generator->create_certification();

        $this->assertTrue(approval::is_new_allowed($certification));
        set_config('source_approval_allownew', 0, 'tool_mucertify');
        $this->assertFalse(approval::is_new_allowed($certification));
    }
}
