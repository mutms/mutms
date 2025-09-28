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

namespace tool_mutenancy\phpunit\patch;

use tool_mutenancy\local\tenancy;
/**
 * Multi-tenancy upstream patch test.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core_course_category
 */
final class core_course_category_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::can_delete
     */
    public function test_can_delete(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();

        $this->setAdminUser();
        $cat = \core_course_category::get($tenant1->categoryid);

        $this->assertFalse($cat->can_delete());

        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $this->assertTrue($cat->can_delete());
    }

    /**
     * @covers ::can_delete_full
     */
    public function test_can_delete_full(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();

        $this->setAdminUser();
        $cat = \core_course_category::get($tenant1->categoryid);

        $this->assertFalse($cat->can_delete_full());

        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $this->assertTrue($cat->can_delete_full());
    }

    /**
     * @covers ::can_move_content_to
     */
    public function test_can_move_content_to(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $category = $this->getDataGenerator()->create_category();

        $this->setAdminUser();
        $cat = \core_course_category::get($tenant1->categoryid);

        $this->assertFalse($cat->can_move_content_to($category->id));

        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $this->assertTrue($cat->can_move_content_to($category->id));
    }

    /**
     * @covers ::delete_full
     */
    public function test_delete_full(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();

        $this->setAdminUser();
        $cat = \core_course_category::get($tenant1->categoryid);

        try {
            $cat->delete_full(false);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Cannot delete tenant category', $ex->getMessage());
        }

        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $cat->delete_full(false);
    }

    /**
     * @covers ::delete_move
     */
    public function test_delete_move(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $category = $this->getDataGenerator()->create_category();

        $this->setAdminUser();
        $cat = \core_course_category::get($tenant1->categoryid);

        try {
            $cat->delete_move($category->id, false);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Cannot delete tenant category', $ex->getMessage());
        }

        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $cat->delete_move($category->id, false);
    }

    /**
     * @covers ::change_parent_raw
     */
    public function test_change_parent_raw(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $category = $this->getDataGenerator()->create_category();

        $this->setAdminUser();
        $cat = \core_course_category::get($tenant1->categoryid);

        try {
            $cat->change_parent($category);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Cannot move category', $ex->getMessage());
        }

        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);
        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $cat->change_parent($category);
    }

    /**
     * @covers ::prepare_to_cache
     */
    public function test_prepare_to_cache(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $category = $this->getDataGenerator()->create_category();

        $result = $category->prepare_to_cache();
        $this->assertArrayNotHasKey('xt', $result);

        tenancy::activate();

        $category = $this->getDataGenerator()->create_category();

        $result = $category->prepare_to_cache();
        $this->assertNull($result['xt']);

        $tenant = $generator->create_tenant();
        $category = \core_course_category::get($tenant->categoryid, MUST_EXIST, true);
        $result = $category->prepare_to_cache();
        $this->assertSame((int)$tenant->id, $result['xt']);
    }

    /**
     * @covers ::wake_from_cache
     */
    public function test_wake_from_cache(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $category = $this->getDataGenerator()->create_category();

        $cache = $category->prepare_to_cache();
        $cat = \core_course_category::wake_from_cache($cache);

        tenancy::activate();

        $category = $this->getDataGenerator()->create_category();

        $cache = $category->prepare_to_cache();
        $cat = \core_course_category::wake_from_cache($cache);

        $tenant = $generator->create_tenant();
        $category = \core_course_category::get($tenant->categoryid, MUST_EXIST, true);
        $cache = $category->prepare_to_cache();
        $cat = \core_course_category::wake_from_cache($cache);
    }

    /**
     * @covers ::user_top
     */
    public function test_user_top(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user(['tenantid' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $this->setUser($user0);
        $top = \core_course_category::user_top();
        $this->assertSame(0, $top->id);

        $this->setUser($user1);
        $top = \core_course_category::user_top();
        $this->assertSame($tenant1->categoryid, $top->id);

        $this->setUser($user2);
        $top = \core_course_category::user_top();
        $this->assertSame($tenant2->categoryid, $top->id);

        $this->setUser($user0);
        tenancy::force_current_tenantid($tenant1->id);
        $top = \core_course_category::user_top();
        $this->assertSame($tenant1->categoryid, $top->id);
    }
}
