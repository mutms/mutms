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

use tool_mucertify\local\period;
use tool_mulib\local\date_util;
use stdClass;
use tool_mucertify\local\source\manual;
use tool_muprog\local\course_reset;
use tool_muprog\local\program;

/**
 * Certification period helper test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\period
 */
final class period_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_default_dates(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $program3 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $data = [
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $now = time();

        $this->setCurrentTimeStart();
        $dateoverrides = [];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertTimeCurrent($result['timewindowstart']);
        $this->assertSame(null, $result['timewindowdue']);
        $this->assertSame(null, $result['timewindowend']);
        $this->assertSame(null, $result['timefrom']);
        $this->assertSame(null, $result['timeuntil']);
        $this->assertCount(5, $result);

        $data = [
            'id' => $certification->id,
            'due1' => WEEKSECS,
            'windowend1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P30D'],
            'valid1' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expires1' => ['since' => \tool_mucertify\local\certification::SINCE_CERTIFIED, 'delay' => 'P1Y'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $dateoverrides = [
            'timewindowstart' => $now,
        ];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($now, $result['timewindowstart']);
        $this->assertSame($now + WEEKSECS, $result['timewindowdue']);
        $this->assertSame($now + DAYSECS * 30, $result['timewindowend']);
        $this->assertSame(null, $result['timefrom']);
        $this->assertSame(null, $result['timeuntil']);
        $this->assertCount(5, $result);

        $data = [
            'id' => $certification->id,
            'due1' => WEEKSECS,
            'windowend1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P30D'],
            'valid1' => \tool_mucertify\local\certification::SINCE_WINDOWSTART,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P90D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $dateoverrides = [
            'timewindowstart' => $now,
        ];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($now, $result['timewindowstart']);
        $this->assertSame($now + WEEKSECS, $result['timewindowdue']);
        $this->assertSame($now + DAYSECS * 30, $result['timewindowend']);
        $this->assertSame($now, $result['timefrom']);
        $this->assertSame($now + DAYSECS * 90, $result['timeuntil']);
        $this->assertCount(5, $result);

        $data = [
            'id' => $certification->id,
            'due1' => WEEKSECS,
            'windowend1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWDUE, 'delay' => 'P30D'],
            'valid1' => \tool_mucertify\local\certification::SINCE_WINDOWDUE,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWDUE, 'delay' => 'P90D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $dateoverrides = [
            'timewindowstart' => $now,
        ];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($now, $result['timewindowstart']);
        $this->assertSame($now + WEEKSECS, $result['timewindowdue']);
        $this->assertSame($now + DAYSECS * 37, $result['timewindowend']);
        $this->assertSame($now + WEEKSECS, $result['timefrom']);
        $this->assertSame($now + DAYSECS * 97, $result['timeuntil']);
        $this->assertCount(5, $result);

        $data = [
            'id' => $certification->id,
            'due1' => WEEKSECS,
            'windowend1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWDUE, 'delay' => 'P30D'],
            'valid1' => \tool_mucertify\local\certification::SINCE_WINDOWEND,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWEND, 'delay' => 'P90D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $dateoverrides = [
            'timewindowstart' => $now,
        ];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($now, $result['timewindowstart']);
        $this->assertSame($now + WEEKSECS, $result['timewindowdue']);
        $this->assertSame($now + DAYSECS * 37, $result['timewindowend']);
        $this->assertSame($now + DAYSECS * 37, $result['timefrom']);
        $this->assertSame($now + DAYSECS * 127, $result['timeuntil']);
        $this->assertCount(5, $result);

        $data = [
            'id' => $certification->id,
            'due1' => WEEKSECS,
            'windowend1' => ['since' => \tool_mucertify\local\certification::SINCE_NEVER],
            'valid1' => \tool_mucertify\local\certification::SINCE_WINDOWSTART,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_NEVER],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $dateoverrides = [
            'timewindowstart' => $now,
        ];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($now, $result['timewindowstart']);
        $this->assertSame($now + WEEKSECS, $result['timewindowdue']);
        $this->assertSame(null, $result['timewindowend']);
        $this->assertSame($now, $result['timefrom']);
        $this->assertSame(null, $result['timeuntil']);
        $this->assertCount(5, $result);

        $dateoverrides = [
            'timewindowstart' => $now + 1000,
            'timewindowdue' => $now + 2000,
            'timewindowend' => $now + 3000,
            'timefrom' => $now + 1500,
            'timeuntil' => $now + 4000,
        ];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($dateoverrides['timewindowstart'], $result['timewindowstart']);
        $this->assertSame($dateoverrides['timewindowdue'], $result['timewindowdue']);
        $this->assertSame($dateoverrides['timewindowend'], $result['timewindowend']);
        $this->assertSame($dateoverrides['timefrom'], $result['timefrom']);
        $this->assertSame($dateoverrides['timeuntil'], $result['timeuntil']);
        $this->assertCount(5, $result);

        // Second period tests.
        $data = [
            'id' => $certification->id,
            'programid1' => $program1->id,
            'due1' => WEEKSECS,
            'windowend1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P1D'],
            'valid1' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_CERTIFIED, 'delay' => 'P90D'],
            'recertify' => DAYSECS,
            'programid2' => $program2->id,
            'windowend2' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P30D'],
            'valid2' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration2' => ['since' => \tool_mucertify\local\certification::SINCE_CERTIFIED, 'delay' => 'P60D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        manual::assign_users($certification->id, $source->id, [$user1->id], [
            'timewindowstart' => $now - 100,
            'timewindowdue' => $now + DAYSECS,
            'timewindowend' => $now + DAYSECS * 2,
            'timefrom' => $now + 150,
            'timeuntil' => $now + DAYSECS * 90,
            'timecertified' => $now,
        ]);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );

        $dateoverrides = [];
        $this->setCurrentTimeStart();
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($now + DAYSECS * 90 - DAYSECS, $result['timewindowstart']);
        $this->assertSame($now + DAYSECS * 90, $result['timewindowdue']);
        $this->assertSame($result['timewindowstart'] + DAYSECS * 30, $result['timewindowend']);
        $this->assertSame(null, $result['timefrom']);
        $this->assertSame(null, $result['timeuntil']);
        $this->assertCount(5, $result);

        $dateoverrides = [
            'timewindowstart' => $now + DAYSECS + 10,
        ];
        $this->setCurrentTimeStart();
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($dateoverrides['timewindowstart'], $result['timewindowstart']);
        $this->assertSame($result['timewindowstart'] + WEEKSECS, $result['timewindowdue']);
        $this->assertSame($result['timewindowstart'] + DAYSECS * 30, $result['timewindowend']);
        $this->assertSame(null, $result['timefrom']);
        $this->assertSame(null, $result['timeuntil']);
        $this->assertCount(5, $result);

        $dateoverrides = [
            'timewindowstart' => $now + DAYSECS * 120,
        ];
        $this->setCurrentTimeStart();
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($dateoverrides['timewindowstart'], $result['timewindowstart']);
        $this->assertSame($result['timewindowstart'] + WEEKSECS, $result['timewindowdue']);
        $this->assertSame($result['timewindowstart'] + DAYSECS * 30, $result['timewindowend']);
        $this->assertSame(null, $result['timefrom']);
        $this->assertSame(null, $result['timeuntil']);
        $this->assertCount(5, $result);

        $dateoverrides = [
            'timewindowstart' => $now + 1000,
            'timewindowdue' => $now + 2000,
            'timewindowend' => $now + 3000,
            'timefrom' => $now + 1500,
            'timeuntil' => $now + 4000,
        ];
        $result = period::get_default_dates($certification, $user1->id, $dateoverrides);
        $this->assertSame($dateoverrides['timewindowstart'], $result['timewindowstart']);
        $this->assertSame($dateoverrides['timewindowdue'], $result['timewindowdue']);
        $this->assertSame($dateoverrides['timewindowend'], $result['timewindowend']);
        $this->assertSame($dateoverrides['timefrom'], $result['timefrom']);
        $this->assertSame($dateoverrides['timeuntil'], $result['timeuntil']);
        $this->assertCount(5, $result);
    }

    public function test_add(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $program3 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $data = [
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $periods = $DB->get_records(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $user1->id],
            'timewindowstart ASC'
        );
        $this->assertCount(0, $periods);

        $now = time();

        $data = [
            'assignmentid' => $assignment->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now - 10),
            'evidencedetails' => 'should be ignored',
        ];
        $period1 = period::add((object)$data);
        $this->assertSame($certification->id, $period1->certificationid);
        $this->assertSame($user1->id, $period1->userid);
        $this->assertSame($program1->id, $period1->programid);
        $this->assertSame($data['timewindowstart'], $period1->timewindowstart);
        $this->assertSame(null, $period1->timewindowdue);
        $this->assertSame(null, $period1->timewindowend);
        $this->assertSame(null, $period1->allocationid);
        $this->assertSame(null, $period1->timecertified);
        $this->assertSame(null, $period1->timefrom);
        $this->assertSame(null, $period1->timeuntil);
        $this->assertSame(null, $period1->timerevoked);
        $this->assertSame('{}', $period1->evidencejson);
        $this->assertSame('1', $period1->first);
        $this->assertSame('1', $period1->recertifiable);

        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now + 1000),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timecertified' => (string)$now,
            'evidencedetails' => 'done elsewhere',
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
        ];
        $period2 = period::add((object)$data);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertSame($certification->id, $period2->certificationid);
        $this->assertSame($user1->id, $period2->userid);
        $this->assertSame($program2->id, $period2->programid);
        $this->assertSame($data['timewindowstart'], $period2->timewindowstart);
        $this->assertSame($data['timewindowdue'], $period2->timewindowdue);
        $this->assertSame($data['timewindowend'], $period2->timewindowend);
        $this->assertSame(null, $period2->allocationid);
        $this->assertSame($data['timecertified'], $period2->timecertified);
        $this->assertSame($data['timefrom'], $period2->timefrom);
        $this->assertSame($data['timeuntil'], $period2->timeuntil);
        $this->assertSame(null, $period2->timerevoked);
        $this->assertSame(json_encode(['details' => $data['evidencedetails']]), $period2->evidencejson);
        $this->assertSame('0', $period2->first);
        $this->assertSame('1', $period2->recertifiable);
        $this->assertSame('1', $period1->first);
        $this->assertSame('0', $period1->recertifiable);

        $data = [
            'assignmentid' => $assignment->id,
            'programid' => $program3->id,
            'timewindowstart' => (string)($now - 10000),
        ];
        $period3 = period::add((object)$data);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertSame($certification->id, $period3->certificationid);
        $this->assertSame($user1->id, $period3->userid);
        $this->assertSame($program3->id, $period3->programid);
        $this->assertSame($data['timewindowstart'], $period3->timewindowstart);
        $this->assertSame(null, $period3->timewindowdue);
        $this->assertSame(null, $period3->timewindowend);
        $this->assertSame(null, $period3->allocationid);
        $this->assertSame(null, $period3->timecertified);
        $this->assertSame(null, $period3->timefrom);
        $this->assertSame(null, $period3->timeuntil);
        $this->assertSame(null, $period3->timerevoked);
        $this->assertSame('{}', $period3->evidencejson);
        $this->assertSame('1', $period3->first);
        $this->assertSame('0', $period3->recertifiable);
        $this->assertSame('0', $period2->first);
        $this->assertSame('1', $period2->recertifiable);
        $this->assertSame('0', $period1->first);
        $this->assertSame('0', $period1->recertifiable);

        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 90000),
            'timerevoked' => (string)$now,
            'evidencedetails' => 'bad luck',
        ];
        $period4 = period::add((object)$data);
        $period3 = $DB->get_record('tool_mucertify_period', ['id' => $period3->id], '*', MUST_EXIST);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $this->assertSame($certification->id, $period4->certificationid);
        $this->assertSame($user1->id, $period4->userid);
        $this->assertSame($program1->id, $period4->programid);
        $this->assertSame($data['timewindowstart'], $period4->timewindowstart);
        $this->assertSame(null, $period4->timewindowdue);
        $this->assertSame(null, $period4->timewindowend);
        $this->assertSame(null, $period4->allocationid);
        $this->assertSame(null, $period4->timecertified);
        $this->assertSame(null, $period4->timefrom);
        $this->assertSame(null, $period4->timeuntil);
        $this->assertSame($data['timerevoked'], $period4->timerevoked);
        $this->assertSame(json_encode(['details' => $data['evidencedetails']]), $period4->evidencejson);
        $this->assertSame('0', $period4->first);
        $this->assertSame('0', $period4->recertifiable);
        $this->assertSame('1', $period3->first);
        $this->assertSame('0', $period3->recertifiable);
        $this->assertSame('0', $period2->first);
        $this->assertSame('1', $period2->recertifiable);
        $this->assertSame('0', $period1->first);
        $this->assertSame('0', $period1->recertifiable);

        $data = [
            'certificationid' => $certification->id,
            'userid' => $user2->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now - 10),
        ];
        $period5 = period::add((object)$data);
        $this->assertSame($certification->id, $period5->certificationid);
        $this->assertSame($user2->id, $period5->userid);
        $this->assertSame($program1->id, $period5->programid);
        $this->assertSame($data['timewindowstart'], $period5->timewindowstart);
        $this->assertSame(null, $period5->timewindowdue);
        $this->assertSame(null, $period5->timewindowend);
        $this->assertSame(null, $period5->allocationid);
        $this->assertSame(null, $period5->timecertified);
        $this->assertSame(null, $period5->timefrom);
        $this->assertSame(null, $period5->timeuntil);
        $this->assertSame(null, $period5->timerevoked);
        $this->assertSame('{}', $period5->evidencejson);
        $this->assertSame('1', $period5->first);
        $this->assertSame('1', $period5->recertifiable);

        try {
            $data = [
                'certificationid' => $certification->id,
                'userid' => $user2->id,
                'programid' => $program1->id,
                'timewindowstart' => (string)($now - 10),
                'timewindowdue' => (string)($now - 11),
            ];
            period::add((object)$data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timewindowdue invalid)', $ex->getMessage());
        }

        try {
            $data = [
                'certificationid' => $certification->id,
                'userid' => $user2->id,
                'programid' => $program1->id,
                'timewindowstart' => (string)($now - 10),
                'timewindowend' => (string)($now - 10),
            ];
            period::add((object)$data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timewindowend invalid)', $ex->getMessage());
        }

        try {
            $data = [
                'certificationid' => $certification->id,
                'userid' => $user2->id,
                'programid' => $program1->id,
                'timewindowstart' => (string)($now - 10),
                'timewindowdue' => (string)($now + 10),
                'timewindowend' => (string)($now + 9),
            ];
            period::add((object)$data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timewindowend invalid)', $ex->getMessage());
        }

        try {
            $data = [
                'certificationid' => $certification->id,
                'userid' => $user2->id,
                'programid' => $program1->id,
                'timewindowstart' => (string)($now - 10),
                'timecertified' => (string)$now,
                'timefrom' => (string)($now - 100),
                'timeuntil' => (string)($now - 100),
            ];
            period::add((object)$data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timeuntil invalid)', $ex->getMessage());
        }

        try {
            $data = [
                'certificationid' => $certification->id,
                'userid' => $user2->id,
                'programid' => $program1->id,
                'timewindowstart' => (string)($now - 10),
                'timecertified' => $now,
                'timefrom' => null,
                'timeuntil' => (string)($now + 100),
            ];
            period::add((object)$data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timefrom required)', $ex->getMessage());
        }

        // Import of historic periods.
        $user3 = $this->getDataGenerator()->create_user();
        manual::assign_users($certification->id, $source->id, [$user3->id], ['noperiod' => true]);
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user3->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user3->id,
            'programid' => null,
            'timecertified' => (string)(new \DateTime('2019-01-01'))->getTimestamp(),
            'timewindowstart' => (string)(new \DateTime('2019-01-01'))->getTimestamp(),
            'timefrom' => (string)(new \DateTime('2019-01-01'))->getTimestamp(),
            'timeuntil' => (string)(new \DateTime('2019-3-31'))->getTimestamp(),
        ];
        $period31 = period::add((object)$data);
        $this->assertSame($certification->id, $period31->certificationid);
        $this->assertSame($user3->id, $period31->userid);
        $this->assertSame(null, $period31->programid);
        $this->assertSame($data['timewindowstart'], $period31->timewindowstart);
        $this->assertSame(null, $period31->timewindowdue);
        $this->assertSame(null, $period31->timewindowend);
        $this->assertSame(null, $period31->allocationid);
        $this->assertSame($data['timecertified'], $period31->timecertified);
        $this->assertSame($data['timefrom'], $period31->timefrom);
        $this->assertSame($data['timeuntil'], $period31->timeuntil);
        $this->assertSame(null, $period31->timerevoked);
        $this->assertSame('{}', $period31->evidencejson);
        $this->assertSame('1', $period31->first);
        $this->assertSame('1', $period31->recertifiable);

        $data = [
            'certificationid' => $certification->id,
            'userid' => $user3->id,
            'programid' => null,
            'timecertified' => (string)(new \DateTime('2019-03-31'))->getTimestamp(),
            'timewindowstart' => (string)(new \DateTime('2019-03-31'))->getTimestamp(),
            'timefrom' => (string)(new \DateTime('2019-03-31'))->getTimestamp(),
            'timeuntil' => (string)(new \DateTime('2019-05-31'))->getTimestamp(),
        ];
        $period31 = period::add((object)$data);
        $this->assertSame($certification->id, $period31->certificationid);
        $this->assertSame($user3->id, $period31->userid);
        $this->assertSame(null, $period31->programid);
        $this->assertSame($data['timewindowstart'], $period31->timewindowstart);
        $this->assertSame(null, $period31->timewindowdue);
        $this->assertSame(null, $period31->timewindowend);
        $this->assertSame(null, $period31->allocationid);
        $this->assertSame($data['timecertified'], $period31->timecertified);
        $this->assertSame($data['timefrom'], $period31->timefrom);
        $this->assertSame($data['timeuntil'], $period31->timeuntil);
        $this->assertSame(null, $period31->timerevoked);
        $this->assertSame('{}', $period31->evidencejson);
        $this->assertSame('0', $period31->first);
        $this->assertSame('1', $period31->recertifiable);
    }

    public function test_add_first(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $program3 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
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
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $DB->delete_records('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user1->id]);

        $this->setCurrentTimeStart();
        $period1 = period::add_first($assignment, []);
        $this->assertSame($certification->id, $period1->certificationid);
        $this->assertSame($user1->id, $period1->userid);
        $this->assertSame($program1->id, $period1->programid);
        $this->assertTimeCurrent($period1->timewindowstart);
        $this->assertSame(null, $period1->timewindowdue);
        $this->assertSame(null, $period1->timewindowend);
        $this->assertSame(null, $period1->allocationid);
        $this->assertSame(null, $period1->timecertified);
        $this->assertSame(null, $period1->timefrom);
        $this->assertSame(null, $period1->timeuntil);
        $this->assertSame(null, $period1->timerevoked);
        $this->assertSame('{}', $period1->evidencejson);
        $this->assertSame('1', $period1->first);
        $this->assertSame('1', $period1->recertifiable);

        $this->assertSame(null, period::add_first($assignment, []));
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user1->id]));

        $DB->delete_records('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user1->id]);
        $data = [
            'id' => $certification->id,
            'due1' => DAYSECS,
            'windowend1' => \tool_mucertify\local\util::get_delay_form_value(['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P7D'], 'days'),
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $period1 = period::add_first($assignment, []);
        $this->assertSame($certification->id, $period1->certificationid);
        $this->assertSame($user1->id, $period1->userid);
        $this->assertSame($program1->id, $period1->programid);
        $this->assertTimeCurrent($period1->timewindowstart);
        $this->assertSame((string)($period1->timewindowstart + DAYSECS), $period1->timewindowdue);
        $this->assertSame((string)($period1->timewindowstart + DAYSECS * 7), $period1->timewindowend);
        $this->assertSame(null, $period1->allocationid);
        $this->assertSame(null, $period1->timecertified);
        $this->assertSame(null, $period1->timefrom);
        $this->assertSame(null, $period1->timeuntil);
        $this->assertSame(null, $period1->timerevoked);
        $this->assertSame('{}', $period1->evidencejson);
        $this->assertSame('1', $period1->first);
        $this->assertSame('1', $period1->recertifiable);

        $now = time();
        $DB->delete_records('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user1->id]);
        $dateoverrides = [
            'timewindowstart' => (string)($now + 1000),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)($now - 1),
            'evidencedetails' => 'not ignored',
            // The rest is ignored.
            'timerevoked' => (string)$now,
            'programid' => $program2->id,
        ];
        $period1 = period::add_first($assignment, $dateoverrides);
        $this->assertSame($certification->id, $period1->certificationid);
        $this->assertSame($user1->id, $period1->userid);
        $this->assertSame($program1->id, $period1->programid);
        $this->assertSame($dateoverrides['timewindowstart'], $period1->timewindowstart);
        $this->assertSame($dateoverrides['timewindowdue'], $period1->timewindowdue);
        $this->assertSame($dateoverrides['timewindowend'], $period1->timewindowend);
        $this->assertSame(null, $period1->allocationid);
        $this->assertSame($dateoverrides['timecertified'], $period1->timecertified);
        $this->assertSame($dateoverrides['timefrom'], $period1->timefrom);
        $this->assertSame($dateoverrides['timeuntil'], $period1->timeuntil);
        $this->assertSame(null, $period1->timerevoked);
        $this->assertSame(json_encode(['details' => $dateoverrides['evidencedetails']]), $period1->evidencejson);
        $this->assertSame('1', $period1->first);
        $this->assertSame('1', $period1->recertifiable);
    }

    public function test_override_dates(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $program3 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
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
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $now = time();
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now + 1000),
        ];
        $period2 = period::add((object)$data);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);

        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => (string)$now,
            'evidencedetails' => 'bad',
        ];
        $period1 = period::override_dates((object)$dateoverrides);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $this->assertSame($dateoverrides['timewindowstart'], $period1->timewindowstart);
        $this->assertSame($dateoverrides['timewindowdue'], $period1->timewindowdue);
        $this->assertSame($dateoverrides['timewindowend'], $period1->timewindowend);
        $this->assertSame(null, $period1->allocationid);
        $this->assertSame($dateoverrides['timecertified'], $period1->timecertified);
        $this->assertSame($dateoverrides['timefrom'], $period1->timefrom);
        $this->assertSame($dateoverrides['timeuntil'], $period1->timeuntil);
        $this->assertSame($dateoverrides['timerevoked'], $period1->timerevoked);
        $this->assertSame(json_encode(['details' => $dateoverrides['evidencedetails']]), $period1->evidencejson);
        $this->assertSame('0', $period1->first);
        $this->assertSame('0', $period1->recertifiable);
        $this->assertSame('1', $period2->first);
        $this->assertSame('1', $period2->recertifiable);

        $dateoverrides2 = [
            'id' => $period1->id,
            'timerevoked' => null,
        ];
        $period1 = period::override_dates((object)$dateoverrides2);
        $this->assertSame($dateoverrides['timewindowstart'], $period1->timewindowstart);
        $this->assertSame($dateoverrides['timewindowdue'], $period1->timewindowdue);
        $this->assertSame($dateoverrides['timewindowend'], $period1->timewindowend);
        $this->assertSame(null, $period1->allocationid);
        $this->assertSame($dateoverrides['timecertified'], $period1->timecertified);
        $this->assertSame($dateoverrides['timefrom'], $period1->timefrom);
        $this->assertSame($dateoverrides['timeuntil'], $period1->timeuntil);
        $this->assertSame(null, $period1->timerevoked);
        $this->assertSame(json_encode(['details' => $dateoverrides['evidencedetails']]), $period1->evidencejson);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $this->assertSame('0', $period1->first);
        $this->assertSame('1', $period1->recertifiable);
        $this->assertSame('1', $period2->first);
        $this->assertSame('0', $period2->recertifiable);

        $dateoverrides = [
            'id' => $period1->id,
            'timecertified' => null,
            'evidencedetails' => 'ignored',
        ];
        $period1 = period::override_dates((object)$dateoverrides);
        $this->assertSame('{}', $period1->evidencejson);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $this->assertSame('0', $period1->first);
        $this->assertSame('1', $period1->recertifiable);
        $this->assertSame('1', $period2->first);
        $this->assertSame('0', $period2->recertifiable);

        try {
            $dateoverrides = [
                'id' => $period1->id,
                'timewindowstart' => 0,
            ];
            $period1 = period::override_dates((object)$dateoverrides);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timewindowstart invalid)', $ex->getMessage());
        }

        try {
            $dateoverrides = [
                'id' => $period1->id,
                'timewindowstart' => (string)($now + 2000),
            ];
            $period1 = period::override_dates((object)$dateoverrides);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timewindowdue invalid)', $ex->getMessage());
        }

        try {
            $dateoverrides = [
                'id' => $period1->id,
                'timewindowstart' => (string)($now + 3000),
                'timewindowdue' => null,
            ];
            $period1 = period::override_dates((object)$dateoverrides);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timewindowend invalid)', $ex->getMessage());
        }

        try {
            $dateoverrides = [
                'id' => $period1->id,
                'timefrom' => (string)($now + 3000),
                'timeuntil' => (string)($now + 2000),
            ];
            $period1 = period::override_dates((object)$dateoverrides);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timeuntil invalid)', $ex->getMessage());
        }

        try {
            $dateoverrides = [
                'id' => $period1->id,
                'timefrom' => 0,
                'timeuntil' => (string)($now + 2000),
                'timecertified' => (string)$now,
            ];
            $period1 = period::override_dates((object)$dateoverrides);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (timefrom required)', $ex->getMessage());
        }
    }

    public function test_update_recertifiable(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $program3 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
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
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $now = time();
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now - 1000),
            'timerevoked' => $now,
        ];
        $period0 = period::add((object)$data);
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now + 1000),
        ];
        $period2 = period::add((object)$data);
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now + 3000),
            'timerevoked' => $now,
        ];
        $period3 = period::add((object)$data);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $period0 = $DB->get_record('tool_mucertify_period', ['id' => $period0->id], '*', MUST_EXIST);
        $this->assertSame('0', $period0->first);
        $this->assertSame('0', $period0->recertifiable);
        $this->assertSame('1', $period1->first);
        $this->assertSame('0', $period1->recertifiable);
        $this->assertSame('0', $period2->first);
        $this->assertSame('1', $period2->recertifiable);
        $this->assertSame('0', $period3->first);
        $this->assertSame('0', $period3->recertifiable);

        period::update_recertifiable($assignment, true);
        $period3 = $DB->get_record('tool_mucertify_period', ['id' => $period3->id], '*', MUST_EXIST);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $period0 = $DB->get_record('tool_mucertify_period', ['id' => $period0->id], '*', MUST_EXIST);
        $this->assertSame('0', $period0->first);
        $this->assertSame('0', $period0->recertifiable);
        $this->assertSame('1', $period1->first);
        $this->assertSame('0', $period1->recertifiable);
        $this->assertSame('0', $period2->first);
        $this->assertSame('0', $period2->recertifiable);
        $this->assertSame('0', $period3->first);
        $this->assertSame('0', $period3->recertifiable);

        period::update_recertifiable($assignment, false);
        $period3 = $DB->get_record('tool_mucertify_period', ['id' => $period3->id], '*', MUST_EXIST);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $period0 = $DB->get_record('tool_mucertify_period', ['id' => $period0->id], '*', MUST_EXIST);
        $this->assertSame('0', $period0->first);
        $this->assertSame('0', $period0->recertifiable);
        $this->assertSame('1', $period1->first);
        $this->assertSame('0', $period1->recertifiable);
        $this->assertSame('0', $period2->first);
        $this->assertSame('1', $period2->recertifiable);
        $this->assertSame('0', $period3->first);
        $this->assertSame('0', $period3->recertifiable);
    }

    public function test_delete(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $program3 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
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
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );

        $now = time();
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now - 1000),
            'timerevoked' => $now,
        ];
        $period0 = period::add((object)$data);
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now + 1000),
        ];
        $period2 = period::add((object)$data);
        $data = [
            'certificationid' => $certification->id,
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timewindowstart' => (string)($now + 3000),
        ];
        $period3 = period::add((object)$data);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $period1 = $DB->get_record('tool_mucertify_period', ['id' => $period1->id], '*', MUST_EXIST);
        $period0 = $DB->get_record('tool_mucertify_period', ['id' => $period0->id], '*', MUST_EXIST);

        period::delete($period1->id);
        period::delete($period3->id);
        $period0 = $DB->get_record('tool_mucertify_period', ['id' => $period0->id], '*', MUST_EXIST);
        $period2 = $DB->get_record('tool_mucertify_period', ['id' => $period2->id], '*', MUST_EXIST);
        $this->assertCount(2, $DB->get_records('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user1->id]));
        $this->assertSame('0', $period0->first);
        $this->assertSame('0', $period0->recertifiable);
        $this->assertSame('1', $period2->first);
        $this->assertSame('0', $period2->recertifiable);
    }

    public function test_allocation_completed(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program(['sources' => 'mucertify']);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);
        $top1 = program::load_content($program1->id);
        $program2 = $programgenerator->create_program(['sources' => 'mucertify']);
        $program2source = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $top2 = program::load_content($program2->id);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_NEVER],
            'recertify' => null,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id, $user3->id], [
            'timewindowstart' => time() - 100,
        ]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['sourceid' => $program1source->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $allocation3 = $DB->get_record('tool_muprog_allocation', ['sourceid' => $program1source->id, 'userid' => $user3->id], '*', MUST_EXIST);

        $this->setCurrentTimeStart();
        \tool_muprog\local\allocation::update_item_completion((object)[
            'allocationid' => $allocation1->id,
            'itemid' => $top1->get_id(),
            'timecompleted' => time(),
            'evidencetimecompleted' => time(),
            'evidencedetails' => 'test',
        ]);
        $period1 = $DB->get_record('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame($allocation1->id, $period1->allocationid);
        $this->assertTimeCurrent($period1->timecertified);
        $this->assertSame($period1->timecertified, $period1->timefrom);
        $this->assertSame(null, $period1->timeuntil);

        $data = [
            'id' => $certification->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_WINDOWSTART,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_CERTIFIED, 'delay' => 'P30D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $this->setCurrentTimeStart();
        \tool_muprog\local\allocation::update_item_completion((object)[
            'allocationid' => $allocation2->id,
            'itemid' => $top1->get_id(),
            'timecompleted' => time(),
            'evidencetimecompleted' => time(),
            'evidencedetails' => 'test',
        ]);
        $period2 = $DB->get_record('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($allocation2->id, $period2->allocationid);
        $this->assertTimeCurrent($period2->timecertified);
        $this->assertSame($period2->timewindowstart, $period2->timefrom);
        $this->assertSame((string)($period2->timecertified + 30 * DAYSECS), $period2->timeuntil);

        $now = time();
        $data = [
            'id' => $certification->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_WINDOWDUE,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWDUE, 'delay' => 'P30D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $period2->timewindowdue = $now + 200;
        $period2->timewindowend = $now + 3000;
        $period2->timecertified = null;
        $period2->timefrom = null;
        $period2->timeuntil = null;
        $DB->update_record('tool_mucertify_period', $period2);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        period::allocation_completed($program1, $allocation2);
        $period2 = $DB->get_record('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($allocation2->id, $period2->allocationid);
        $this->assertTimeCurrent($period2->timecertified);
        $this->assertSame($period2->timewindowdue, $period2->timefrom);
        $this->assertSame((string)($period2->timewindowdue + 30 * DAYSECS), $period2->timeuntil);

        $now = time();
        $data = [
            'id' => $certification->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_WINDOWEND,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWEND, 'delay' => 'P30D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $period2->timewindowdue = $now + 200;
        $period2->timewindowend = $now + 3000;
        $period2->timecertified = null;
        $period2->timefrom = null;
        $period2->timeuntil = null;
        $DB->update_record('tool_mucertify_period', $period2);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['sourceid' => $program1source->id, 'userid' => $user2->id], '*', MUST_EXIST);
        period::allocation_completed($program1, $allocation2);
        $period2 = $DB->get_record('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertSame($allocation2->id, $period2->allocationid);
        $this->assertTimeCurrent($period2->timecertified);
        $this->assertSame($period2->timewindowend, $period2->timefrom);
        $this->assertSame((string)($period2->timewindowend + 30 * DAYSECS), $period2->timeuntil);
    }

    public function test_get_status_html(): void {
        $now = time();
        $certification = new stdClass();
        $certification->archived = '0';
        $assignment = new stdClass();
        $assignment->archived = '0';
        $period = new stdClass();
        $period->timerevoked = null;
        $period->timecertified = null;
        $period->timefrom = null;
        $period->timeuntil = null;
        $period->timewindowstart = $now + 100;
        $period->timewindowdue = null;
        $period->timewindowend = null;
        $this->assertStringContainsString('>Future<', period::get_status_html($certification, $assignment, $period));

        $period->timewindowstart = $now - 100;
        $this->assertStringContainsString('>Pending<', period::get_status_html($certification, $assignment, $period));

        $period->timewindowend = $now - 50;
        $this->assertStringContainsString('>Failed<', period::get_status_html($certification, $assignment, $period));

        $period->timewindowend = $now + 2000;
        $period->timewindowdue = $now - 50;
        $this->assertStringContainsString('>Overdue<', period::get_status_html($certification, $assignment, $period));

        $period->timecertified = $now - 20;
        $period->timefrom = $now - 20;
        $this->assertStringContainsString('>Certified<', period::get_status_html($certification, $assignment, $period));

        $period->timeuntil = $now + 10;
        $this->assertStringContainsString('>Certified<', period::get_status_html($certification, $assignment, $period));

        $period->timeuntil = $now - 10;
        $this->assertStringContainsString('>Expired<', period::get_status_html($certification, $assignment, $period));

        $period->timerevoked = $now + 10;
        $this->assertStringContainsString('>Revoked<', period::get_status_html($certification, $assignment, $period));

        $assignment->archived = '1';
        $this->assertStringContainsString('>Archived<', period::get_status_html($certification, $assignment, $period));

        $assignment->archived = '0';
        $certification->archived = '1';
        $this->assertStringContainsString('>Archived<', period::get_status_html($certification, $assignment, $period));

        $assignment->archived = '0';
        $certification->archived = '0';
        $this->assertStringContainsString('>Archived<', period::get_status_html($certification, null, $period));
    }

    public function test_get_windowstart_html(): void {
        $now = time();
        $certification = new stdClass();
        $assignment = new stdClass();
        $period = new stdClass();
        $period->timewindowstart = $now + 100;

        $expected = userdate($period->timewindowstart);
        $this->assertSame($expected, period::get_windowstart_html($certification, $assignment, $period));
        $this->assertSame($expected, period::get_windowstart_html($certification, $assignment, $period, false));

        $expected = userdate($period->timewindowstart, get_string('strftimedatetimeshort'));
        $this->assertSame($expected, period::get_windowstart_html($certification, $assignment, $period, true));
    }

    public function test_get_windowdue_html(): void {
        $now = time();
        $certification = new stdClass();
        $assignment = new stdClass();
        $period = new stdClass();
        $period->timewindowdue = $now + 100;

        $expected = userdate($period->timewindowdue);
        $this->assertSame($expected, period::get_windowdue_html($certification, $assignment, $period));
        $this->assertSame($expected, period::get_windowdue_html($certification, $assignment, $period, false));

        $expected = userdate($period->timewindowdue, get_string('strftimedatetimeshort'));
        $this->assertSame($expected, period::get_windowdue_html($certification, $assignment, $period, true));

        $period->timewindowdue = null;
        $this->assertSame('Not set', period::get_windowdue_html($certification, $assignment, $period, false));
        $this->assertSame('Not set', period::get_windowdue_html($certification, $assignment, $period, true));
    }

    public function test_get_windowend_html(): void {
        $now = time();
        $certification = new stdClass();
        $assignment = new stdClass();
        $period = new stdClass();
        $period->timewindowend = $now + 100;

        $expected = userdate($period->timewindowend);
        $this->assertSame($expected, period::get_windowend_html($certification, $assignment, $period));
        $this->assertSame($expected, period::get_windowend_html($certification, $assignment, $period, false));

        $expected = userdate($period->timewindowend, get_string('strftimedatetimeshort'));
        $this->assertSame($expected, period::get_windowend_html($certification, $assignment, $period, true));

        $period->timewindowend = null;
        $this->assertSame('Not set', period::get_windowend_html($certification, $assignment, $period, false));
        $this->assertSame('Not set', period::get_windowend_html($certification, $assignment, $period, true));
    }

    public function test_get_from_html(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $data = [
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);

        $data = [
            'id' => $certification->id,
            'programid1' => $program1->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'recertify' => DAYSECS,
            'programid2' => $program1->id,
            'valid2' => \tool_mucertify\local\certification::SINCE_WINDOWDUE,
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $period = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );

        $this->assertSame('Certification completion date', period::get_from_html($certification, $assignment, $period));
        $this->assertSame('Certification completion date', period::get_from_html($certification, $assignment, $period, false));
        $this->assertSame('Certification completion date', period::get_from_html($certification, $assignment, $period, true));

        $now = time();
        $period->timefrom = $now - 100;
        $expected = userdate($period->timefrom);
        $this->assertSame($expected, period::get_from_html($certification, $assignment, $period));
        $this->assertSame($expected, period::get_from_html($certification, $assignment, $period, false));

        $expected = userdate($period->timefrom, get_string('strftimedatetimeshort'));
        $this->assertSame($expected, period::get_from_html($certification, $assignment, $period, true));

        $period->timefrom = null;
        $period->first = '0';
        $this->assertSame('Certification due', period::get_from_html($certification, $assignment, $period));

        // This should not happen.
        $period->timecertified = $now;
        $this->assertSame('Not set', period::get_from_html($certification, $assignment, $period));
    }

    public function test_get_until_html(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $data = [
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);

        $data = [
            'id' => $certification->id,
            'programid1' => $program1->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_CERTIFIED, 'delay' => 'P30D'],
            'recertify' => DAYSECS,
            'programid2' => $program1->id,
            'valid2' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration2' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWDUE, 'delay' => 'P31D'],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $source = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification->id],
            '*',
            MUST_EXIST
        );
        $period = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );

        $this->assertSame('30 days after Certification completion date', period::get_until_html($certification, $assignment, $period));
        $this->assertSame('30 days after Certification completion date', period::get_until_html($certification, $assignment, $period, false));
        $this->assertSame('30 days after Certification completion date', period::get_until_html($certification, $assignment, $period, true));

        $now = time();
        $period->timeuntil = $now - 100;
        $expected = userdate($period->timeuntil);
        $this->assertSame($expected, period::get_until_html($certification, $assignment, $period));
        $this->assertSame($expected, period::get_until_html($certification, $assignment, $period, false));

        $expected = userdate($period->timeuntil, get_string('strftimedatetimeshort'));
        $this->assertSame($expected, period::get_until_html($certification, $assignment, $period, true));

        $period->timecertified = null;
        $period->timeuntil = null;
        $period->first = '0';
        $this->assertSame('31 days after Certification due', period::get_until_html($certification, $assignment, $period));

        $period->timecertified = $now;
        $this->assertSame('No expiration', period::get_until_html($certification, $assignment, $period));

        $data = [
            'id' => $certification->id,
            'expiration2' => ['since' => \tool_mucertify\local\certification::SINCE_NEVER],
        ];
        $certification = \tool_mucertify\local\certification::update_settings((object)$data);
        $period->timecertified = null;
        $period->timeuntil = null;
        $period->first = '0';
        $this->assertSame('Never', period::get_until_html($certification, $assignment, $period));
    }

    public function test_get_recertify_html(): void {
        $now = time();
        $certification = new stdClass();
        $certification->recertify = DAYSECS;
        $certification->archived = '0';
        $assignment = new stdClass();
        $assignment->archived = '0';
        $period = new stdClass();
        $period->recertifiable = '1';
        $period->timeuntil = $now + 200;

        $expected = userdate($period->timeuntil - $certification->recertify);
        $this->assertSame($expected, period::get_recertify_html($certification, $assignment, $period));
        $this->assertSame($expected, period::get_recertify_html($certification, $assignment, $period, false));

        $expected = userdate($period->timeuntil - $certification->recertify, get_string('strftimedatetimeshort'));
        $this->assertSame($expected, period::get_recertify_html($certification, $assignment, $period, true));

        $period->timeuntil = $now - 10;
        $expected = userdate($period->timeuntil - $certification->recertify, get_string('strftimedatetimeshort'));
        $this->assertSame($expected, period::get_recertify_html($certification, $assignment, $period, true));

        $period->timeuntil = null;
        $this->assertSame('If expired', period::get_recertify_html($certification, $assignment, $period));

        $period->timeuntil = $now + 200;
        $period->recertifiable = '0';
        $this->assertSame('No', period::get_recertify_html($certification, $assignment, $period));

        $period->recertifiable = '1';
        $assignment->archived = '1';
        $this->assertSame('No', period::get_recertify_html($certification, $assignment, $period));

        $assignment->archived = '0';
        $certification->archived = '1';
        $this->assertSame('No', period::get_recertify_html($certification, $assignment, $period));

        $certification->archived = '0';
        $certification->recertify = null;
        $this->assertSame('No', period::get_recertify_html($certification, $assignment, $period));
    }

    public function test_process_recertifications(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $now = time();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $program1 = $programgenerator->create_program(['sources' => 'mucertify']);
        $program1source = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify']);
        $item1 = $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);

        $program2 = $programgenerator->create_program(['sources' => 'mucertify']);
        $program2source = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify']);
        $item2 = $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $certification1 = $generator->create_certification([
            'sources' => 'manual',
            'programid1' => $program1->id,
            'periods_resettype1' => course_reset::RESETTYPE_STANDARD,
            'recertify' => DAYSECS,
            'programid2' => $program2->id,
            'periods_grace2' => DAYSECS * 14,
            'periods_resettype2' => course_reset::RESETTYPE_FULL,
            'periods_windowend2' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P11D'],
            'periods_valid2' => \tool_mucertify\local\certification::SINCE_WINDOWDUE,
            'periods_expiration2' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWDUE, 'delay' => 'P20D'],
        ]);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification1->id, $source1->id, [$user1->id], [
            'timewindowstart' => $now - YEARSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - YEARSECS,
            'timeuntil' => $now + DAYSECS - 77,
            'timecertified' => $now - YEARSECS + 10,
        ]);
        $period1x1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame('1', $period1x1->first);
        $this->assertSame('1', $period1x1->recertifiable);
        $this->assertSame((string)($now - YEARSECS + 10), $period1x1->timecertified);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id]));

        period::process_recertifications(null, null);
        $period1x1 = $DB->get_record('tool_mucertify_period', ['id' => $period1x1->id], '*', MUST_EXIST);
        $this->assertSame('1', $period1x1->first);
        $this->assertSame('0', $period1x1->recertifiable);
        $this->assertSame((string)($now - YEARSECS + 10), $period1x1->timecertified);
        $period1x2 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user1->id, 'recertifiable' => 1],
            '*',
            MUST_EXIST
        );
        $this->assertSame('0', $period1x2->first);
        $this->assertSame((string)($now - 77), $period1x2->timewindowstart);
        $this->assertSame((string)($now + DAYSECS - 77), $period1x2->timewindowdue);
        $this->assertSame((string)($now + (DAYSECS * 11) - 77), $period1x2->timewindowend);
        $assignment1 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame((string)($now + (DAYSECS * 15) - 77), $assignment1->timecertifiedtemp);
        $this->assertCount(2, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id]));

        period::process_recertifications($certification1->id, null);
        period::process_recertifications($certification1->id, $user1->id);
        period::process_recertifications(null, $user1->id);
        $this->assertCount(2, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id]));

        manual::assign_users($certification1->id, $source1->id, [$user2->id], [
            'timewindowstart' => $now - YEARSECS,
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => $now - YEARSECS,
            'timeuntil' => $now + DAYSECS - 77,
            'timecertified' => $now - YEARSECS + 10,
        ]);
        $period2x1 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user2->id],
            '*',
            MUST_EXIST
        );

        // Recertification stopped.
        $DB->set_field('tool_mucertify_period', 'recertifiable', 0, ['id' => $period2x1->id]);
        period::process_recertifications(null, null);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $DB->set_field('tool_mucertify_period', 'recertifiable', 1, ['id' => $period2x1->id]);

        // Archived assignment.
        $assignment2 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $DB->set_field('tool_mucertify_assignment', 'archived', 1, ['id' => $assignment2->id]);
        period::process_recertifications(null, null);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $DB->set_field('tool_mucertify_assignment', 'archived', 0, ['id' => $assignment2->id]);

        // Archived certification.
        $DB->set_field('tool_mucertify_certification', 'archived', 1, ['id' => $certification1->id]);
        period::process_recertifications(null, null);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $DB->set_field('tool_mucertify_certification', 'archived', 0, ['id' => $certification1->id]);

        // Too old to recertify.
        $DB->set_field('tool_mucertify_period', 'timeuntil', $now - 91 * DAYSECS, ['id' => $period2x1->id]);
        period::process_recertifications(null, null);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $DB->set_field('tool_mucertify_period', 'timeuntil', $now + DAYSECS - 77, ['id' => $period2x1->id]);

        // No end date.
        $DB->set_field('tool_mucertify_period', 'timeuntil', null, ['id' => $period2x1->id]);
        period::process_recertifications(null, null);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $DB->set_field('tool_mucertify_period', 'timeuntil', $now + DAYSECS - 77, ['id' => $period2x1->id]);

        // Revoked.
        $DB->set_field('tool_mucertify_period', 'timerevoked', $now, ['id' => $period2x1->id]);
        period::process_recertifications(null, null);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $DB->set_field('tool_mucertify_period', 'timerevoked', null, ['id' => $period2x1->id]);

        // Not certified.
        $DB->set_field('tool_mucertify_period', 'timecertified', null, ['id' => $period2x1->id]);
        period::process_recertifications(null, null);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $DB->set_field('tool_mucertify_period', 'timecertified', $now - YEARSECS + 10, ['id' => $period2x1->id]);

        // Check we could actually certify if there were no problems above.
        $certification1 = \tool_mucertify\local\certification::update_settings((object)[
            'id' => $certification1->id,
            'grace2' => null,
        ]);
        period::process_recertifications(null, null);
        $this->assertCount(2, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id]));
        $period2x1 = $DB->get_record('tool_mucertify_period', ['id' => $period2x1->id], '*', MUST_EXIST);
        $this->assertSame('1', $period2x1->first);
        $this->assertSame('0', $period2x1->recertifiable);
        $this->assertSame((string)($now - YEARSECS + 10), $period2x1->timecertified);
        $period2x2 = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user2->id, 'recertifiable' => 1],
            '*',
            MUST_EXIST
        );
        $this->assertSame('0', $period2x2->first);
        $this->assertSame((string)($now - 77), $period2x2->timewindowstart);
        $this->assertSame((string)($now + DAYSECS - 77), $period2x2->timewindowdue);
        $this->assertSame((string)($now + (DAYSECS * 11) - 77), $period2x2->timewindowend);
        $assignment2 = $DB->get_record(
            'tool_mucertify_assignment',
            ['userid' => $user2->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $assignment2->timecertifiedtemp);
    }

    public function test_process_history_upload(): void {
        global $DB;
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $now = time();

        $user1 = $this->getDataGenerator()->create_user(
            ['email' => 'user1@example.com', 'username' => 'user1', 'idnumber' => 'iduser1']
        );
        $user2 = $this->getDataGenerator()->create_user(
            ['email' => 'user2@example.com', 'username' => 'user2', 'idnumber' => 'iduser2']
        );
        $user3 = $this->getDataGenerator()->create_user(
            ['email' => 'user3@example.com', 'username' => 'user3', 'idnumber' => 'iduser3']
        );
        $user4 = $this->getDataGenerator()->create_user(
            ['email' => 'user4@example.com', 'username' => 'user4']
        );
        $user5 = $this->getDataGenerator()->create_user(
            ['email' => 'user5@example.com', 'username' => 'user5']
        );
        $user5x = $this->getDataGenerator()->create_user(
            ['email' => 'user5@example.com', 'username' => 'user5x']
        ); // Duplicate email test.

        $program1 = $programgenerator->create_program(['sources' => 'mucertify']);
        $program2 = $programgenerator->create_program(['sources' => 'mucertify']);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P90D'],
            'recertify' => null,
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'valid1' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration1' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P90D'],
            'programid2' => $program2->id,
            'valid2' => \tool_mucertify\local\certification::SINCE_CERTIFIED,
            'expiration2' => ['since' => \tool_mucertify\local\certification::SINCE_WINDOWSTART, 'delay' => 'P90D'],
            'recertify' => DAYSECS,
        ];
        $certification2 = $generator->create_certification($data);
        $source2 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification2->id],
            '*',
            MUST_EXIST
        );

        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $period1x0 = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification1->id]);
        manual::assign_users($certification1->id, $source1->id, [$user2->id], ['noperiod' => true]);
        manual::assign_users($certification1->id, $source1->id, [$user5->id], ['noperiod' => true]);

        // Only assigned users tests.

        $formdata = (object)[
            'certificationid' => $certification1->id,
            'usercolumn' => '0',
            'usermapping' => 'username',
            'hasheaders' => '1',
            'assign' => '0',
            'skipassigned' => '0',
            'timefromcolumn' => '1',
            'timeuntilcolumn' => '2',
            'timecertifiedcolumn' => '3',
            'evidencecolumn' => '4',
            'evidencedefault' => 'history upload',
        ];
        $filedata = [
            ['username', 'from', 'until', 'certified', 'evidence'],
            ['user1', '2020-01-01', '2020-03-31', '2020-01-01', 'ext program X passed'],
            ['user1', '2020-01-04', '2020-06-30', '2020-03-30', ''], // Use default evidence text.
            ['user2', '2021-01-01', '2021-03-31', '2021-01-02', 'ext program Y passed'],
            ['user3', '2022-01-01', '2022-03-31', '2022-01-01', 'ext program X passed'], // Not assigned skipped.
            ['userz', '2020-01-01', '2020-03-31', '2020-01-01', 'ext program X passed'], // Unknown user error.
        ];
        $result = period::process_history_upload($formdata, $filedata);
        $this->assertSame(['assigned' => 0, 'periods' => 3, 'skipped' => 1, 'errors' => 1], $result);
        $this->assertCount(3, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification1->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id]));
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user3->id, 'certificationid' => $certification1->id]));
        $period2x1 = $DB->get_record('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id]);
        $this->assertSame($certification1->id, $period2x1->certificationid);
        $this->assertSame($user2->id, $period2x1->userid);
        $this->assertSame(null, $period2x1->programid);
        $this->assertSame($period2x1->timefrom, $period2x1->timewindowstart);
        $this->assertSame(null, $period2x1->timewindowdue);
        $this->assertSame(null, $period2x1->timewindowend);
        $this->assertSame(null, $period2x1->allocationid);
        $this->assertSame((string)strtotime('2021-01-02'), $period2x1->timecertified);
        $this->assertSame((string)strtotime('2021-01-01'), $period2x1->timefrom);
        $this->assertSame((string)strtotime('2021-03-31'), $period2x1->timeuntil);
        $this->assertSame(null, $period2x1->timerevoked);
        $this->assertSame('{"details":"ext program Y passed"}', $period2x1->evidencejson);
        $this->assertSame('1', $period2x1->first);
        $this->assertSame('1', $period2x1->recertifiable);
        $periods = $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification1->id], 'timewindowstart ASC');
        [$period1x1, $period1x2, $period1x3] = array_values($periods);
        $this->assertSame($certification1->id, $period1x1->certificationid);
        $this->assertSame($user1->id, $period1x1->userid);
        $this->assertSame(null, $period1x1->programid);
        $this->assertSame($period1x1->timefrom, $period1x1->timewindowstart);
        $this->assertSame(null, $period1x1->timewindowdue);
        $this->assertSame(null, $period1x1->timewindowend);
        $this->assertSame(null, $period1x1->allocationid);
        $this->assertSame((string)strtotime('2020-01-01'), $period1x1->timecertified);
        $this->assertSame((string)strtotime('2020-01-01'), $period1x1->timefrom);
        $this->assertSame((string)strtotime('2020-03-31'), $period1x1->timeuntil);
        $this->assertSame(null, $period1x1->timerevoked);
        $this->assertSame('{"details":"ext program X passed"}', $period1x1->evidencejson);
        $this->assertSame('1', $period1x1->first);
        $this->assertSame('0', $period1x1->recertifiable);
        $this->assertSame($certification1->id, $period1x2->certificationid);
        $this->assertSame($user1->id, $period1x2->userid);
        $this->assertSame(null, $period1x2->programid);
        $this->assertSame($period1x2->timefrom, $period1x2->timewindowstart);
        $this->assertSame(null, $period1x2->timewindowdue);
        $this->assertSame(null, $period1x2->timewindowend);
        $this->assertSame(null, $period1x2->allocationid);
        $this->assertSame((string)strtotime('2020-03-30'), $period1x2->timecertified);
        $this->assertSame((string)strtotime('2020-01-04'), $period1x2->timefrom);
        $this->assertSame((string)strtotime('2020-06-30'), $period1x2->timeuntil);
        $this->assertSame(null, $period1x2->timerevoked);
        $this->assertSame('{"details":"history upload"}', $period1x2->evidencejson);
        $this->assertSame('0', $period1x2->first);
        $this->assertSame('0', $period1x2->recertifiable);
        $period1x0->first = '0';
        $this->assertSame((array)$period1x0, (array)$period1x3);

        // Skip duplicate data.
        $filedata = [
            ['username', 'from', 'until', 'certified', 'evidence'],
            ['user1', '2020-01-01', '2020-03-31', '2020-01-01', 'ext program X passed'],
            ['user1', '2020-01-04', '2020-06-30', '2020-03-30', ''], // Use default evidence text.
            ['user2', '2021-01-01', '2021-03-31', '2021-01-02', 'ext program Y passed'],
        ];
        $result = period::process_history_upload($formdata, $filedata);
        $this->assertSame(['assigned' => 0, 'periods' => 0, 'skipped' => 3, 'errors' => 0], $result);

        // Errors for invalid data.
        $filedata = [
            ['username', 'from', 'until', 'certified', 'evidence'],
            ['user2', 'xyz', 'zyq', 'xyz', 'ext program X passed'], // Invalid date format.
            ['user2', '2032-01-01', '2032-03-31', '2032-01-01', 'ext program X passed'], // Future date error.
            ['user2', '2019-01-01', '2019-03-31', '', 'ext program X passed'], // No certification date error.
            ['user2', '', '2019-03-31', '2019-01-01', 'ext program X passed'], // No start date error.
            ['user2', '2019-01-01', '', '2019-01-01', 'ext program X passed'], // No end date error.
        ];
        $result = period::process_history_upload($formdata, $filedata);
        $this->assertSame(['assigned' => 0, 'periods' => 0, 'skipped' => 0, 'errors' => 5], $result);

        // Duplicate emails.
        $formdata = (object)[
            'certificationid' => $certification1->id,
            'usercolumn' => '0',
            'usermapping' => 'email',
            'hasheaders' => '0',
            'assign' => '0',
            'skipassigned' => '0',
            'timefromcolumn' => '1',
            'timeuntilcolumn' => '2',
            'timecertifiedcolumn' => '1',
            'evidencecolumn' => '',
            'evidencedefault' => 'history upload',
        ];
        $filedata = [
            ['user2@example.com', '2022-01-01', '2022-03-31'],
            ['user5@example.com', '2022-01-01', '2022-03-31'],
        ];
        $result = period::process_history_upload($formdata, $filedata);
        $this->assertSame(['assigned' => 0, 'periods' => 1, 'skipped' => 0, 'errors' => 1], $result);
        $this->assertCount(3, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification1->id]));
        $this->assertCount(2, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id]));
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user5->id, 'certificationid' => $certification1->id]));
        $periods = $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id], 'timewindowstart ASC');
        [$period2x1, $period2x2] = array_values($periods);
        $this->assertSame($certification1->id, $period2x2->certificationid);
        $this->assertSame($user2->id, $period2x2->userid);
        $this->assertSame(null, $period2x2->programid);
        $this->assertSame($period2x2->timefrom, $period2x2->timewindowstart);
        $this->assertSame(null, $period2x2->timewindowdue);
        $this->assertSame(null, $period2x2->timewindowend);
        $this->assertSame(null, $period2x2->allocationid);
        $this->assertSame((string)strtotime('2022-01-01'), $period2x2->timecertified);
        $this->assertSame((string)strtotime('2022-01-01'), $period2x2->timefrom);
        $this->assertSame((string)strtotime('2022-03-31'), $period2x2->timeuntil);
        $this->assertSame(null, $period2x2->timerevoked);
        $this->assertSame('{"details":"history upload"}', $period2x2->evidencejson);
        $this->assertSame('0', $period2x2->first);
        $this->assertSame('1', $period2x2->recertifiable);

        // ID number mapping.
        $formdata = (object)[
            'certificationid' => $certification1->id,
            'usercolumn' => '0',
            'usermapping' => 'idnumber',
            'hasheaders' => '0',
            'assign' => '0',
            'skipassigned' => '0',
            'timefromcolumn' => '1',
            'timeuntilcolumn' => '2',
            'timecertifiedcolumn' => '1',
            'evidencecolumn' => '',
            'evidencedefault' => 'history upload',
        ];
        $filedata = [
            ['iduser2', '2023-01-01', '2023-03-31'],
            ['iduserz', '2023-01-01', '2023-03-31'],
        ];
        $result = period::process_history_upload($formdata, $filedata);
        $this->assertSame(['assigned' => 0, 'periods' => 1, 'skipped' => 0, 'errors' => 1], $result);
        $this->assertCount(3, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification1->id]));
        $this->assertCount(3, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id]));
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user5->id, 'certificationid' => $certification1->id]));
        $periods = $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id], 'timewindowstart ASC');
        [$period2x1, $period2x2, $period2x3] = array_values($periods);
        $this->assertSame($certification1->id, $period2x3->certificationid);
        $this->assertSame($user2->id, $period2x3->userid);
        $this->assertSame(null, $period2x3->programid);
        $this->assertSame($period2x3->timefrom, $period2x3->timewindowstart);
        $this->assertSame(null, $period2x3->timewindowdue);
        $this->assertSame(null, $period2x3->timewindowend);
        $this->assertSame(null, $period2x3->allocationid);
        $this->assertSame((string)strtotime('2023-01-01'), $period2x3->timecertified);
        $this->assertSame((string)strtotime('2023-01-01'), $period2x3->timefrom);
        $this->assertSame((string)strtotime('2023-03-31'), $period2x3->timeuntil);
        $this->assertSame(null, $period2x3->timerevoked);
        $this->assertSame('{"details":"history upload"}', $period2x3->evidencejson);
        $this->assertSame('0', $period2x3->first);
        $this->assertSame('1', $period2x3->recertifiable);

        // Assign new.
        $formdata = (object)[
            'certificationid' => $certification1->id,
            'usercolumn' => '0',
            'usermapping' => 'username',
            'hasheaders' => '0',
            'assign' => '1',
            'skipassigned' => '0',
            'timefromcolumn' => '1',
            'timeuntilcolumn' => '2',
            'timecertifiedcolumn' => '1',
            'evidencecolumn' => '',
            'evidencedefault' => 'history upload',
        ];
        $filedata = [
            ['user2', '2019-01-01', '2019-03-31'],
            ['user3', '2019-01-01', '2019-03-31'],
        ];
        $result = period::process_history_upload($formdata, $filedata);
        $this->assertSame(['assigned' => 1, 'periods' => 2, 'skipped' => 0, 'errors' => 0], $result);
        $this->assertCount(4, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user3->id, 'certificationid' => $certification1->id]));
        $periods = $DB->get_records('tool_mucertify_period', ['userid' => $user3->id, 'certificationid' => $certification1->id], 'timewindowstart ASC');
        [$period3x1] = array_values($periods);
        $this->assertSame($certification1->id, $period3x1->certificationid);
        $this->assertSame($user3->id, $period3x1->userid);
        $this->assertSame(null, $period3x1->programid);
        $this->assertSame($period3x1->timefrom, $period3x1->timewindowstart);
        $this->assertSame(null, $period3x1->timewindowdue);
        $this->assertSame(null, $period3x1->timewindowend);
        $this->assertSame(null, $period3x1->allocationid);
        $this->assertSame((string)strtotime('2019-01-01'), $period3x1->timecertified);
        $this->assertSame((string)strtotime('2019-01-01'), $period3x1->timefrom);
        $this->assertSame((string)strtotime('2019-03-31'), $period3x1->timeuntil);
        $this->assertSame(null, $period3x1->timerevoked);
        $this->assertSame('{"details":"history upload"}', $period3x1->evidencejson);
        $this->assertSame('1', $period3x1->first);
        $this->assertSame('0', $period3x1->recertifiable);

        // Assign new and skip existing.
        $formdata = (object)[
            'certificationid' => $certification1->id,
            'usercolumn' => '0',
            'usermapping' => 'username',
            'hasheaders' => '0',
            'assign' => '1',
            'skipassigned' => '1',
            'timefromcolumn' => '1',
            'timeuntilcolumn' => '2',
            'timecertifiedcolumn' => '1',
            'evidencecolumn' => '',
            'evidencedefault' => 'history upload',
        ];
        $filedata = [
            ['user2', '2019-01-01', '2019-03-31'],
            ['user4', '2019-01-01', '2019-03-31'],
        ];
        $result = period::process_history_upload($formdata, $filedata);
        $this->assertSame(['assigned' => 1, 'periods' => 1, 'skipped' => 1, 'errors' => 0], $result);
        $this->assertCount(4, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification1->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user4->id, 'certificationid' => $certification1->id]));
        $periods = $DB->get_records('tool_mucertify_period', ['userid' => $user4->id, 'certificationid' => $certification1->id], 'timewindowstart ASC');
        [$period4x1] = array_values($periods);
        $this->assertSame($certification1->id, $period4x1->certificationid);
        $this->assertSame($user4->id, $period4x1->userid);
        $this->assertSame(null, $period4x1->programid);
        $this->assertSame($period4x1->timefrom, $period4x1->timewindowstart);
        $this->assertSame(null, $period4x1->timewindowdue);
        $this->assertSame(null, $period4x1->timewindowend);
        $this->assertSame(null, $period4x1->allocationid);
        $this->assertSame((string)strtotime('2019-01-01'), $period4x1->timecertified);
        $this->assertSame((string)strtotime('2019-01-01'), $period4x1->timefrom);
        $this->assertSame((string)strtotime('2019-03-31'), $period4x1->timeuntil);
        $this->assertSame(null, $period4x1->timerevoked);
        $this->assertSame('{"details":"history upload"}', $period4x1->evidencejson);
        $this->assertSame('1', $period4x1->first);
        $this->assertSame('0', $period4x1->recertifiable);
    }

    public function test_fix_flags(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

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

        $now = time();

        manual::assign_users($certification->id, $source->id, [$user2->id]);
        $periodx = $DB->get_record('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $periodx->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $periodx = period::override_dates((object)$dateoverrides);

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $period1 = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period1 = period::override_dates((object)$dateoverrides);
        $data = [
            'assignmentid' => $assignment1->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 4000),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 6000),
            'timeuntil' => null,
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period2 = period::add((object)$data);
        $data = [
            'assignmentid' => $assignment1->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 500),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 8000),
            'timeuntil' => (string)($now + 9000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period3 = period::add((object)$data);

        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame(null, $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', $now - 300, ['id' => $period1->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period1->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', $now - 200, ['id' => $period2->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame((string)date_util::TIMESTAMP_FOREVER, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', $now - 100, ['id' => $period3->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame((string)date_util::TIMESTAMP_FOREVER, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timerevoked', $now, ['id' => $period2->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period3->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now - 100, ['id' => $assignment1->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period3->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now + YEARSECS, ['id' => $assignment1->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((string)($now + YEARSECS), $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period3->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', null, ['id' => $assignment1->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period3->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', null, ['id' => $period3->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period1->timeuntil, $assignment1->timecertifieduntil);

        $DB->delete_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame(null, $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecreated', $now - 10, ['id' => $assignment1->id]);
        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now + WEEKSECS, ['id' => $assignment1->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((string)($now + WEEKSECS), $assignment1->timecertifiedtemp);
        $this->assertSame((string)($now - 10), $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecreated', $now - 10, ['id' => $assignment1->id]);
        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now + 10, ['id' => $assignment1->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((string)($now + 10), $assignment1->timecertifiedtemp);
        $this->assertSame((string)($assignment1->timecertifiedtemp - DAYSECS), $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', null, ['id' => $assignment1->id]);
        period::fix_flags($assignment1->certificationid, $assignment1->userid);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame(null, $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment2->timecertifiedtemp);
        $this->assertSame((string)($now + 1000), $assignment2->timecertifiedfrom);
        $this->assertSame((string)($now + 5000), $assignment2->timecertifieduntil);
    }
}
