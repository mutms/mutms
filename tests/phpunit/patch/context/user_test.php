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

namespace tool_mutenancy\phpunit\patch\context;

use tool_mutenancy\local\tenancy;
use core\context\tenant;
use core\context\system;
use core\context\user;

/**
 * Multi-tenancy core modifications tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core\context\user
 */
final class user_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_possible_parent_levels
     */
    public function test_get_possible_parent_levels(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $this->assertSame([system::LEVEL], user::get_possible_parent_levels());

        tenancy::activate();
        $this->assertSame([system::LEVEL, tenant::LEVEL], user::get_possible_parent_levels());
    }

    /**
     * @covers ::instance
     */
    public function test_instance(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $user0 = $this->getDataGenerator()->create_user();
        $context = user::instance($user0->id);
        $this->assertSame(null, $context->tenantid);

        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $context = user::instance($user1->id);
        $this->assertSame((int)$tenant1->id, $context->tenantid);
    }

    /**
     * @covers ::build_paths
     */
    public function test_build_paths(): void {
        global $DB;
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        tenancy::activate();

        $syscontext = system::instance();
        $tenant1 = $generator->create_tenant();
        $tenantcontext1 = tenant::instance($tenant1->id);

        $user0 = $this->getDataGenerator()->create_user();
        $usercontext0 = user::instance($user0->id);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $usercontext1 = user::instance($user1->id);

        $DB->set_field('context', 'path', null, ['id' => $usercontext0->id]);
        $DB->set_field('context', 'path', null, ['id' => $usercontext1->id]);
        \context_helper::build_all_paths(false);
        $c0 = $DB->get_record('context', ['id' => $usercontext0->id]);
        $this->assertSame("/$syscontext->id/$usercontext0->id", $c0->path);
        $this->assertSame('2', $c0->depth);
        $c1 = $DB->get_record('context', ['id' => $usercontext1->id]);
        $this->assertSame("/$syscontext->id/$tenantcontext1->id/$usercontext1->id", $c1->path);
        $this->assertSame('3', $c1->depth);

        $DB->set_field('context', 'depth', 0, ['id' => $usercontext0->id]);
        $DB->set_field('context', 'depth', 0, ['id' => $usercontext1->id]);
        \context_helper::build_all_paths(false);
        $c0 = $DB->get_record('context', ['id' => $usercontext0->id]);
        $this->assertSame("/$syscontext->id/$usercontext0->id", $c0->path);
        $this->assertSame('2', $c0->depth);
        $c1 = $DB->get_record('context', ['id' => $usercontext1->id]);
        $this->assertSame("/$syscontext->id/$tenantcontext1->id/$usercontext1->id", $c1->path);
        $this->assertSame('3', $c1->depth);

        \context_helper::build_all_paths(true);
        $c0 = $DB->get_record('context', ['id' => $usercontext0->id]);
        $this->assertSame("/$syscontext->id/$usercontext0->id", $c0->path);
        $this->assertSame('2', $c0->depth);
        $c1 = $DB->get_record('context', ['id' => $usercontext1->id]);
        $this->assertSame("/$syscontext->id/$tenantcontext1->id/$usercontext1->id", $c1->path);
        $this->assertSame('3', $c1->depth);
    }
}
