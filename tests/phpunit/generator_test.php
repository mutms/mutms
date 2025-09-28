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

namespace tool_mucertify\phpunit;

use tool_mucertify\local\certification;

/**
 * Certification generator test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create_certification(): void {
        global $DB;

        $syscontext = \context_system::instance();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        $this->assertInstanceOf('tool_mucertify_generator', $generator);

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->setCurrentTimeStart();
        $certification = $generator->create_certification([]);
        $this->assertInstanceOf('stdClass', $certification);
        $this->assertSame((string)$syscontext->id, $certification->contextid);
        $this->assertSame('Certification 1', $certification->fullname);
        $this->assertSame('crt1', $certification->idnumber);
        $this->assertSame('', $certification->description);
        $this->assertSame('1', $certification->descriptionformat);
        $this->assertSame('[]', $certification->presentationjson);
        $this->assertSame('0', $certification->publicaccess);
        $this->assertSame('0', $certification->archived);
        $this->assertSame(null, $certification->programid1);
        $this->assertSame(null, $certification->programid2);
        $this->assertSame(null, $certification->recertify);
        $this->assertSame((array)certification::get_periods_defaults(), json_decode($certification->periodsjson, true));
        $this->assertTimeCurrent($certification->timecreated);

        $sources = $DB->get_records('tool_mucertify_source', ['certificationid' => $certification->id]);
        $this->assertCount(0, $sources);

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $program3 = $programgenerator->create_program();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Some other certification',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'publicaccess' => '1',
            'archived' => '1',
            'sources' => ['manual' => []],
            'cohorts' => [$cohort1->id, $cohort2->name],
            'programid1' => $program1->id,
            'programid2' => $program2->id,
            'recertify' => '77777',
        ];

        $this->setCurrentTimeStart();
        $certification = $generator->create_certification($data);
        $this->assertInstanceOf('stdClass', $certification);
        $this->assertSame((string)$catcontext->id, $certification->contextid);
        $this->assertSame($data->fullname, $certification->fullname);
        $this->assertSame($data->idnumber, $certification->idnumber);
        $this->assertSame($data->description, $certification->description);
        $this->assertSame($data->descriptionformat, $certification->descriptionformat);
        $this->assertSame('[]', $certification->presentationjson);
        $this->assertSame($data->publicaccess, $certification->publicaccess);
        $this->assertSame($data->archived, $certification->archived);
        $this->assertSame($program1->id, $certification->programid1);
        $this->assertSame($program2->id, $certification->programid2);
        $this->assertSame('77777', $certification->recertify);
        $this->assertSame((array)certification::get_periods_defaults(), json_decode($certification->periodsjson, true));
        $this->assertTimeCurrent($certification->timecreated);

        $sources = $DB->get_records('tool_mucertify_source', ['certificationid' => $certification->id]);
        $this->assertCount(1, $sources);
        $source = reset($sources);
        $this->assertSame('manual', $source->type);
        $cs = $DB->get_records('tool_mucertify_cohort', ['certificationid' => $certification->id], 'cohortid ASC');
        $this->assertCount(2, $cs);
        $cs = array_values($cs);
        $this->assertSame($cohort1->id, $cs[0]->cohortid);
        $this->assertSame($cohort2->id, $cs[1]->cohortid);

        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $certification = $generator->create_certification(['category' => $category2->name]);
        $this->assertSame((string)$catcontext2->id, $certification->contextid);

        $data = (object)[
            'cohorts' => "$cohort1->name, $cohort2->id",
        ];
        $certification = $generator->create_certification($data);
        $cs = $DB->get_records('tool_mucertify_cohort', ['certificationid' => $certification->id]);
        $this->assertCount(2, $cs);
        $this->assertCount(2, $cs);
        $cs = array_values($cs);
        $this->assertSame($cohort1->id, $cs[0]->cohortid);
        $this->assertSame($cohort2->id, $cs[1]->cohortid);

        $data = (object)[
            'recertify' => '1234',
            'programid1' => $program1->id,
            'program2' => $program2->idnumber,
            'periods_resettype1' => \tool_muprog\local\course_reset::RESETTYPE_FULL,
            'periods_due1' => '9876',
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P1M'],
        ];
        $certification = $generator->create_certification($data);
        $periods = json_decode($certification->periodsjson, true);
        $this->assertSame($data->recertify, $certification->recertify);
        $this->assertSame($program1->id, $certification->programid1);
        $this->assertSame($program2->id, $certification->programid2);
        $this->assertSame($data->periods_resettype1, $periods['resettype1']);
        $this->assertSame($data->periods_due1, $periods['due1']);
        $this->assertSame($data->periods_windowend1, $periods['windowend1']);
    }

    public function test_create_certification_assignment(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        $this->assertInstanceOf('tool_mucertify_generator', $generator);

        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program1 = $programgenerator->create_program();

        $certification1 = $generator->create_certification(['programid1' => $program1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $this->setCurrentTimeStart();
        $assignment1 = $generator->create_certification_assignment(
            ['certificationid' => $certification1->id, 'userid' => $user1->id]
        );
        $this->assertSame($user1->id, $assignment1->userid);
        $this->assertSame($certification1->id, $assignment1->certificationid);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertTimeCurrent($assignment1->timecreated);

        $assignment2 = $generator->create_certification_assignment(
            ['certification' => $certification1->fullname, 'user' => $user2->username]
        );
        $this->assertSame($user2->id, $assignment2->userid);
        $this->assertSame($certification1->id, $assignment2->certificationid);
        $this->assertSame(null, $assignment2->timecertifiedtemp);

        $now = time();
        $data = (object)[
            'certificationid' => $certification1->id,
            'userid' => $user3->id,
            'timecreated' => (string)($now - DAYSECS),
            'timecertifiedtemp' => (string)($now + WEEKSECS),
        ];
        $assignment3 = $generator->create_certification_assignment($data);
        $this->assertSame($user3->id, $assignment3->userid);
        $this->assertSame($certification1->id, $assignment3->certificationid);
        $this->assertSame($data->timecertifiedtemp, $assignment3->timecertifiedtemp);
        $this->assertSame($data->timecreated, $assignment3->timecreated);

        $data = (object)[
            'certificationid' => $certification1->id,
            'userid' => $user4->id,
            'noperiod' => '1',
        ];
        $assignment4 = $generator->create_certification_assignment($data);
        $this->assertFalse($DB->record_exists(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user4->id]
        ));

        $data = (object)[
            'certificationid' => $certification1->id,
            'userid' => $user5->id,
            'noperiod' => '',
        ];
        $assignment5 = $generator->create_certification_assignment($data);
        $this->assertTrue($DB->record_exists(
            'tool_mucertify_period',
            ['certificationid' => $certification1->id, 'userid' => $user5->id]
        ));
    }
}
