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

namespace tool_muprog\phpunit\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use stdClass;
use tool_muprog\local\certificate;
use tool_muprog\privacy\provider;

/**
 * Privacy provider tests for tool_muprog.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Chris Tranel
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var stdClass A user who is not enrolled in any program. */
    protected $user0;

    /** @var stdClass A user who is only enrolled in program1. */
    protected $user1;

    /** @var stdClass A user who is only enrolled in program2. */
    protected $user2;

    /** @var stdClass A user who is enrolled in programs 1 and 2. */
    protected $user3;

    /** @var stdClass A test program. */
    protected $program1;

    /** @var stdClass A test program. */
    protected $program2;

    public function tearDown(): void {
        $this->user0 = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->program1 = null;
        $this->program2 = null;
        parent::tearDown();
    }

    /**
     * Init tests.
     */
    protected function set_instance_vars(): void {
        global $DB;

        $this->resetAfterTest();

        $syscontext = \context_system::instance();
        $coursecategorycontext = \context_coursecat::instance(1);
        $generator = $this->getDataGenerator();

        // Create users.
        $this->user0 = $generator->create_user();
        $this->user1 = $generator->create_user();
        $this->user2 = $generator->create_user();
        $this->user3 = $generator->create_user();

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $generator->get_plugin_generator('tool_muprog');

        // Set up and allocate users to programs.
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ];
        $this->program1 = $programgenerator->create_program($data);
        $source = $DB->get_record('tool_muprog_source', ['programid' => $this->program1->id, 'type' => 'manual']);
        \tool_muprog\local\source\manual::allocate_users($this->program1->id, $source->id, [$this->user1->id, $this->user3->id]);
        certificate::update_program_certificate([
            'id' => $this->program1->id,
            'programid' => $this->program1->id,
            'templateid' => 0,
            'expirydatetype' => 0,
        ]);
        certificate::issue($this->program1->id, $this->user1->id);

        $data = (object)[
            'fullname' => 'Another program',
            'idnumber' => 'AP1',
            'contextid' => $coursecategorycontext->id,
            'sources' => ['manual' => []],
        ];
        $this->program2 = $programgenerator->create_program($data);
        $source = $DB->get_record('tool_muprog_source', ['programid' => $this->program2->id, 'type' => 'manual']);
        \tool_muprog\local\source\manual::allocate_users($this->program2->id, $source->id, [$this->user2->id, $this->user3->id]);
    }

    /**
     * Check that a program context is returned if there is any user data for this user.
     */
    public function test_get_contexts_for_userid(): void {
        $this->set_instance_vars();
        $this->assertEmpty(provider::get_contexts_for_userid($this->user0->id));
        // Check that we only get back one context for user1.
        $contextlist = provider::get_contexts_for_userid($this->user1->id);
        $this->assertCount(1, $contextlist);
        // Check that the context is returned is the expected.
        $programcontext = \context::instance_by_id($this->program1->contextid);
        $this->assertEquals($programcontext->id, $contextlist->get_contextids()[0]);

        // Check that we get 2 contexts for user3.
        $contextlist = provider::get_contexts_for_userid($this->user3->id);
        $this->assertCount(2, $contextlist);
    }
    /**
     * Test that user data is exported correctly.
     */
    public function test_export_user_data(): void {
        $this->set_instance_vars();
        $program1context = \context::instance_by_id($this->program1->contextid);
        $program2context = \context::instance_by_id($this->program2->contextid);

        // Get contexts containing user data.
        $contextlist1 = provider::get_contexts_for_userid($this->user1->id);
        $this->assertEquals(1, $contextlist1->count());

        $approvedcontextlist1 = new approved_contextlist(
            $this->user1,
            'tool_muprog',
            $contextlist1->get_contextids()
        );

        // Export for the approved contexts.
        provider::export_user_data($approvedcontextlist1);

        $strallocation = get_string('programallocations', 'tool_muprog');

        // Verify we have content in program 1 for user1.
        $writer = writer::with_context($program1context);
        $programdata = $writer->get_data([$strallocation, $this->program1->fullname]);
        $this->assertNotEmpty($programdata);

        // Verify we have nothing in program 2 for user1.
        $writer = writer::with_context($program2context);
        $this->assertEmpty($writer->get_data([$strallocation, $this->program2->fullname]));
    }

    /**
     * Test deleting all user data for a specific context.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        $this->set_instance_vars();

        $program1context = \context::instance_by_id($this->program1->contextid);

        // Get all user allocations.
        $userallocations = $DB->get_records('tool_muprog_allocation', []);
        $this->assertCount(4, $userallocations);
        // Get all user enrolments match with program1.
        $sql = "SELECT pa.id, pa.userid
                  FROM {tool_muprog_program} p
                  JOIN {tool_muprog_allocation} pa ON pa.programid = p.id AND p.id = :programid";
        $userallocations = $DB->get_records_sql($sql, ['programid' => $this->program1->id]);
        $this->assertCount(2, $userallocations);
        $allocationids = [current($userallocations)->id, next($userallocations)->id];
        // Delete everything for the first program context.
        provider::delete_data_for_all_users_in_context($program1context);
        // Get all user allocations match with this context.
        $userallocations = $DB->get_records_sql($sql, ['programid' => $this->program1->id]);
        $this->assertCount(0, $userallocations);
        $certs = $DB->get_records_sql(
            "SELECT 1
                FROM {tool_muprog_cert_issue}
                WHERE allocationid IN(?, ?)",
            $allocationids
        );
        $this->assertCount(0, $certs);

        // Get all user allocations match with this context, check count of allocations from other contexts.
        $userallocations = $DB->get_records('tool_muprog_allocation', []);
        $this->assertCount(2, $userallocations);
    }
    /**
     * This should work identical to the above test.
     */
    public function test_delete_data_for_user(): void {
        global $DB;
        $this->set_instance_vars();

        $program1context = \context::instance_by_id($this->program1->contextid);
        $program2context = \context::instance_by_id($this->program2->contextid);
        // Get all user enrolments.
        $userenrolments = $DB->get_records('tool_muprog_allocation', []);
        $this->assertCount(4, $userenrolments);
        // Get all user enrolments match with user1.
        $userenrolments = $DB->get_records('tool_muprog_allocation', ['userid' => $this->user3->id]);
        $this->assertCount(2, $userenrolments);

        // Delete everything for the user3 in the context.
        $approvedlist = new approved_contextlist($this->user3, 'tool_muprog', [$program1context->id, $program2context->id]);
        provider::delete_data_for_user($approvedlist);
        // Get all user enrolments match with user3.
        $userenrolments = $DB->get_records('tool_muprog_allocation', ['userid' => $this->user3->id]);
        $this->assertCount(0, $userenrolments);
        // Check for tool_muprog_request with user3.
        $requests = $DB->get_records('tool_muprog_request', ['userid' => $this->user3->id]);
        $this->assertCount(0, $requests);

        // Get all user enrolments accounts.
        $userenrolments = $DB->get_records('tool_muprog_allocation', []);
        $this->assertCount(2, $userenrolments);
    }

    /**
     * Test that only users within a program context are fetched.
     */
    public function test_get_users_in_context(): void {
        global $DB;
        $component = 'tool_muprog';
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $usercontext = \context_user::instance($user->id);

        $data = (object)[
                'fullname' => 'Get User program',
                'idnumber' => 'GU1',
                'contextid' => \context_system::instance()->id,
                'sources' => ['manual' => []],
            ];
        $program = $this->getDataGenerator()->get_plugin_generator('tool_muprog')->create_program($data);

        $programcontext = \context::instance_by_id($program->contextid);

        $userlist1 = new \core_privacy\local\request\userlist($programcontext, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(0, $userlist1);

        // Allocate user to program.
        $source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual']);
        \tool_muprog\local\source\manual::allocate_users($program->id, $source->id, [$user->id]);

        // The list of users within the program context should contain user.
        provider::get_users_in_context($userlist1);
        $this->assertCount(1, $userlist1);

        $userids = $userlist1->get_userids();
        $this->assertContains((int)$user->id, $userids);

        // The list of users within the user context should be empty.
        $userlist2 = new \core_privacy\local\request\userlist($usercontext, $component);
        provider::get_users_in_context($userlist2);
        $this->assertCount(0, $userlist2);
    }

    /**
     * Test that data for users in approved userlist is deleted.
     */
    public function test_delete_data_for_users(): void {
        global $DB;
        $this->set_instance_vars();

        $component = 'tool_muprog';
        $program1context = \context::instance_by_id($this->program1->contextid);
        $program2context = \context::instance_by_id($this->program2->contextid);

        $userlist1 = new \core_privacy\local\request\userlist($program1context, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(2, $userlist1);

        $userlist2 = new \core_privacy\local\request\userlist($program2context, $component);
        provider::get_users_in_context($userlist2);
        $this->assertCount(2, $userlist2);

        // Convert $userlist1 into an approved_contextlist.
        $approvedlist1 = new approved_userlist($program1context, $component, $userlist1->get_userids());
        // Delete using delete_data_for_user.
        provider::delete_data_for_users($approvedlist1);
        // Re-fetch users in $program1context.
        $userlist1 = new \core_privacy\local\request\userlist($program1context, $component);
        provider::get_users_in_context($userlist1);
        // The user data in $program1context should be deleted.
        $this->assertCount(0, $userlist1);
        // Check for tool_muprog_request with user3.
        $requests = $DB->get_records('tool_muprog_request', ['userid' => $this->user3->id]);
        $this->assertCount(0, $requests);

        // Re-fetch users in $program2context.
        $userlist2 = new \core_privacy\local\request\userlist($program2context, $component);
        provider::get_users_in_context($userlist2);
        // The user data in $program2context should be still present.
        $this->assertCount(2, $userlist2);

        // Convert $userlist2 into an approved_contextlist in the system context.
        $approvedlist2 = new approved_userlist($program2context, $component, $userlist2->get_userids());
        // Delete using delete_data_for_user.
        provider::delete_data_for_users($approvedlist2);
        // Re-fetch users in $program1context.
        $userlist2 = new \core_privacy\local\request\userlist($program2context, $component);
        provider::get_users_in_context($userlist2);
        // The user data in systemcontext should not be deleted.
        $this->assertCount(0, $userlist2);
    }
}
