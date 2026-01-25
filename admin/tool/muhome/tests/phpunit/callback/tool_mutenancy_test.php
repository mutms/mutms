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

namespace tool_muhome\phpunit\callback;

use tool_muhome\local\page;

/**
 * Multitenancy callbacks class tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muhome\callback\tool_mutenancy
 */
final class tool_mutenancy_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multitenancy not available');
        }

        $this->resetAfterTest();
    }

    public function test_hook_tenant_management_menu(): void {
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
        assign_capability('tool/muhome:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user2->id, $catcontext->id);

        $this->setUser($user1);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $tenantnode->children->count());

        $this->setUser($user2);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $tenantnode->children->count());

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');
        $page1 = $generator->create_page(['status' => page::STATUS_DRAFT]);

        $this->setUser($user1);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $tenantnode->children->count());

        $this->setUser($user2);
        $hook = new \tool_mutenancy\hook\tenant_management_menu($tenantnode, $tenant, $tenantcontext, $catcontext);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $tenantnode->children->count());

        $page2 = $generator->create_page(['status' => page::STATUS_ACTIVE]);

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
        $this->assertSame('Home pages management', $node->text);
        $programurl = new \moodle_url('/admin/tool/muhome/management/index.php', ['contextid' => $catcontext->id]);
        $this->assertSame($programurl->out(false), $node->action()->out(false));
    }

    public function test_hook_pre_tenant_delete(): void {
        global $DB;

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

        $tenant2 = \tool_mutenancy\local\tenant::archive($tenant2->id);
        \tool_mutenancy\local\tenant::delete($tenant2->id);

        $page2x = $DB->get_record('tool_muhome_page', ['id' => $page2->id]);
        $page2->status = (string)page::STATUS_ARCHIVED;
        $page2->timemodified = $page2x->timemodified;
        $this->assertEquals($page2, $page2x);

        $this->assertEquals($page0, $DB->get_record('tool_muhome_page', ['id' => $page0->id]));
        $this->assertEquals($page1, $DB->get_record('tool_muhome_page', ['id' => $page1->id]));
    }
}
