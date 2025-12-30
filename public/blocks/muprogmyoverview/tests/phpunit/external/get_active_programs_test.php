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

namespace block_muprogmyoverview\phpunit\external;

use block_muprogmyoverview\external\get_active_programs;

/**
 * My programs external API tests.
 *
 * @group       MuTMS
 * @package     block_muprogmyoverview
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \block_muprogmyoverview\external\get_active_programs
 */
final class get_active_programs_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $now = time();
        $shortdate = get_string('strftimedatetimeshort', 'langconfig');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $program1 = $generator->create_program([
            'fullname' => 'Program 1',
            'idnumber' => 'CP1',
            'description' => 'First description',
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'Program 2',
            'idnumber' => 'BP2',
            'description' => 'Second description',
            'contextid' => $catcontext1->id,
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Program 3',
            'idnumber' => 'AP3',
            'description' => 'Third description',
        ]);
        $program4 = $generator->create_program();
        $program5 = $generator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $allocation1x1 = $generator->create_program_allocation([
            'userid' => $user1->id,
            'programid' => $program1->id,
            'timestart' => $now + DAYSECS,
            'timedue' => $now + WEEKSECS,
        ]);
        $allocation1x2 = $generator->create_program_allocation([
            'userid' => $user1->id,
            'programid' => $program2->id,
            'timestart' => $now - DAYSECS,
            'timedue' => $now + DAYSECS,
        ]);
        $allocation1x3 = $generator->create_program_allocation([
            'userid' => $user1->id,
            'programid' => $program3->id,
            'timestart' => $now - 2 * DAYSECS,
            'timeend' => $now - DAYSECS,
        ]);
        $allocation1x4 = $generator->create_program_allocation(['userid' => $user1->id, 'programid' => $program4->id]);
        $allocation1x4 = \tool_muprog\local\source\base::allocation_archive($allocation1x4->id);

        $allocation2x1 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program1->id]);
        $allocation2x2 = $generator->create_program_allocation(['userid' => $user2->id, 'programid' => $program2->id]);

        $this->setUser($user1);

        set_user_preference('block_muprogmyoverview_hidden_program_' . $program2->id, 1);
        \block_muprogmyoverview\external\set_favourite_program::execute($program1->id, 1);

        $result = get_active_programs::execute('allincludinghidden', 0, 0, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(3, $result['programs']);
        $this->assertSame(3, $result['nextoffset']);
        $p1 = (object)$result['programs'][0];
        $this->assertSame((int)$program1->id, $p1->id);
        $this->assertSame('Program 1', $p1->fullname);
        $this->assertSame('CP1', $p1->idnumber);
        $this->assertSame(null, $p1->description);
        $this->assertSame(userdate($now + DAYSECS, $shortdate), $p1->startdate);
        $this->assertSame(userdate($now + WEEKSECS, $shortdate), $p1->duedate);
        $this->assertSame(null, $p1->enddate);
        $this->assertSame('System', $p1->programcategory);
        $this->assertStringStartsWith('data:image/svg+xml;base64', $p1->programimage);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/muprog/my/program.php?id={$program1->id}",
            $p1->viewurl
        );
        $this->assertSame('Not open yet', $p1->status);
        $this->assertSame('bg-light text-dark', $p1->statusclass);
        $this->assertSame(false, $p1->hasprogress);
        $this->assertSame(0, $p1->progress);
        $this->assertSame(true, $p1->isfavourite);
        $this->assertSame(false, $p1->hidden);
        $this->assertSame(false, $p1->showprogramcategory);
        $p2 = (object)$result['programs'][1];
        $this->assertSame('Program 2', $p2->fullname);
        $this->assertSame('BP2', $p2->idnumber);
        $this->assertSame(null, $p2->description);
        $this->assertSame(userdate($now - DAYSECS, $shortdate), $p2->startdate);
        $this->assertSame(userdate($now + DAYSECS, $shortdate), $p2->duedate);
        $this->assertSame(null, $p2->enddate);
        $this->assertSame($category1->name, $p2->programcategory);
        $this->assertStringStartsWith('data:image/svg+xml;base64', $p2->programimage);
        $this->assertSame(
            "https://www.example.com/moodle/admin/tool/muprog/my/program.php?id={$program2->id}",
            $p2->viewurl
        );
        $this->assertSame('Open', $p2->status);
        $this->assertSame('bg-primary', $p2->statusclass);
        $this->assertSame(false, $p2->hasprogress);
        $this->assertSame(null, $p2->progress);
        $this->assertSame(false, $p2->isfavourite);
        $this->assertSame(true, $p2->hidden);
        $this->assertSame(false, $p2->showprogramcategory);
        $this->assertSame((int)$program2->id, $p2->id);
        $p3 = (object)$result['programs'][2];
        $this->assertSame((int)$program3->id, $p3->id);

        $result = get_active_programs::execute('allincludinghidden', 0, 0, 'title', 'am 1', 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $this->assertSame(1, $result['nextoffset']);
        $p1 = (object)$result['programs'][0];
        $this->assertSame((int)$program1->id, $p1->id);
        $this->assertSame(null, $p1->description);

        $result = get_active_programs::execute('allincludinghidden', 0, 0, 'title', 'CP1', 1);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $this->assertSame(1, $result['nextoffset']);
        $p1 = (object)$result['programs'][0];
        $this->assertSame((int)$program1->id, $p1->id);
        $this->assertSame('First description', $p1->description);

        $result = get_active_programs::execute('allincludinghidden', 0, 0, 'title', 'Second', 1);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $this->assertSame(1, $result['nextoffset']);
        $p2 = (object)$result['programs'][0];
        $this->assertSame((int)$program2->id, $p2->id);
        $this->assertSame('Second description', $p2->description);

        $result = get_active_programs::execute('allincludinghidden', 0, 0, 'idnumber', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(3, $result['programs']);
        $this->assertSame(3, $result['nextoffset']);
        $p3 = (object)$result['programs'][0];
        $p2 = (object)$result['programs'][1];
        $p1 = (object)$result['programs'][2];
        $this->assertSame((int)$program1->id, $p1->id);
        $this->assertSame((int)$program2->id, $p2->id);
        $this->assertSame((int)$program3->id, $p3->id);

        $result = get_active_programs::execute('allincludinghidden', 0, 0, 'duedate', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(3, $result['programs']);
        $this->assertSame(3, $result['nextoffset']);
        $p2 = (object)$result['programs'][0];
        $p1 = (object)$result['programs'][1];
        $p3 = (object)$result['programs'][2];
        $this->assertSame((int)$program1->id, $p1->id);
        $this->assertSame((int)$program2->id, $p2->id);
        $this->assertSame((int)$program3->id, $p3->id);

        $result = get_active_programs::execute('hidden', 0, 0, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $p2 = (object)$result['programs'][0];
        $this->assertSame((int)$program2->id, $p2->id);

        $result = get_active_programs::execute('past', 0, 0, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $p3 = (object)$result['programs'][0];
        $this->assertSame((int)$program3->id, $p3->id);

        $result = get_active_programs::execute('future', 0, 0, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $p1 = (object)$result['programs'][0];
        $this->assertSame((int)$program1->id, $p1->id);

        $result = get_active_programs::execute('inprogress', 0, 0, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(0, $result['programs']);

        unset_user_preference('block_muprogmyoverview_hidden_program_' . $program2->id);

        $result = get_active_programs::execute('inprogress', 0, 0, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $p2 = (object)$result['programs'][0];
        $this->assertSame((int)$program2->id, $p2->id);

        $result = get_active_programs::execute('favourites', 0, 0, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $p1 = (object)$result['programs'][0];
        $this->assertSame((int)$program1->id, $p1->id);

        $result = get_active_programs::execute('allincludinghidden', 1, 1, 'title', null, 0);
        $result = get_active_programs::validate_parameters(get_active_programs::execute_returns(), $result);
        $this->assertCount(1, $result['programs']);
        $this->assertSame(2, $result['nextoffset']);
        $p2 = (object)$result['programs'][0];
        $this->assertSame((int)$program2->id, $p2->id);
    }
}
