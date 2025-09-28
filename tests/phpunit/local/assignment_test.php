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

namespace tool_mucertify\phpunit\local;

use tool_mucertify\local\source\manual;
use tool_mucertify\local\assignment;
use tool_mucertify\local\certification;
use tool_mucertify\local\period;

/**
 * Certification assignment test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\assignment
 */
final class assignment_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_source_classes(): void {
        $classes = \tool_mucertify\local\assignment::get_source_classes();
        $this->assertIsArray($classes);
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
        }
        $this->assertArrayHasKey('manual', $classes);
        $this->assertArrayHasKey('cohort', $classes);
        $this->assertArrayHasKey('selfassignment', $classes);
        $this->assertArrayHasKey('approval', $classes);
    }

    public function test_get_source_classname(): void {
        $this->assertSame(manual::class, assignment::get_source_classname('manual'));
        $this->assertSame(null, assignment::get_source_classname('xyz'));
    }

    public function test_get_source_names(): void {
        $names = \tool_mucertify\local\assignment::get_source_names();
        $this->assertSame('Manual assignment', $names['manual']);
        $this->assertSame('Automatic cohort assignment', $names['cohort']);
        $this->assertSame('Self assignment', $names['selfassignment']);
        $this->assertSame('Requests with approval', $names['approval']);
    }

    public function test_get_status_html(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $now = time();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Not certified', $status);

        $certification = certification::archive($certification->id);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Archived', $status);
        $certification = certification::restore($certification->id);

        $assignment = \tool_mucertify\local\source\base::assignment_archive($assignment->id);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Archived', $status);
        $assignment = \tool_mucertify\local\source\base::assignment_restore($assignment->id);

        $period = period::override_dates((object)[
            'id' => $period->id,
            'timecertified' => $now - HOURSECS,
            'timefrom' => $now - DAYSECS,
            'timeuntil' => $now + DAYSECS,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id]);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Valid', $status);

        $period = period::override_dates((object)[
            'id' => $period->id,
            'timerevoked' => $now,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id]);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Not certified', $status);

        $period = period::override_dates((object)[
            'id' => $period->id,
            'timerevoked' => null,
            'timecertified' => $now - DAYSECS,
            'timefrom' => $now - WEEKSECS,
            'timeuntil' => $now - HOURSECS,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id]);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Expired', $status);

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)[
            'id' => $assignment->id,
            'timecertifiedtemp' => $now + DAYSECS,
        ]);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Temporary valid', $status);

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)[
            'id' => $assignment->id,
            'timecertifiedtemp' => $now - 1,
        ]);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Expired', $status);

        $assignment = \tool_mucertify\local\source\base::assignment_archive($assignment->id);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Archived', $status);
    }

    public function test_sync_current_status(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);

        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        \tool_mucertify\local\assignment::sync_current_status($assignment);
    }

    public function test_fix_assignment_sources(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);

        \tool_mucertify\local\assignment::fix_assignment_sources(null, null);
        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, null);
        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, $user1->id);
        \tool_mucertify\local\assignment::fix_assignment_sources(null, $user1->id);
    }

    public function test_has_active_assignments(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $this->assertTrue(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment = \tool_mucertify\local\source\base::assignment_archive($assignment->id);
        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        $assignment = \tool_mucertify\local\source\base::assignment_restore($assignment->id);
        $certification = \tool_mucertify\local\certification::archive($certification->id);
        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));
    }

    public function test_get_until_html(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $now = time();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('Not set', \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + DAYSECS]);
        $this->assertSame(
            userdate($assignment->timecertifiedtemp, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment)
        );

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $period1 = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame('Not set', \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(
            userdate($period1->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment)
        );

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + WEEKSECS]);
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(
            userdate($assignment->timecertifiedtemp, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment)
        );

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $data = [
            'assignmentid' => $assignment->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 4000),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 6000),
            'timeuntil' => (string)($now + 7000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::add((object)$data);
        $this->assertSame(
            userdate($period1->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment)
        );

        $dateoverrides = [
            'id' => $period2->id,
            'timecertified' => (string)($now + 1234),
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(
            userdate($period2->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment)
        );

        $dateoverrides = [
            'id' => $period2->id,
            'timerevoked' => $now,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(
            userdate($period1->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment)
        );
    }

    public function test_fix_caches_indirect(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $now = time();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment->timecertifiedfrom);
        $this->assertSame(null, $assignment->timecertifieduntil);

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => $now - DAYSECS]);
        $this->assertSame((string)($now - DAYSECS - DAYSECS), $assignment->timecertifiedfrom);
        $this->assertSame(null, $assignment->timecertifieduntil);
        $this->assertSame((string)($now - DAYSECS), $assignment->timecertifiedtemp);

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + (DAYSECS * 2)]);
        $this->assertSame($assignment->timecreated, $assignment->timecertifiedfrom);
        $this->assertSame(null, $assignment->timecertifieduntil);
        $this->assertSame((string)($now + (DAYSECS * 2)), $assignment->timecertifiedtemp);

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $period1 = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(null, $assignment->timecertifiedfrom);
        $this->assertSame(null, $assignment->timecertifieduntil);

        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($dateoverrides['timefrom'], $assignment->timecertifiedfrom);
        $this->assertSame($dateoverrides['timeuntil'], $assignment->timecertifieduntil);
        $this->assertSame(null, $assignment->timecertifiedtemp);

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + WEEKSECS]);
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($dateoverrides['timefrom'], $assignment->timecertifiedfrom);
        $this->assertSame($dateoverrides['timeuntil'], $assignment->timecertifieduntil);
        $this->assertSame((string)($now + WEEKSECS), $assignment->timecertifiedtemp);

        $assignment = \tool_mucertify\local\source\base::assignment_update((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $data = [
            'assignmentid' => $assignment->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 4000),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 6000),
            'timeuntil' => (string)($now + 7000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::add((object)$data);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($dateoverrides['timefrom'], $assignment->timecertifiedfrom);
        $this->assertSame($dateoverrides['timeuntil'], $assignment->timecertifieduntil);

        $dateoverrides = [
            'id' => $period2->id,
            'timecertified' => (string)($now + 1234),
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($period1->timefrom, $assignment->timecertifiedfrom);
        $this->assertSame($period2->timeuntil, $assignment->timecertifieduntil);

        $dateoverrides = [
            'id' => $period2->id,
            'timerevoked' => $now,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment->timecertifiedfrom);
        $this->assertSame($period1->timeuntil, $assignment->timecertifieduntil);
    }
}
