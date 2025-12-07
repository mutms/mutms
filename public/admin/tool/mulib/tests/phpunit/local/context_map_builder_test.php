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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\context_map_builder;
use context, context_system, context_tenant, context_user, context_coursecat, context_course;

/**
 * Context map builder tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\context_map_builder
 */
final class context_map_builder_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            \tool_mutenancy\local\tenancy::deactivate();
        }
    }

    public function test_upsert_context_parent(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $usercontext1 = context_user::instance($user1->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');
        context_map_builder::upsert_context_parent($usercontext1->id, $syscontext->id);
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', []));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $usercontext1->id, 'parentcontextid' => $syscontext->id]));

        context_map_builder::upsert_context_parent($usercontext1->id, $syscontext->id);
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', []));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $usercontext1->id, 'parentcontextid' => $syscontext->id]));

        context_map_builder::upsert_context_parent($coursecontext1->id, $usercontext1->id);
        $this->assertSame(2, $DB->count_records('tool_mulib_context_parent', []));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $usercontext1->id, 'parentcontextid' => $syscontext->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $coursecontext1->id, 'parentcontextid' => $usercontext1->id]));

        context_map_builder::upsert_context_parent($coursecontext1->id, $usercontext1->id);
        $this->assertSame(2, $DB->count_records('tool_mulib_context_parent', []));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $usercontext1->id, 'parentcontextid' => $syscontext->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $coursecontext1->id, 'parentcontextid' => $usercontext1->id]));

        context_map_builder::upsert_context_parent($coursecontext1->id, $categorycontext1->id);
        $this->assertSame(2, $DB->count_records('tool_mulib_context_parent', []));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $usercontext1->id, 'parentcontextid' => $syscontext->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_parent', ['contextid' => $coursecontext1->id, 'parentcontextid' => $categorycontext1->id]));
    }

    public function test_upsert_context_map(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $syscontext = context_system::instance();
        $usercontext1 = context_user::instance($user1->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $coursecontext1 = context_course::instance($course1->id);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');
        $parentcontextids = $coursecontext1->get_parent_context_ids(true);
        $parentcontextids = array_map('intval', $parentcontextids);
        $this->assertSame(
            [$coursecontext1->id, $categorycontext2->id, $categorycontext1->id, $syscontext->id],
            $parentcontextids
        );
        context_map_builder::upsert_context_map($coursecontext1->id, $parentcontextids);
        $this->assert_map_exists($coursecontext1, $parentcontextids);
        $this->assertSame(4, $DB->count_records('tool_mulib_context_map', ['contextid' => $coursecontext1->id]));

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');
        $parentcontextids = $coursecontext1->get_parent_context_ids(true);
        $parentcontextids = array_map('intval', $parentcontextids);
        unset($parentcontextids[0]);
        context_map_builder::upsert_context_map($coursecontext1->id, $parentcontextids);
        $this->assert_map_exists($coursecontext1, $parentcontextids);

        $DB->set_field('tool_mulib_context_map', 'relatedcontextid', $usercontext1->id, ['contextid' => $coursecontext1->id]);
        context_map_builder::upsert_context_map($coursecontext1->id, $parentcontextids);
        $this->assert_map_exists($coursecontext1, $parentcontextids);

        $this->assertDebuggingNotCalled();
        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');
        $parentcontextids = $coursecontext1->get_parent_context_ids(true);
        unset($parentcontextids[2]);
        context_map_builder::upsert_context_map($coursecontext1->id, $parentcontextids);
        $this->assertDebuggingCalled('relatedcontextid with distance 2 is missing for context ' . $coursecontext1->id);
        $this->assertSame(0, $DB->count_records('tool_mulib_context_map', ['contextid' => $coursecontext1->id]));

        $parentcontextids = $coursecontext1->get_parent_context_ids(true);
        $parentcontextids[] = 3;
        context_map_builder::upsert_context_map($coursecontext1->id, $parentcontextids);
        $this->assertDebuggingCalled("Top parent of context {$coursecontext1->id} must be a system context");
        $this->assertSame(0, $DB->count_records('tool_mulib_context_map', ['contextid' => $coursecontext1->id]));

        $parentcontextids = $coursecontext1->get_parent_context_ids(true);
        $parentcontextids[0] = $usercontext1->id;
        context_map_builder::upsert_context_map($coursecontext1->id, $parentcontextids);
        $this->assertDebuggingCalled("relatedcontextid with distance 0 must match own contextid {$coursecontext1->id}");
        $this->assertSame(0, $DB->count_records('tool_mulib_context_map', ['contextid' => $coursecontext1->id]));
    }

    public function test_parent_purge_deleted(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $syscontext = context_system::instance();
        $usercontext1 = context_user::instance($user1->id);
        $usercontext2 = context_user::instance($user2->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);

        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::upsert_context_parent($usercontext1->id, $syscontext->id);
        context_map_builder::upsert_context_parent($usercontext2->id, $syscontext->id);
        context_map_builder::upsert_context_parent($coursecontext1->id, $categorycontext1->id);
        context_map_builder::upsert_context_parent($coursecontext2->id, $categorycontext2->id);
        context_map_builder::upsert_context_parent($categorycontext2->id, $syscontext->id);

        $prevparents = self::fetch_mulib_context_parents();
        $DB->insert_records('tool_mulib_context_parent', [
            ['contextid' => $syscontext->id, 'parentcontextid' => $usercontext1->id],
            ['contextid' => -1, 'parentcontextid' => $syscontext->id],
            ['contextid' => -2, 'parentcontextid' => $syscontext->id],
        ]);

        context_map_builder::parent_purge_deleted();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());
    }

    public function test_parent_user_fix(): void {
        global $DB;

        $admin = get_admin();
        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $guestcontext = context_user::instance($guest->id);
        $admincontext = context_user::instance($admin->id);
        $usercontext1 = context_user::instance($user1->id);
        $usercontext2 = context_user::instance($user2->id);
        $usercontext3 = context_user::instance($user3->id);

        delete_user($user3);
        delete_user($user4);

        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::upsert_context_parent($guestcontext->id, $syscontext->id);
        context_map_builder::upsert_context_parent($admincontext->id, $syscontext->id);
        context_map_builder::upsert_context_parent($usercontext1->id, $syscontext->id);
        context_map_builder::upsert_context_parent($usercontext2->id, $syscontext->id);
        $prevparents = self::fetch_mulib_context_parents();
        $this->assertCount(4, $prevparents);

        context_map_builder::upsert_context_parent($usercontext3->id, $syscontext->id);
        context_map_builder::upsert_context_parent($usercontext2->id, $usercontext1->id);
        context_map_builder::parent_user_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());

        $prevparents = self::fetch_mulib_context_parents(true);
        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::parent_user_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
    }

    public function test_parent_tenant_fix(): void {
        global $DB;
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multi-tenancy not available');
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant();

        $syscontext = context_system::instance();
        $tenantcontext1 = context_tenant::instance($tenant1->id);
        $tenantcontext2 = context_tenant::instance($tenant2->id);
        $tenantcontext3 = context_tenant::instance($tenant3->id);

        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::upsert_context_parent($tenantcontext1->id, $syscontext->id);
        context_map_builder::upsert_context_parent($tenantcontext2->id, $syscontext->id);
        context_map_builder::upsert_context_parent($tenantcontext3->id, $syscontext->id);
        $prevparents = self::fetch_mulib_context_parents();
        $this->assertCount(3, $prevparents);

        context_map_builder::upsert_context_parent($tenantcontext3->id, $tenantcontext1->id);
        context_map_builder::parent_tenant_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());

        $prevparents = self::fetch_mulib_context_parents(true);
        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::parent_tenant_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
    }

    public function test_parent_tenant_user_fix(): void {
        global $DB;
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multi-tenancy not available');
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant();
        $tenant4 = $tenantgenerator->create_tenant();
        $admin = get_admin();
        $guest = guest_user();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id]);
        $user4 = $this->getDataGenerator()->create_user(['tenantid' => $tenant4->id]);

        $syscontext = context_system::instance();
        $tenantcontext1 = context_tenant::instance($tenant1->id);
        $tenantcontext2 = context_tenant::instance($tenant2->id);
        $tenantcontext3 = context_tenant::instance($tenant3->id);
        $tenantcontext4 = context_tenant::instance($tenant4->id);
        $guestcontext = context_user::instance($guest->id);
        $admincontext = context_user::instance($admin->id);
        $usercontext0 = context_user::instance($user0->id);
        $usercontext1 = context_user::instance($user1->id);
        $usercontext2 = context_user::instance($user2->id);
        $usercontext3 = context_user::instance($user3->id);
        $usercontext4 = context_user::instance($user4->id);

        delete_user($user0);
        delete_user($user4);

        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::upsert_context_parent($guestcontext->id, $syscontext->id);
        context_map_builder::upsert_context_parent($admincontext->id, $syscontext->id);
        context_map_builder::upsert_context_parent($usercontext1->id, $tenantcontext1->id);
        context_map_builder::upsert_context_parent($usercontext2->id, $tenantcontext2->id);
        context_map_builder::upsert_context_parent($usercontext3->id, $tenantcontext3->id);
        $prevparents = self::fetch_mulib_context_parents();
        $this->assertCount(5, $prevparents);

        context_map_builder::upsert_context_parent($usercontext0->id, $syscontext->id);
        context_map_builder::upsert_context_parent($usercontext4->id, $tenantcontext4->id);
        context_map_builder::upsert_context_parent($admincontext->id, $tenantcontext1->id);
        context_map_builder::upsert_context_parent($usercontext1->id, $syscontext->id);
        context_map_builder::parent_tenant_user_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());

        $prevparents = self::fetch_mulib_context_parents(true);
        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::parent_tenant_user_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
    }

    public function test_parent_category_fix(): void {
        global $DB;

        $category0 = \core_course_category::get_default();
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category3 = $this->getDataGenerator()->create_category(['parent' => $category2->id]);

        $syscontext = context_system::instance();
        $categorycontext0 = context_coursecat::instance($category0->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $categorycontext3 = context_coursecat::instance($category3->id);

        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::upsert_context_parent($categorycontext0->id, $syscontext->id);
        context_map_builder::upsert_context_parent($categorycontext1->id, $syscontext->id);
        context_map_builder::upsert_context_parent($categorycontext2->id, $categorycontext1->id);
        context_map_builder::upsert_context_parent($categorycontext3->id, $categorycontext2->id);
        $prevparents = self::fetch_mulib_context_parents();
        $this->assertCount(4, $prevparents);

        context_map_builder::upsert_context_parent($categorycontext0->id, $categorycontext1->id);
        context_map_builder::upsert_context_parent($categorycontext2->id, $syscontext->id);

        context_map_builder::parent_category_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());

        $prevparents = self::fetch_mulib_context_parents(true);
        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::parent_category_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
    }

    public function test_parent_course_fix(): void {
        global $DB;

        $category0 = \core_course_category::get_default();
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category3 = $this->getDataGenerator()->create_category(['parent' => $category2->id]);
        $site = $DB->get_record('course', []);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category3->id]);

        $syscontext = context_system::instance();
        $categorycontext0 = context_coursecat::instance($category0->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $categorycontext3 = context_coursecat::instance($category3->id);
        $sitecontext = context_course::instance($site->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $coursecontext3 = context_course::instance($course3->id);

        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::upsert_context_parent($sitecontext->id, $syscontext->id);
        context_map_builder::upsert_context_parent($coursecontext1->id, $categorycontext1->id);
        context_map_builder::upsert_context_parent($coursecontext2->id, $categorycontext2->id);
        context_map_builder::upsert_context_parent($coursecontext3->id, $categorycontext3->id);
        $prevparents = self::fetch_mulib_context_parents();
        $this->assertCount(4, $prevparents);

        context_map_builder::upsert_context_parent($sitecontext->id, $categorycontext1->id);
        context_map_builder::upsert_context_parent($coursecontext1->id, $syscontext->id);
        context_map_builder::upsert_context_parent($coursecontext2->id, $categorycontext3->id);

        context_map_builder::parent_course_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());

        $prevparents = self::fetch_mulib_context_parents(true);
        $DB->delete_records('tool_mulib_context_parent', []);
        context_map_builder::parent_course_fix();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
    }

    public function test_map_purge_deleted(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $category0 = $DB->get_record('course_categories', []);
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $syscontext = context_system::instance();
        $categorycontext0 = context_coursecat::instance($category0->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $usercontext1 = context_user::instance($user1->id);
        $usercontext2 = context_user::instance($user2->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);

        $DB->delete_records('tool_mulib_context_parent', []);
        $DB->delete_records('tool_mulib_context_map', []);

        context_map_builder::parent_user_fix();
        context_map_builder::parent_category_fix();
        context_map_builder::parent_course_fix();
        $this->assert_has_parent($syscontext, $categorycontext0);
        $this->assert_has_parent($syscontext, $categorycontext1);
        $this->assert_has_parent($syscontext, $categorycontext2);
        $this->assert_has_parent($syscontext, $usercontext1);
        $this->assert_has_parent($syscontext, $usercontext2);
        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assert_has_parent($categorycontext2, $coursecontext2);

        $contextids = $DB->get_fieldset('context', 'id');
        foreach ($contextids as $contextid) {
            if ($contextid == $syscontext->id) {
                continue;
            }
            $context = context::instance_by_id($contextid);
            context_map_builder::upsert_context_map($contextid, $context->get_parent_context_ids(true));
            $this->assert_map_exists($context->id, $context->get_parent_context_ids(true));
        }

        $DB->delete_records_select(
            'tool_mulib_context_parent',
            "contextid NOT IN ($usercontext1->id, $categorycontext1->id, $coursecontext1->id, $coursecontext2->id)"
        );

        context_map_builder::map_purge_deleted();

        $this->assert_map_exists($usercontext1->id, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1->id, $coursecontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext2->id, $coursecontext2->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1->id, $categorycontext1->get_parent_context_ids(true));
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $usercontext2->id]));
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $categorycontext2->id]));
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $categorycontext0->id]));
    }

    public function test_map_distance_0(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $usercontext1 = context_user::instance($user1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $DB->delete_records('tool_mulib_context_parent', []);

        context_map_builder::parent_user_fix();
        context_map_builder::parent_category_fix();
        context_map_builder::parent_course_fix();

        $DB->delete_records_select(
            'tool_mulib_context_parent',
            "contextid NOT IN ($categorycontext1->id, $usercontext1->id, $coursecontext1->id)"
        );
        $DB->delete_records('tool_mulib_context_map', []);

        context_map_builder::map_distance_0();
        $this->assertSame(4, $DB->count_records('tool_mulib_context_map', []));
        $this->assertSame(4, $DB->count_records('tool_mulib_context_map', ['distance' => 0]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 0, 'contextid' => $syscontext->id, 'relatedcontextid' => $syscontext->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 0, 'contextid' => $categorycontext1->id, 'relatedcontextid' => $categorycontext1->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 0, 'contextid' => $usercontext1->id, 'relatedcontextid' => $usercontext1->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 0, 'contextid' => $coursecontext1->id, 'relatedcontextid' => $coursecontext1->id]));
    }

    public function test_map_distance_1(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category0 = $this->getDataGenerator()->create_category();
        $category1 = $this->getDataGenerator()->create_category(['parent' => $category0->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $categorycontext0 = context_coursecat::instance($category0->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $usercontext1 = context_user::instance($user1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $DB->delete_records('tool_mulib_context_parent', []);

        context_map_builder::parent_user_fix();
        context_map_builder::parent_category_fix();
        context_map_builder::parent_course_fix();

        $DB->delete_records_select(
            'tool_mulib_context_parent',
            "contextid NOT IN ($categorycontext0->id, $categorycontext1->id,$usercontext1->id, $coursecontext1->id)"
        );
        $DB->delete_records('tool_mulib_context_map', []);

        context_map_builder::map_distance_1();
        $this->assertSame(4, $DB->count_records('tool_mulib_context_map', []));
        $this->assertSame(4, $DB->count_records('tool_mulib_context_map', ['distance' => 1]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 1, 'contextid' => $categorycontext0->id, 'relatedcontextid' => $syscontext->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 1, 'contextid' => $categorycontext1->id, 'relatedcontextid' => $categorycontext0->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 1, 'contextid' => $usercontext1->id, 'relatedcontextid' => $syscontext->id]));
        $this->assertSame(1, $DB->count_records('tool_mulib_context_map', ['distance' => 1, 'contextid' => $coursecontext1->id, 'relatedcontextid' => $categorycontext1->id]));
    }

    public function test_map_distance_n(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category0 = $this->getDataGenerator()->create_category();
        $category1 = $this->getDataGenerator()->create_category(['parent' => $category0->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $categorycontext0 = context_coursecat::instance($category0->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $usercontext1 = context_user::instance($user1->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);

        $DB->delete_records('tool_mulib_context_parent', []);

        context_map_builder::parent_user_fix();
        context_map_builder::parent_category_fix();
        context_map_builder::parent_course_fix();

        $DB->delete_records_select(
            'tool_mulib_context_parent',
            "contextid NOT IN ($categorycontext0->id, $categorycontext1->id, $usercontext1->id, $coursecontext1->id, $coursecontext2->id)"
        );
        $DB->delete_records('tool_mulib_context_map', []);
        context_map_builder::map_distance_0();
        context_map_builder::map_distance_1();

        context_map_builder::map_distance_n(2);
        $this->assertSame(14, $DB->count_records('tool_mulib_context_map', []));
        $this->assertSame(6, $DB->count_records('tool_mulib_context_map', ['distance' => 0]));
        $this->assertSame(5, $DB->count_records('tool_mulib_context_map', ['distance' => 1]));
        $this->assertSame(3, $DB->count_records('tool_mulib_context_map', ['distance' => 2]));
        $this->assert_map_exists($syscontext, $syscontext->get_parent_context_ids(true));
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext0, $categorycontext0->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));

        context_map_builder::map_distance_n(3);
        $this->assertSame(16, $DB->count_records('tool_mulib_context_map', []));
        $this->assertSame(6, $DB->count_records('tool_mulib_context_map', ['distance' => 0]));
        $this->assertSame(5, $DB->count_records('tool_mulib_context_map', ['distance' => 1]));
        $this->assertSame(3, $DB->count_records('tool_mulib_context_map', ['distance' => 2]));
        $this->assertSame(2, $DB->count_records('tool_mulib_context_map', ['distance' => 3]));
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext0, $categorycontext0->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext2, $coursecontext2->get_parent_context_ids(true));

        context_map_builder::map_distance_n(4);
        $this->assertSame(16, $DB->count_records('tool_mulib_context_map', []));
        $this->assertSame(6, $DB->count_records('tool_mulib_context_map', ['distance' => 0]));
        $this->assertSame(5, $DB->count_records('tool_mulib_context_map', ['distance' => 1]));
        $this->assertSame(3, $DB->count_records('tool_mulib_context_map', ['distance' => 2]));
        $this->assertSame(2, $DB->count_records('tool_mulib_context_map', ['distance' => 3]));
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext2, $coursecontext2->get_parent_context_ids(true));
    }

    public function test_map_purge_above_system(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $usercontext1 = context_user::instance($user1->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');

        context_map_builder::build();
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $prevcount = $DB->count_records('tool_mulib_context_map', []);

        context_map_builder::map_purge_above_system();
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));

        $DB->insert_record('tool_mulib_context_map', ['contextid' => $usercontext1->id, 'relatedcontextid' => $coursecontext1->id, 'distance' => 3]);
        context_map_builder::map_purge_above_system();
        $this->assertSame($prevcount, $DB->count_records('tool_mulib_context_map', []));
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));

        $DB->insert_record('tool_mulib_context_map', ['contextid' => $usercontext1->id, 'relatedcontextid' => $syscontext->id, 'distance' => 2]);
        $DB->insert_record('tool_mulib_context_map', ['contextid' => $usercontext1->id, 'relatedcontextid' => $syscontext->id, 'distance' => 3]);
        context_map_builder::map_purge_above_system();
        $this->assertSame($prevcount, $DB->count_records('tool_mulib_context_map', []));
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
    }

    public function test_build(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $usercontext1 = context_user::instance($user1->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $prevparents = self::fetch_mulib_context_parents(true);
        $prevmaps = self::fetch_mulib_context_maps(true);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');

        context_map_builder::build();
        $this->assert_map_exists($syscontext, [$syscontext->id]);
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
        $this->assertEquals($prevmaps, self::fetch_mulib_context_maps(true));

        $prevparents = self::fetch_mulib_context_parents();
        $prevmaps = self::fetch_mulib_context_maps();

        context_map_builder::build();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());
        $this->assertEquals($prevmaps, self::fetch_mulib_context_maps());

        $this->getDataGenerator()->create_module('page', ['course' => $course1->id]);
        context_map_builder::build();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());
        $this->assertEquals($prevmaps, self::fetch_mulib_context_maps());

        $user2 = $this->getDataGenerator()->create_user();
        $course2 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        delete_course($course2, false);
        delete_user($user2);
        context_map_builder::build();
        $this->assertEquals($prevparents, self::fetch_mulib_context_parents());
        $this->assertEquals($prevmaps, self::fetch_mulib_context_maps());
    }

    public function test_analyze(): void {
        context_map_builder::build();
        context_map_builder::analyze();
    }

    public function test_build_random(): void {
        global $DB;

        $syscontext = context_system::instance();

        $users = [];
        for ($i = 0; $i < 20; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[$user->id] = $user;
        }

        $categories = [0];
        for ($i = 0; $i < 20; $i++) {
            $parent = $categories[rand(0, array_key_last($categories))];
            $category = $this->getDataGenerator()->create_category(['parent' => $parent]);
            $categories[] = $category->id;
        }
        array_shift($categories);

        for ($i = 0; $i < 50; $i++) {
            $categoryid = $categories[rand(0, array_key_last($categories))];
            $course = $this->getDataGenerator()->create_course(['category' => $categoryid]);
            for ($j = 0; $j < 10; $j++) {
                $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
            }
        }

        $prevparents = self::fetch_mulib_context_parents(true);
        $prevmaps = self::fetch_mulib_context_maps(true);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');

        context_map_builder::build();

        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
        $this->assertEquals($prevmaps, self::fetch_mulib_context_maps(true));

        $allcontextids = $DB->get_fieldset('context', 'id', []);
        for ($i = 0; $i < 20; $i++) {
            $contextid1 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $contextid2 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $maxd = (int)$DB->get_field('tool_mulib_context_map', 'MAX(distance)', ['contextid' => $contextid1]);
            $d = rand(0, $maxd);
            $DB->set_field('tool_mulib_context_map', 'relatedcontextid', $contextid2, ['contextid' => $contextid1, 'distance' => $d]);
        }
        for ($i = 0; $i < 20; $i++) {
            $contextid1 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $maxd = (int)$DB->get_field('tool_mulib_context_map', 'MAX(distance)', ['contextid' => $contextid1]);
            $d = rand(0, $maxd);
            $DB->delete_records('tool_mulib_context_map', ['contextid' => $contextid1, 'distance' => $d]);
        }
        for ($i = 0; $i < 10; $i++) {
            $contextid1 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $maxd = (int)$DB->get_field('tool_mulib_context_map', 'MAX(distance)', ['contextid' => $contextid1]);
            $d = rand(0, $maxd);
            $DB->set_field('tool_mulib_context_map', 'relatedcontextid', -1, ['contextid' => $contextid1, 'distance' => $d]);
        }
        for ($i = 0; $i < 20; $i++) {
            $contextid1 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $contextid2 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $maxd = (int)$DB->get_field('tool_mulib_context_map', 'MAX(distance)', ['contextid' => $contextid1]);
            $d = rand($maxd + 1, $maxd + 10);
            if (!$DB->record_exists('tool_mulib_context_map', ['contextid' => $contextid1, 'distance' => $d])) {
                $DB->insert_record('tool_mulib_context_map', ['contextid' => $contextid1, 'distance' => $d, 'relatedcontextid' => $contextid2]);
            }
        }
        for ($i = 0; $i < 10; $i++) {
            $contextid1 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $maxd = (int)$DB->get_field('tool_mulib_context_map', 'MAX(distance)', ['contextid' => $contextid1]);
            $d = rand(0, $maxd);
            $DB->set_field('tool_mulib_context_map', 'relatedcontextid', $syscontext->id, ['contextid' => $contextid1, 'distance' => $d]);
        }

        for ($i = 0; $i < 5; $i++) {
            $contextid1 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $contextid2 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $contextid3 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $contextid4 = $allcontextids[rand(0, array_key_last($allcontextids))];
            $DB->delete_records('tool_mulib_context_parent', ['contextid' => $contextid1]);
            $DB->delete_records('tool_mulib_context_parent', ['parentcontextid' => $contextid2]);
            $DB->set_field('tool_mulib_context_parent', 'parentcontextid', $contextid4, ['contextid' => $contextid3]);
        }

        context_map_builder::build();

        $this->assertEquals($prevparents, self::fetch_mulib_context_parents(true));
        $this->assertEquals($prevmaps, self::fetch_mulib_context_maps(true));
    }

    public function test_map_check(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $usercontext1 = context_user::instance($user1->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');
        context_map_builder::build();

        $this->assertSame(null, context_map_builder::map_check());

        $DB->set_field('tool_mulib_context_map', 'relatedcontextid', $coursecontext1->id, ['contextid' => $usercontext1->id, 'distance' => 1]);
        $this->assertSame(
            'Context map error - following contexts do not have system as top parent: ' . $usercontext1->id,
            context_map_builder::map_check()
        );
    }

    public function test_build_tenant(): void {
        global $DB;
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multi-tenancy not available');
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $tenatcontext = context_tenant::instance($tenant1->id);
        $usercontext0 = context_user::instance($user0->id);
        $usercontext1 = context_user::instance($user1->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $DB->delete_records('tool_mulib_context_parent');
        $DB->delete_records('tool_mulib_context_map');

        context_map_builder::build();
        $this->assert_map_exists($tenatcontext, $tenatcontext->get_parent_context_ids(true));
        $this->assert_map_exists($usercontext0, $usercontext0->get_parent_context_ids(true));
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));

        context_map_builder::build();
        $this->assert_map_exists($tenatcontext, $tenatcontext->get_parent_context_ids(true));
        $this->assert_map_exists($usercontext0, $usercontext0->get_parent_context_ids(true));
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
    }

    public function test_delete_context_parent(): void {
        global $DB;

        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);

        context_map_builder::build();
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext2, $coursecontext2->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $categorycontext1);
        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assert_has_parent($categorycontext1, $coursecontext2);
        $this->assertTrue($DB->record_exists('tool_mulib_context_parent', ['contextid' => $coursecontext2->id]));

        context_map_builder::delete_context_parent($coursecontext2->id);
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext2, $coursecontext2->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $categorycontext1);
        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assertFalse($DB->record_exists('tool_mulib_context_parent', ['contextid' => $coursecontext2->id]));
    }

    public function test_delete_context_map(): void {
        global $DB;

        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);

        context_map_builder::build();
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext2, $coursecontext2->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $categorycontext1);
        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assert_has_parent($categorycontext1, $coursecontext2);
        $this->assertTrue($DB->record_exists('tool_mulib_context_map', ['contextid' => $coursecontext2->id]));

        context_map_builder::delete_context_map($coursecontext2->id);
        $this->assert_map_exists($categorycontext1, $categorycontext1->get_parent_context_ids(true));
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $categorycontext1);
        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assert_has_parent($categorycontext1, $coursecontext2);
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $coursecontext2->id]));
    }

    public function test_tenant_events(): void {
        global $DB;

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multi-tenancy not available');
        }

        $syscontext = context_system::instance();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext1 = context_tenant::instance($tenant1->id);
        $tenantcontext2 = context_tenant::instance($tenant2->id);

        $this->assertEquals([$tenantcontext1->id, $syscontext->id], $tenantcontext1->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $tenantcontext1);
        $this->assert_map_exists($tenantcontext1, $tenantcontext1->get_parent_context_ids(true));
        $this->assertTrue($DB->record_exists('tool_mulib_context_parent', ['contextid' => $tenantcontext1->id]));
        $this->assertTrue($DB->record_exists('tool_mulib_context_map', ['contextid' => $tenantcontext1->id]));

        \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $this->assertFalse($DB->record_exists('tool_mulib_context_parent', ['contextid' => $tenantcontext1->id]));
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $tenantcontext1->id]));
    }

    public function test_user_events(): void {
        global $DB;

        $syscontext = context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $usercontext1 = context_user::instance($user1->id);
        $this->assertEquals([$usercontext1->id, $syscontext->id], $usercontext1->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $usercontext1);
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));
        $this->assertTrue($DB->record_exists('tool_mulib_context_parent', ['contextid' => $usercontext1->id]));
        $this->assertTrue($DB->record_exists('tool_mulib_context_map', ['contextid' => $usercontext1->id]));

        user_delete_user($user1);
        $this->assertFalse($DB->record_exists('tool_mulib_context_parent', ['contextid' => $usercontext1->id]));
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $usercontext1->id]));
    }

    public function test_user_events_tenant(): void {
        global $DB;

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multi-tenancy not available');
        }

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = context_system::instance();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenantcontext1 = context_tenant::instance($tenant1->id);
        $tenantcontext2 = context_tenant::instance($tenant2->id);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $usercontext0 = context_user::instance($user0->id);
        $this->assertEquals([$usercontext0->id, $syscontext->id], $usercontext0->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $usercontext0);
        $this->assert_map_exists($usercontext0, $usercontext0->get_parent_context_ids(true));

        $usercontext1 = context_user::instance($user1->id);
        $this->assertEquals([$usercontext1->id, $tenantcontext1->id, $syscontext->id], $usercontext1->get_parent_context_ids(true));
        $this->assert_has_parent($tenantcontext1, $usercontext1);
        $this->assert_map_exists($usercontext1, $usercontext1->get_parent_context_ids(true));

        user_delete_user($user1);
        $this->assertFalse($DB->record_exists('tool_mulib_context_parent', ['contextid' => $usercontext1->id]));
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $usercontext1->id]));

        \tool_mutenancy\local\user::allocate($user0->id, $tenant1->id);
        $usercontext0 = context_user::instance($user0->id);
        $this->assertEquals([$usercontext0->id, $tenantcontext1->id, $syscontext->id], $usercontext0->get_parent_context_ids(true));
        $this->assert_has_parent($tenantcontext1, $usercontext0);
        $this->assert_map_exists($usercontext0, $usercontext0->get_parent_context_ids(true));

        \tool_mutenancy\local\user::allocate($user0->id, $tenant2->id);
        $usercontext0 = context_user::instance($user0->id);
        $this->assertEquals([$usercontext0->id, $tenantcontext2->id, $syscontext->id], $usercontext0->get_parent_context_ids(true));
        $this->assert_has_parent($tenantcontext2, $usercontext0);
        $this->assert_map_exists($usercontext0, $usercontext0->get_parent_context_ids(true));

        \tool_mutenancy\local\user::allocate($user0->id, 0);
        $usercontext0 = context_user::instance($user0->id);
        $this->assertEquals([$usercontext0->id, $syscontext->id], $usercontext0->get_parent_context_ids(true));
        $this->assert_has_parent($syscontext, $usercontext0);
        $this->assert_map_exists($usercontext0, $usercontext0->get_parent_context_ids(true));

        $usercontext2 = context_user::instance($user2->id);
        $this->assertEquals([$usercontext2->id, $tenantcontext2->id, $syscontext->id], $usercontext2->get_parent_context_ids(true));
        $this->assert_has_parent($tenantcontext2, $usercontext2);
        $this->assert_map_exists($usercontext2, $usercontext2->get_parent_context_ids(true));
    }

    public function test_category_events(): void {
        global $DB;

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $category3 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category3->id]);

        $syscontext = context_system::instance();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $categorycontext3 = context_coursecat::instance($category3->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $coursecontext3 = context_course::instance($course3->id);

        $this->assert_has_parent($syscontext, $categorycontext1);
        $this->assert_has_parent($syscontext, $categorycontext2);
        $this->assert_has_parent($categorycontext1, $categorycontext3);
        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assert_has_parent($categorycontext2, $coursecontext2);
        $this->assert_has_parent($categorycontext3, $coursecontext3);

        $coursecat = \core_course_category::get($category3->id, MUST_EXIST, true);
        $coursecat->update(['id' => $category3->id, 'parent' => $category2->id], null);

        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $categorycontext3 = context_coursecat::instance($category3->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $coursecontext3 = context_course::instance($course3->id);

        $this->assertEquals(
            [$coursecontext3->id, $categorycontext3->id, $categorycontext2->id, $syscontext->id],
            $coursecontext3->get_parent_context_ids(true)
        );
        $this->assert_has_parent($syscontext, $categorycontext1);
        $this->assert_has_parent($syscontext, $categorycontext2);
        $this->assert_has_parent($categorycontext2, $categorycontext3);
        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assert_has_parent($categorycontext2, $coursecontext2);
        $this->assert_has_parent($categorycontext3, $coursecontext3);

        $courseids = [];
        for ($i = 1; $i <= context_map_builder::MAX_CATEGORY_UPDATE_COUNT + 2; $i++) {
            $courseids[$i] = $this->getDataGenerator()->create_course(['category' => $category3->id])->id;
        }

        $coursecat = \core_course_category::get($category3->id, MUST_EXIST, true);
        $coursecat->update(['id' => $category3->id, 'parent' => $category2->id], null);

        foreach ($courseids as $courseid) {
            $context = context_course::instance($courseid);
            $this->assert_has_parent($categorycontext3, $context);
            $this->assert_map_exists($context, $context->get_parent_context_ids(true));
        }
    }

    public function test_course_events(): void {
        global $DB;

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $syscontext = context_system::instance();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $coursecontext1 = context_course::instance($course1->id);

        $coursecontext2 = context_course::instance($course2->id);
        $this->assertEquals([$coursecontext2->id, $categorycontext1->id, $syscontext->id], $coursecontext2->get_parent_context_ids(true));
        $this->assert_has_parent($categorycontext1, $coursecontext2);
        $this->assert_map_exists($coursecontext2, $coursecontext2->get_parent_context_ids(true));
        $this->assertTrue($DB->record_exists('tool_mulib_context_parent', ['contextid' => $coursecontext2->id]));
        $this->assertTrue($DB->record_exists('tool_mulib_context_map', ['contextid' => $coursecontext2->id]));

        delete_course($course2->id, false);
        $this->assertFalse($DB->record_exists('tool_mulib_context_parent', ['contextid' => $coursecontext2->id]));
        $this->assertFalse($DB->record_exists('tool_mulib_context_map', ['contextid' => $coursecontext2->id]));

        $this->assert_has_parent($categorycontext1, $coursecontext1);
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));

        $course1->category = $category2->id;
        update_course($course1);
        $coursecontext1 = context_course::instance($course1->id);
        $this->assertEquals([$coursecontext1->id, $categorycontext2->id, $syscontext->id], $coursecontext1->get_parent_context_ids(true));
        $this->assert_has_parent($categorycontext2, $coursecontext1);
        $this->assert_map_exists($coursecontext1, $coursecontext1->get_parent_context_ids(true));
    }

    /**
     * Check the context parent is marching the provided value
     * and that it is present int the parent cache table.
     *
     * @param int|context $parentcontextid
     * @param int|context $contextid
     * @return void
     */
    public function assert_has_parent(int|context $parentcontextid, int|context $contextid): void {
        global $DB;
        if (is_object($contextid)) {
            $contextid = $contextid->id;
        }
        if (is_object($parentcontextid)) {
            $parentcontextid = $parentcontextid->id;
        }
        $this->assertTrue($DB->record_exists('tool_mulib_context_parent', ['contextid' => $contextid, 'parentcontextid' => $parentcontextid]));
        $context = context::instance_by_id($contextid);
        $this->assertEquals($context->get_parent_context()->id, $parentcontextid);
    }

    /**
     * Assert that context map relations exist.
     *
     * @param int|context $contextid
     * @param array $relations array of distance=>relatedcontextid, 0 distance is added automatically
     * @return void
     */
    public function assert_map_exists(int|context $contextid, array $relations): void {
        global $DB;
        if ($contextid instanceof context) {
            $contextid = $contextid->id;
        }
        $syscontext = context_system::instance();

        if (!isset($relations[0])) {
            $relations[0] = $contextid;
        }
        $maxdistance = max(array_keys($relations));
        if ($contextid == $syscontext->id) {
            if ($maxdistance != 0) {
                throw new \Exception('0 distance expected for system context');
            }
        } else if ($maxdistance < 1) {
            throw new \Exception('at least 1 distance entry expected');
        }
        for ($i = 0; $i <= $maxdistance; $i++) {
            if (empty($relations[$i])) {
                throw new \Exception("missing related context for distance $i");
            }
        }
        if ($relations[0] != $contextid) {
            throw new \Exception("relatedcontextid with distance 0 must match own contextid $contextid");
        }
        if ($relations[$maxdistance] != $syscontext->id) {
            throw new \Exception("Top parent of context $contextid must be a system context");
        }

        foreach ($relations as $distance => $relatedcontextid) {
            $this->assertTrue($DB->record_exists('tool_mulib_context_map', [
                'contextid' => $contextid,
                'distance' => $distance,
                'relatedcontextid' => $relatedcontextid,
            ]));
        }

        $this->assertFalse($DB->record_exists_select(
            'tool_mulib_context_map',
            "contextid = ? AND (distance > ? OR distance < 0)",
            [$contextid, $maxdistance]
        ));
    }

    /**
     * Return context map parents in id-less form.
     * @param bool $withoutids
     * @return array
     */
    public static function fetch_mulib_context_parents(bool $withoutids = false): array {
        global $DB;
        if ($withoutids) {
            $fields = 'contextid, parentcontextid';
        } else {
            $fields = 'id, contextid, parentcontextid';
        }
        $rs = $DB->get_recordset('tool_mulib_context_parent', [], 'contextid ASC', $fields);
        $result = iterator_to_array($rs, false);
        $rs->close();
        return $result;
    }

    /**
     * Return context maps in id-less form.
     * @param bool $withoutids
     * @return array
     */
    public static function fetch_mulib_context_maps(bool $withoutids = false): array {
        global $DB;
        if ($withoutids) {
            $fields = 'contextid, distance, relatedcontextid';
        } else {
            $fields = 'id, contextid, distance, relatedcontextid';
        }
        $rs = $DB->get_recordset('tool_mulib_context_map', [], 'contextid ASC, distance ASC', $fields);
        $result = iterator_to_array($rs, false);
        $rs->close();
        return $result;
    }
}
