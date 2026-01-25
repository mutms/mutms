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

namespace tool_muhome\phpunit\local;

use tool_muhome\local\page;
use core\exception\moodle_exception;
use core\exception\invalid_parameter_exception;

/**
 * Page class tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muhome\local\page
 */
final class page_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_defaults(): void {
        $syscontext = \context_system::instance();

        $expected = [
            'status' => '0',
            'guestvisible' => '0',
            'uservisible' => '1',
            'priority' => '1000',
        ];
        $this->assertSame($expected, (array)page::get_defaults(null));

        $expected = [
            'contextid' => (string)$syscontext->id,
            'status' => '0',
            'guestvisible' => '0',
            'uservisible' => '1',
            'priority' => '1000',
        ];
        $this->assertSame($expected, (array)page::get_defaults($syscontext->id));
    }

    public function test_create(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $this->setCurrentTimeStart();
        $page = page::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First page',
        ]);
        $this->assertSame('First page', $page->name);
        $this->assertSame((string)$syscontext->id, $page->contextid);
        $this->assertSame(null, $page->title);
        $this->assertSame('0', $page->priority);
        $this->assertSame((string)page::STATUS_DRAFT, $page->status);
        $this->assertSame('0', $page->guestvisible);
        $this->assertSame('0', $page->uservisible);
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

        $page = page::create((object)[
            'contextid' => $categorycontext->id,
            'name' => 'Second page',
            'title' => 'Fancy title',
            'priority' => 99,
            'guestvisible' => 1,
            'uservisible' => 0,
            'status' => page::STATUS_ACTIVE,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
        ]);
        $this->assertSame('Second page', $page->name);
        $this->assertSame((string)$categorycontext->id, $page->contextid);
        $this->assertSame('Fancy title', $page->title);
        $this->assertSame('99', $page->priority);
        $this->assertSame((string)page::STATUS_ACTIVE, $page->status);
        $this->assertSame('1', $page->guestvisible);
        $this->assertSame('0', $page->uservisible);
        $this->assertEquals($now - DAYSECS, $page->hiddenbefore);
        $this->assertEquals($now + DAYSECS, $page->hiddenafter);
        $this->assertSame('0', $page->hiddenfromtenants);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(page::get_cohortvisible_menu($page->id))
        );

        $page = page::create((object)[
            'contextid' => $categorycontext->id,
            'name' => 'Third page',
            'title' => '',
            'priority' => -10,
            'guestvisible' => 0,
            'uservisible' => 1,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
            'hiddenbefore' => 0,
            'hiddenafter' => 0,
            'hiddenfromtenants' => 1,
        ]);
        $this->assertSame('Third page', $page->name);
        $this->assertSame((string)$categorycontext->id, $page->contextid);
        $this->assertSame(null, $page->title);
        $this->assertSame('-10', $page->priority);
        $this->assertSame((string)page::STATUS_DRAFT, $page->status);
        $this->assertSame('0', $page->guestvisible);
        $this->assertSame('1', $page->uservisible);
        $this->assertSame(null, $page->hiddenbefore);
        $this->assertSame(null, $page->hiddenafter);
        $this->assertSame('1', $page->hiddenfromtenants);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(page::get_cohortvisible_menu($page->id))
        );

        try {
            page::create((object)[
                'contextid' => $syscontext->id,
                'name' => '',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (page name is required)', $ex->getMessage());
        }

        try {
            page::create((object)[
                'name' => 'xyz',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (page contextid is required)', $ex->getMessage());
        }

        try {
            page::create((object)[
                'contextid' => $coursecontext->id,
                'name' => '',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (System or category context expected)', $ex->getMessage());
        }
    }

    public function test_update(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $page = page::create((object)[
            'contextid' => $categorycontext->id,
            'name' => 'First page',
        ]);

        $now = time();

        $this->setCurrentTimeStart();
        $page = page::update((object)[
            'id' => $page->id,
            'contextid' => $categorycontext->id,
            'name' => 'Second page',
            'title' => 'Fancy title',
            'status' => page::STATUS_ACTIVE,
            'priority' => 99,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
            'hiddenfromtenants' => 1,
        ]);
        $this->assertSame('Second page', $page->name);
        $this->assertSame((string)$categorycontext->id, $page->contextid);
        $this->assertSame('Fancy title', $page->title);
        $this->assertSame('99', $page->priority);
        $this->assertSame((string)page::STATUS_ACTIVE, $page->status);
        $this->assertSame('1', $page->guestvisible);
        $this->assertSame('0', $page->uservisible);
        $this->assertEquals($now - DAYSECS, $page->hiddenbefore);
        $this->assertEquals($now + DAYSECS, $page->hiddenafter);
        $this->assertSame('1', $page->hiddenfromtenants);
        $this->assertTimeCurrent($page->timemodified);
        $this->assertEqualsCanonicalizing(
            [$cohort1->id, $cohort2->id],
            array_keys(page::get_cohortvisible_menu($page->id))
        );

        $page = page::update((object)[
            'id' => $page->id,
            'name' => 'First page',
            'title' => '',
            'status' => page::STATUS_ARCHIVED,
            'priority' => 10,
            'cohortvisible' => [$cohort3->id, $cohort2->id],
            'hiddenbefore' => 0,
            'hiddenafter' => 0,
            'hiddenfromtenants' => 0,
        ]);
        $this->assertSame('First page', $page->name);
        $this->assertSame((string)$categorycontext->id, $page->contextid);
        $this->assertSame(null, $page->title);
        $this->assertSame('10', $page->priority);
        $this->assertSame((string)page::STATUS_ARCHIVED, $page->status);
        $this->assertSame('1', $page->guestvisible);
        $this->assertSame('0', $page->uservisible);
        $this->assertSame(null, $page->hiddenbefore);
        $this->assertSame(null, $page->hiddenafter);
        $this->assertSame('0', $page->hiddenfromtenants);
        $this->assertEqualsCanonicalizing(
            [$cohort3->id, $cohort2->id],
            array_keys(page::get_cohortvisible_menu($page->id))
        );

        $page = page::update((object)[
            'id' => $page->id,
            'status' => page::STATUS_ARCHIVED,
            'uservisible' => 1,
            'cohortvisible' => [$cohort3->id, $cohort2->id],
            'hiddenbefore' => 0,
            'hiddenafter' => 0,
            'hiddenfromtenants' => 1,
        ]);
        $this->assertSame('First page', $page->name);
        $this->assertSame((string)$categorycontext->id, $page->contextid);
        $this->assertSame(null, $page->title);
        $this->assertSame('10', $page->priority);
        $this->assertSame((string)page::STATUS_ARCHIVED, $page->status);
        $this->assertSame('1', $page->guestvisible);
        $this->assertSame('1', $page->uservisible);
        $this->assertSame(null, $page->hiddenbefore);
        $this->assertSame(null, $page->hiddenafter);
        $this->assertSame('1', $page->hiddenfromtenants);
        $this->assertEqualsCanonicalizing(
            [],
            array_keys(page::get_cohortvisible_menu($page->id))
        );

        try {
            page::update((object)[
                'id' => $page->id,
                'contextid' => $syscontext->id,
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: page::update() cannot change contextid, use page::move() instead',
                $ex->getMessage()
            );
        }

        try {
            page::update((object)[
                'id' => $page->id,
                'name' => ' ',
            ]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (page name is required)', $ex->getMessage());
        }
    }

    public function test_move(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $page = page::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Some page',
        ]);

        $this->setCurrentTimeStart();
        $page = page::move($page->id, $categorycontext->id);
        $this->assertSame((string)$categorycontext->id, $page->contextid);
        $this->assertTimeCurrent($page->timemodified);

        $this->setCurrentTimeStart();
        $page = page::move($page->id, $syscontext->id);
        $this->assertSame((string)$syscontext->id, $page->contextid);
        $this->assertTimeCurrent($page->timemodified);

        $DB->set_field('tool_muhome_page', 'contextid', -1, ['id' => $page->id]);
        $page = page::move($page->id, $syscontext->id);
        $this->assertSame((string)$syscontext->id, $page->contextid);

        try {
            page::move($page->id, $coursecontext->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (System or category context expected)', $ex->getMessage());
        }
    }
    public function test_delete(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $page1 = $generator->create_page([
            'status' => page::STATUS_DRAFT,
        ]);
        $bi1 = $this->getDataGenerator()->create_block('online_users', [
            'parentcontextid' => $page1->contextid,
            'pagetypepattern' => page::PAGE_TYPE,
            'subpagepattern' => $page1->id,
        ]);
        $page2 = $generator->create_page([
            'name' => 'Some page',
            'contextid' => $categorycontext->id,
            'title' => 'Some title',
            'priority' => '777',
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $bi2 = $this->getDataGenerator()->create_block('online_users', [
            'parentcontextid' => $page2->contextid,
            'pagetypepattern' => page::PAGE_TYPE,
            'subpagepattern' => $page2->id,
        ]);
        $page3 = $generator->create_page([
            'status' => page::STATUS_ARCHIVED,
        ]);
        $bi3 = $this->getDataGenerator()->create_block('online_users', [
            'parentcontextid' => $page3->contextid,
            'pagetypepattern' => page::PAGE_TYPE,
            'subpagepattern' => $page3->id,
        ]);

        $this->assertTrue($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id, 'cohortid' => $cohort1->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id, 'cohortid' => $cohort2->id]));
        $this->assertTrue($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page1->id]));
        $this->assertTrue($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page2->id]));
        $this->assertTrue($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page3->id]));

        page::delete($page1->id);
        $this->assertFalse($DB->record_exists('tool_muhome_page', ['id' => $page1->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_page', ['id' => $page2->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_page', ['id' => $page3->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id, 'cohortid' => $cohort1->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id, 'cohortid' => $cohort2->id]));
        $this->assertFalse($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page1->id]));
        $this->assertTrue($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page2->id]));
        $this->assertTrue($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page3->id]));

        page::delete($page2->id);
        $this->assertFalse($DB->record_exists('tool_muhome_page', ['id' => $page1->id]));
        $this->assertFalse($DB->record_exists('tool_muhome_page', ['id' => $page2->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_page', ['id' => $page3->id]));
        $this->assertFalse($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id, 'cohortid' => $cohort1->id]));
        $this->assertFalse($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id, 'cohortid' => $cohort2->id]));
        $this->assertFalse($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page1->id]));
        $this->assertFalse($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page2->id]));
        $this->assertTrue($DB->record_exists('block_instances', ['pagetypepattern' => page::PAGE_TYPE, 'subpagepattern' => $page3->id]));
    }

    public function test_fix_muhome_active(): void {
        $syscontext = \context_system::instance();

        $this->assertFalse(\tool_mulib\local\mulib::is_muhome_active());
        $this->assertSame(false, get_config('tool_muhome', 'active'));

        $page1 = page::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'First page',
            'status' => page::STATUS_DRAFT,
        ]);
        $this->assertFalse(\tool_mulib\local\mulib::is_muhome_active());
        $this->assertSame('0', get_config('tool_muhome', 'active'));

        $page2 = page::create((object)[
            'contextid' => $syscontext->id,
            'name' => 'Second page',
            'status' => page::STATUS_ACTIVE,
        ]);
        $this->assertTrue(\tool_mulib\local\mulib::is_muhome_active());
        $this->assertSame('1', get_config('tool_muhome', 'active'));

        $page2 = page::update((object)[
            'id' => $page2->id,
            'status' => page::STATUS_ARCHIVED,
        ]);
        $this->assertFalse(\tool_mulib\local\mulib::is_muhome_active());
        $this->assertSame('0', get_config('tool_muhome', 'active'));

        $page2 = page::update((object)[
            'id' => $page2->id,
            'status' => page::STATUS_ACTIVE,
        ]);
        $this->assertTrue(\tool_mulib\local\mulib::is_muhome_active());
        $this->assertSame('1', get_config('tool_muhome', 'active'));

        page::delete($page2->id);
        $this->assertFalse(\tool_mulib\local\mulib::is_muhome_active());
        $this->assertSame('0', get_config('tool_muhome', 'active'));
    }

    public function test_get_statuses_menu(): void {
        $this->assertSame(
            [
                0 => 'Draft',
                1 => 'Active',
                2 => 'Archived',
            ],
            page::get_statuses_menu()
        );
    }

    public function test_get_cohortvisible_menu(): void {
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 2']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort 3']);

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $page1 = $generator->create_page([
        ]);
        $page2 = $generator->create_page([
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $page3 = $generator->create_page([
            'uservisible' => 0,
            'cohortvisible' => [$cohort3->id],
        ]);

        $this->assertSame([], page::get_cohortvisible_menu($page1->id));
        $this->assertSame([$cohort1->id => $cohort1->name, $cohort2->id => $cohort2->name], page::get_cohortvisible_menu($page2->id));
        $this->assertSame([$cohort3->id => $cohort3->name], page::get_cohortvisible_menu($page3->id));
    }

    public function test_get_url(): void {
        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $page1 = $generator->create_page([]);
        $page2 = $generator->create_page([]);

        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muhome/?pageid=' . $page1->id,
            page::get_url($page1->id)->out(false)
        );

        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muhome/',
            page::get_url(null)->out(false)
        );
    }

    public function test_get_my_pages(): void {
        global $DB;

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort3->id, $user2->id);

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $now = time();

        $page1 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 0,
            'uservisible' => 1,
        ]);
        $page2 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 0,
        ]);
        $page3 = $generator->create_page([
            'status' => page::STATUS_DRAFT,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $page4 = $generator->create_page([
            'status' => page::STATUS_ARCHIVED,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $page5 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $page6 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
            'hiddenbefore' => $now - DAYSECS,
            'hiddenafter' => $now + DAYSECS,
            'hidddenfromtenants' => 1,
        ]);
        $page7 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
            'hiddenbefore' => $now + DAYSECS,
            'hiddenafter' => 0,
        ]);
        $page8 = $generator->create_page([
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
            'hiddenbefore' => 0,
            'hiddenafter' => $now - DAYSECS,
        ]);

        $this->setUser(null);
        $this->assertSame(
            [$page2->id => $page2->name, $page5->id => $page5->name, $page6->id => $page6->name],
            page::get_my_pages(false)
        );

        $this->setGuestUser();
        $this->assertSame(
            [$page2->id => $page2->name, $page5->id => $page5->name, $page6->id => $page6->name],
            page::get_my_pages(false)
        );

        $this->setAdminUser();
        $this->assertSame(
            [$page1->id => $page1->name, $page6->id => $page6->name],
            page::get_my_pages(false)
        );

        $this->setUser($user1);
        $this->assertSame(
            [$page1->id => $page1->name, $page5->id => $page5->name, $page6->id => $page6->name],
            page::get_my_pages(false)
        );

        $this->setUser($user2);
        $this->assertSame(
            [$page1->id => $page1->name, $page6->id => $page6->name],
            page::get_my_pages(false)
        );

        $page6 = page::update((object)[
            'id' => $page6->id,
            'priority' => 99999999,
        ]);
        $this->assertSame(
            [$page6->id => $page6->name, $page1->id => $page1->name],
            page::get_my_pages(false)
        );

        $this->assertSame(
            [$page6->id => $page6->name, $page1->id => $page1->name],
            page::get_my_pages(true)
        );

        $DB->delete_records('tool_muhome_page', ['id' => $page6->id]);
        $this->assertSame(
            [$page6->id => $page6->name, $page1->id => $page1->name],
            page::get_my_pages(true)
        );
        $this->assertSame(
            [$page1->id => $page1->name],
            page::get_my_pages(false)
        );
    }

    public function test_get_my_pages_tenant(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $syscontext = \context_system::instance();
        $category0 = $this->getDataGenerator()->create_category();
        $catcontext0 = \context_coursecat::instance($category0->id);

        $tenant1 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $user0 = $this->getDataGenerator()->create_user([]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $page0a = $generator->create_page([
            'contextid' => $catcontext0->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
            'hiddenfromtenants' => 0,
        ]);
        $page0b = $generator->create_page([
            'contextid' => $syscontext->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
            'hiddenfromtenants' => 1,
        ]);
        $page1 = $generator->create_page([
            'contextid' => $catcontext1->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $page2 = $generator->create_page([
            'contextid' => $catcontext2->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $page2b = $generator->create_page([
            'contextid' => $catcontext2->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
            'hiddenfromtenants' => 1,
        ]);

        $this->setUser($user0);

        \tool_mutenancy\local\tenancy::switch(null);
        $this->assertSame(
            [$page0a->id => $page0a->name, $page0b->id => $page0b->name],
            page::get_my_pages(true)
        );

        \tool_mutenancy\local\tenancy::switch($tenant1->id);
        $this->assertSame(
            [$page0a->id => $page0a->name, $page1->id => $page1->name],
            page::get_my_pages(true)
        );

        \tool_mutenancy\local\tenancy::switch($tenant2->id);
        $this->assertSame(
            [$page0a->id => $page0a->name, $page2->id => $page2->name],
            page::get_my_pages(true)
        );

        $this->setUser($user2);

        \tool_mutenancy\local\tenancy::switch($tenant2->id);
        $this->assertSame(
            [$page0a->id => $page0a->name, $page2->id => $page2->name],
            page::get_my_pages(true)
        );
    }

    public function test_pre_course_category_delete(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $categorycontext2 = \context_coursecat::instance($category2->id);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $page0 = $generator->create_page([
            'contextid' => $syscontext->id,
            'uservisible' => 1,
            'status' => page::STATUS_DRAFT,
        ]);
        $page1 = $generator->create_page([
            'contextid' => $categorycontext1->id,
            'status' => page::STATUS_DRAFT,
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id, $cohort2->id],
        ]);
        $page2 = $generator->create_page([
            'contextid' => $categorycontext2->id,
            'status' => page::STATUS_DRAFT,
            'uservisible' => 0,
            'cohortvisible' => [$cohort3->id],
        ]);

        page::pre_course_category_delete($category1->id);

        $this->assertFalse($DB->record_exists('tool_muhome_page', ['id' => $page1->id]));
        $this->assertFalse($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page1->id]));
        $this->assertEquals($page0, $DB->get_record('tool_muhome_page', ['id' => $page0->id]));
        $this->assertEquals($page2, $DB->get_record('tool_muhome_page', ['id' => $page2->id]));
        $this->assertTrue($DB->record_exists('tool_muhome_cohortvisible', ['pageid' => $page2->id]));
    }

    public function test_archive_tenant_pages(): void {
        global $DB;

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            return;
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $syscontext = \context_system::instance();
        $category0 = $this->getDataGenerator()->create_category();
        $catcontext0 = \context_coursecat::instance($category0->id);

        $tenant1 = $tenantgenerator->create_tenant();
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $user0 = $this->getDataGenerator()->create_user([]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $page0 = $generator->create_page([
            'contextid' => $catcontext0->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
            'hiddenfromtenants' => 0,
        ]);
        $page1 = $generator->create_page([
            'contextid' => $catcontext1->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);
        $page2 = $generator->create_page([
            'contextid' => $catcontext2->id,
            'status' => page::STATUS_ACTIVE,
            'guestvisible' => 1,
            'uservisible' => 1,
        ]);

        page::archive_tenant_pages($tenant2->id);
        $page2->status = (string)page::STATUS_ARCHIVED;
        $this->assertEquals($page0, $DB->get_record('tool_muhome_page', ['id' => $page0->id]));
        $this->assertEquals($page1, $DB->get_record('tool_muhome_page', ['id' => $page1->id]));
        $this->assertEquals($page2, $DB->get_record('tool_muhome_page', ['id' => $page2->id]));
    }
}
