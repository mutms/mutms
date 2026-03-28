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
use tool_muhome\local\management;

/**
 * Management class tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muhome\local\management
 */
final class management_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_setup_index_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $page1 = $generator->create_page(['contextid' => $syscontext->id]);
        $page2 = $generator->create_page(['contextid' => $categorycontext1->id]);
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/muhome/management/index.php'),
            $syscontext
        );

        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/muhome/management/index.php', ['contextid' => $categorycontext1->id]),
            $categorycontext1
        );
    }

    public function test_get_page_hint(): void {
        global $PAGE;

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $page1 = $generator->create_page(['contextid' => $syscontext->id]);
        $bi1 = $this->getDataGenerator()->create_block('online_users', [
            'parentcontextid' => $page1->contextid,
            'pagetypepattern' => page::PAGE_TYPE,
            'subpagepattern' => $page1->id,
        ]);
        $page2 = $generator->create_page(['contextid' => $syscontext->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muhome:manage', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $user1->id, $syscontext->id);

        $this->setUser($user1);
        $this->assertSame(null, management::get_page_hint($page1, $syscontext));
        $this->assertStringContainsString(
            'to start adding blocks and personalizing this custom home page',
            management::get_page_hint($page2, $syscontext)
        );

        $this->setUser($user2);
        $this->assertSame(null, management::get_page_hint($page1, $syscontext));
        $this->assertSame(null, management::get_page_hint($page2, $syscontext));
    }
}
