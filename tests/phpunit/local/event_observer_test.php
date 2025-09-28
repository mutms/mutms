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
use tool_muprog\local\allocation;
use tool_muprog\local\program;

/**
 * Certification event observer test.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\event_observer
 */
final class event_observer_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_allocation_completed(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course([]);
        $course1context = \context_course::instance($course1->id);
        $program1 = $programgenerator->create_program(['sources' => ['mucertify' => []]]);
        $top1 = program::load_content($program1->id);
        $item1x1 = $top1->append_course($top1, $course1->id);

        $data = [
            'programid1' => $program1->id,
            'sources' => ['manual' => []],
        ];
        $certification1 = $generator->create_certification($data);
        $source1 = $DB->get_record(
            'tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        manual::assign_users($certification1->id, $source1->id, [$user1->id]);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertTrue(\is_enrolled($course1context, $user1));

        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertSame(null, $period1->timecertified);

        $this->setCurrentTimeStart();
        $data = (object)[
            'allocationid' => $allocation->id,
            'timecompleted' => time() - 10,
            'itemid' => $item1x1->get_id(),
            'evidencetimecompleted' => null,
        ];
        allocation::update_item_completion($data);
        $period1 = $DB->get_record(
            'tool_mucertify_period',
            ['userid' => $user1->id, 'certificationid' => $certification1->id],
            '*',
            MUST_EXIST
        );
        $this->assertTimeCurrent($period1->timecertified);
    }
}
