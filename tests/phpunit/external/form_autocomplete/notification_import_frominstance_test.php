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

namespace tool_muprog\phpunit\external\form_autocomplete;

use tool_muprog\external\form_autocomplete\notification_import_frominstance;

/**
 * External API for form import program notification
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\form_autocomplete\notification_import_frominstance
 */
final class notification_import_frominstance_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program([
            'fullname' => 'Hokus',
            'idnumber' => 'p1',
            'description' => 'some desc 1',
            'descriptionformat' => \FORMAT_MARKDOWN,
            'publicaccess' => 1,
            'archived' => 0,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
            'cohorts' => [$cohort1->id],
        ]);
        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program1->id]);
        $generator->create_program_notification(['notificationtype' => 'endsoon', 'programid' => $program1->id]);

        $program2 = $generator->create_program([
            'fullname' => 'Pokus',
            'idnumber' => 'p2',
            'description' => '<b>some desc 2</b>',
            'descriptionformat' => \FORMAT_HTML,
            'publicaccess' => 0,
            'archived' => 0,
            'contextid' => $catcontext1->id,
            'sources' => ['manual' => [], 'cohort' => []],
            'cohorts' => [$cohort1->id, $cohort2->id],
        ]);
        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program2->id]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog3',
            'idnumber' => 'p3',
            'publicaccess' => 1,
            'archived' => 1,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ]);
        $generator->create_program_notification(['notificationtype' => 'allocation', 'programid' => $program3->id]);
        $program4 = $generator->create_program([
            'fullname' => 'Prog4',
            'idnumber' => 'p4',
            'publicaccess' => 1,
            'archived' => 0,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $editroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editroleid, $syscontext);
        $cloneroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:clone', CAP_ALLOW, $cloneroleid, $syscontext);

        role_assign($editroleid, $user1->id, $syscontext->id);
        role_assign($cloneroleid, $user1->id, $syscontext->id);
        $this->setUser($user1);
        $response = notification_import_frominstance::execute('', $program1->id);
        $results = notification_import_frominstance::clean_returnvalue(
            notification_import_frominstance::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $this->assertCount(2, $results['list']);
        $this->assertSame((int)$program2->id, $results['list'][0]['value']);
        $this->assertSame((int)$program3->id, $results['list'][1]['value']);

        role_assign($editroleid, $user2->id, $syscontext->id);
        role_assign($cloneroleid, $user2->id, $catcontext1->id);
        $this->setUser($user2);
        $response = notification_import_frominstance::execute('', $program1->id);
        $results = notification_import_frominstance::clean_returnvalue(
            notification_import_frominstance::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $this->assertCount(1, $results['list']);
        $this->assertSame((int)$program2->id, $results['list'][0]['value']);
    }
}
