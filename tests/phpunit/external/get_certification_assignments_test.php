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

use tool_mucertify\external\get_certification_assignments;
use tool_mulib\local\mulib;

/**
 * Test get_certification_assignments web service.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\external\get_certification_assignments
 */
final class get_certification_assignments_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        global $DB;

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
        $user3 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user0->id, $catcontext1->id);

        $certification1 = $generator->create_certification([
            'fullname' => 'First certification',
            'idnumber' => 'CT1',
            'contextid' => $catcontext1->id,
            'sources' => 'manual',
            'publicaccess' => 1,
            'programid1' => $program1->id,
            'programid2' => $program2->id,
            'recertify' => 604800,
        ]);
        $certification2 = $generator->create_certification([
            'fullname' => 'Second certification',
            'contextid' => $syscontext->id,
            'idnumber' => 'CT2',
            'sources' => 'manual',
            'publicaccess' => 0,
            'programid1' => $program2->id,
        ]);

        $now = time();

        $assignment1 = $generator->create_certification_assignment([
            'certificationid' => $certification1->id,
            'userid' => $user1->id,
            'timecreated' => $now,
        ]);

        $assignment2 = $generator->create_certification_assignment([
            'certificationid' => $certification1->id,
            'userid' => $user2->id,
            'timecreated' => (string)($now - DAYSECS),
            'timecertifiedtemp' => (string)($now + WEEKSECS),
        ]);
        $period2 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user2->id]
        );
        $assignment2 = \tool_mucertify\local\source\base::assignment_archive($assignment2->id);

        $generator->create_certification_assignment([
            'certificationid' => $certification2->id,
            'userid' => $user1->id,
            'timecreated' => (string)($now - HOURSECS),
        ]);

        $this->setUser($user0);

        $response = get_certification_assignments::execute($certification1->id, []);
        $results = get_certification_assignments::clean_returnvalue(get_certification_assignments::execute_returns(), $response);
        $this->assertCount(2, $results);

        $response = get_certification_assignments::execute($certification1->id, null);
        $results = get_certification_assignments::clean_returnvalue(get_certification_assignments::execute_returns(), $response);
        $this->assertCount(2, $results);

        $result = (object)$results[0];
        $this->assertSame((int)$certification1->id, $result->certificationid);
        $this->assertSame((int)$user1->id, $result->userid);
        $this->assertSame('manual', $result->sourcetype);
        $this->assertSame(false, $result->archived);
        $this->assertSame(null, $result->timecertifiedtemp);
        $this->assertSame("[]", $result->evidencejson);
        $this->assertSame(null, $result->timecertifiedfrom);
        $this->assertSame(null, $result->timecertifieduntil);
        $this->assertSame($now, $result->timecreated);
        $this->assertSame(false, $result->deletepossible);
        $this->assertSame(true, $result->archivepossible);
        $this->assertSame(false, $result->restorepossible);

        $result = (object)$results[1];
        $this->assertSame((int)$certification1->id, $result->certificationid);
        $this->assertSame((int)$user2->id, $result->userid);
        $this->assertSame('manual', $result->sourcetype);
        $this->assertSame(true, $result->archived);
        $this->assertSame(($now + WEEKSECS), $result->timecertifiedtemp);
        $this->assertSame("[]", $result->evidencejson);
        $this->assertSame($result->timecreated, $result->timecertifiedfrom);
        $this->assertSame(null, $result->timecertifieduntil);
        $this->assertSame($now - DAYSECS, $result->timecreated);
        $this->assertSame(true, $result->deletepossible);
        $this->assertSame(false, $result->archivepossible);
        $this->assertSame(true, $result->restorepossible);

        $response = get_certification_assignments::execute($certification1->id, [$user1->id]);
        $results = get_certification_assignments::clean_returnvalue(get_certification_assignments::execute_returns(), $response);
        $this->assertCount(1, $results);
        $result = (object)$results[0];
        $this->assertSame((int)$certification1->id, $result->certificationid);
        $this->assertSame((int)$user1->id, $result->userid);

        try {
            get_certification_assignments::execute($certification2->id, null);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (View certification management).',
                $ex->getMessage()
            );
        }

        $this->setUser($user1);

        try {
            get_certification_assignments::execute($certification1->id, null);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (View certification management).',
                $ex->getMessage()
            );
        }
    }
}
