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

namespace tool_muprog\phpunit\callback;

/**
 * Multi-tenancy callback test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\callback\tool_mutenancy
 */
final class tool_mutenancy_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multitenancy not avilable');
        }

        $this->resetAfterTest();
    }

    public function test_tenant_management_menu(): void {
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant = $tenantgenerator->create_tenant();
        /** @var \context_tenant $tenantcontext */
        $tenantcontext = \context_tenant::instance($tenant->id);
        $catcontext = \context_coursecat::instance($tenant->categoryid);
        $tenantnode = new \navigation_node('test node');
        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user2->id, $catcontext->id);

        $this->setUser($user1);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $tenantnode->children->count());

        $this->setUser($user2);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $tenantnode->children->count());

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->setUser($user1);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $tenantnode->children->count());

        $this->setUser($user2);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(1, $tenantnode->children->count());
        $node = $tenantnode->children->last();
        $this->assertInstanceOf(\navigation_node::class, $node);
        $this->assertSame('Program management', $node->text);
        $programurl = new \moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $catcontext->id]);
        $this->assertSame($programurl->out(false), $node->action()->out(false));
    }
}
