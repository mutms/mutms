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

namespace tool_murelation\phpunit\local;

use tool_murelation\local\framework;
use tool_murelation\local\subordinate;
use tool_murelation\local\supervisor;
use core\exception\coding_exception;
use core\exception\invalid_parameter_exception;

/**
 * Subordinate class tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\local\subordinate
 */
final class subordinate_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [],
        ]);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);

        $this->setCurrentTimeStart();
        $subordinate1 = subordinate::create((object)[
            'supervisorid' => $supervisor1->id,
            'userid' => $user2->id,
        ]);
        $this->assertSame($supervisor1->id, $subordinate1->supervisorid);
        $this->assertSame($user2->id, $subordinate1->userid);
        $this->assertSame($framework1->id, $subordinate1->frameworkid);
        $this->assertSame(null, $subordinate1->teamposition);
        $this->assertTimeCurrent($subordinate1->timecreated);
        $this->setCurrentTimeStart();

        $subordinate1b = subordinate::create((object)[
            'supervisorid' => $supervisor1->id,
            'userid' => $user3->id,
            'teamposition' => 'somebody',
        ]);
        $this->assertSame($supervisor1->id, $subordinate1b->supervisorid);
        $this->assertSame($user3->id, $subordinate1b->userid);
        $this->assertSame($framework1->id, $subordinate1b->frameworkid);
        $this->assertSame('somebody', $subordinate1b->teamposition);

        try {
            subordinate::create((object)[
                'supervisorid' => $supervisor2->id,
                'userid' => $user3->id,
            ]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (subordinate limit was already reached)', $ex->getMessage());
        }
    }

    public function test_update(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $subordinate1 = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $subordinate2 = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        $subordinate1 = subordinate::update((object)[
            'id' => $subordinate1->id,
            'teamposition' => 'abc',
        ]);
        $subordinate1b = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user3->id], '*', MUST_EXIST);
        $this->assertSame('abc', $subordinate1->teamposition);
        $this->assertSame(null, $subordinate1b->teamposition);

        $subordinate1 = subordinate::update((object)[
            'id' => $subordinate1->id,
            'teamposition' => '',
        ]);
        $this->assertSame(null, $subordinate1->teamposition);

        $subordinate2 = subordinate::update((object)[
            'id' => $subordinate2->id,
            'teamposition' => 'def',
        ]);
        $this->assertSame(null, $subordinate2->teamposition);
    }

    public function test_delete(): void {
        global $DB;
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $subordinate1 = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $subordinate1b = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user3->id], '*', MUST_EXIST);

        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $subordinate2 = $DB->get_record('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id, 'userid' => $user2->id], '*', MUST_EXIST);

        subordinate::delete($subordinate1->id);
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user3->id]));

        try {
            subordinate::delete($subordinate2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: delete supervisor instead of subordinate in Supervisors mode', $ex->getMessage());
        }
    }

    public function test_user_deleted(): void {
        global $DB;

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $supervisor1b = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 2',
            'userid' => null,
            'subuserids' => [$user4->id, $user5->id],
        ]);

        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2b = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user3->id,
            'subuserid' => $user4->id,
        ]);

        delete_user($user2);
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id, 'userid' => $user4->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id, 'userid' => $user5->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2b->id, 'userid' => $user4->id]));
    }

    public function test_cron_cleanup(): void {
        global $DB;

        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);

        $supervisor1 = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 1',
            'userid' => $user1->id,
            'subuserids' => [$user2->id, $user3->id],
        ]);
        $supervisor1b = supervisor::create((object)[
            'frameworkid' => $framework1->id,
            'teamname' => 'team 2',
            'userid' => null,
            'subuserids' => [$user4->id, $user5->id],
        ]);

        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2 = supervisor::create((object)[
            'frameworkid' => $framework2->id,
            'userid' => $user3->id,
            'subuserid' => $user4->id,
        ]);

        $DB->set_field('user', 'deleted', '1', ['id' => $user2->id]);
        subordinate::cron_cleanup();
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1->id, 'userid' => $user3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id, 'userid' => $user4->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor1b->id, 'userid' => $user5->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['supervisorid' => $supervisor2->id, 'userid' => $user4->id]));
    }
}
