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

namespace tool_muhome\phpunit;

use tool_muhome\local\page;

/**
 * Custom home pages generator tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_muhome_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::create_page
     */
    public function test_create_page(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');
        $this->assertInstanceOf('tool_muhome_generator', $generator);

        $this->setCurrentTimeStart();
        $page = $generator->create_page([]);
        $this->assertSame('Custom page 1', $page->name);
        $this->assertSame((string)$syscontext->id, $page->contextid);
        $this->assertSame(null, $page->title);
        $this->assertSame('1000', $page->priority);
        $this->assertSame((string)page::STATUS_DRAFT, $page->status);
        $this->assertSame('0', $page->guestvisible);
        $this->assertSame('1', $page->uservisible);
        $this->assertSame(null, $page->hiddenbefore);
        $this->assertSame(null, $page->hiddenafter);
        $this->assertSame('0', $page->hiddenfromtenants);
        $this->assertTimeCurrent($page->timecreated);
        $this->assertTimeCurrent($page->timemodified);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(page::get_cohortvisible_menu($page->id))
        );

        $now = time();

        $page = $generator->create_page([
            'name' => 'Some page',
            'contextid' => $categorycontext->id,
            'title' => 'Some title',
            'priority' => '777',
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
        ]);
        $this->assertSame('Some page', $page->name);
        $this->assertSame((string)$categorycontext->id, $page->contextid);
        $this->assertSame('Some title', $page->title);
        $this->assertSame('777', $page->priority);
        $this->assertSame((string)page::STATUS_ACTIVE, $page->status);
        $this->assertSame('1', $page->guestvisible);
        $this->assertSame('0', $page->uservisible);
        $this->assertSame((string)($now - DAYSECS), $page->hiddenbefore);
        $this->assertSame((string)($now + DAYSECS), $page->hiddenafter);
        $this->assertSame('0', $page->hiddenfromtenants);
        $this->assertTimeCurrent($page->timecreated);
        $this->assertTimeCurrent($page->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(page::get_cohortvisible_menu($page->id))
        );

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        $page = $generator->create_page([]);
        $this->assertSame('0', $page->hiddenfromtenants);

        $page = $generator->create_page([
            'hiddenfromtenants' => 1,
        ]);
        $this->assertSame('1', $page->hiddenfromtenants);
    }
}
