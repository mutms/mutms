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

namespace tool_mutrain\phpunit\local;

use tool_mutrain\local\management;

/**
 * Credit framework management helper test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\local\management
 */
final class management_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_management_url(): void {
        global $DB;

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $admin = get_admin();
        $guest = guest_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        role_assign($managerrole->id, $manager->id, $catcontext2->id);

        $viewer = $this->getDataGenerator()->create_user();
        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutrain:viewframeworks', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $viewer->id, $catcontext1->id);

        $this->setUser(null);
        $this->assertNull(management::get_management_url());

        $this->setUser($guest);
        $this->assertNull(management::get_management_url());

        $this->setUser($admin);
        $expected = new \core\url('/admin/tool/mutrain/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($manager);
        $this->assertNull(management::get_management_url());

        $this->setUser($viewer);
        $this->assertNull(management::get_management_url());
    }

    public function test_get_management_url_tenant(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multitenancy not available');
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant = $tenantgenerator->create_tenant();
        $tenantcatcontext = \context_coursecat::instance($tenant->categoryid);
        $syscontext = \context_system::instance();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutrain:viewframeworks', CAP_ALLOW, $viewerroleid, $syscontext);

        $viewer0 = $this->getDataGenerator()->create_user();
        role_assign($viewerroleid, $viewer0->id, $syscontext->id);

        $viewer1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        role_assign($viewerroleid, $viewer1->id, $tenantcatcontext->id);

        $this->setUser($viewer0);
        $expected = new \core\url('/admin/tool/mutrain/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($viewer1);
        $expected = new \core\url('/admin/tool/mutrain/management/index.php', ['contextid' => $tenantcatcontext->id]);
        $this->assertSame((string)$expected, (string)management::get_management_url());
    }

    public function test_get_framework_search_query(): void {
        global $DB;

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $framework1 = $generator->create_framework(['name' => 'First framework', 'idnumber' => 'PRG1', 'description' => 'prvni popis']);
        $framework2 = $generator->create_framework(['name' => 'Second framework', 'idnumber' => 'PRG2', 'description' => 'druhy popis']);
        $framework3 = $generator->create_framework(['name' => 'Third framework', 'idnumber' => 'PR3', 'description' => 'treti popis', 'contextid' => $catcontext1->id]);

        [$search, $params] = management::get_framework_search_query(null, 'First', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query(null, 'First', '');
        $frameworkids = $DB->get_fieldset_sql("SELECT * FROM {tool_mutrain_framework} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$framework1->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query(null, 'PRG', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query(null, 'popis', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id, $framework3->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query(null, '', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id, $framework3->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query($catcontext1, '', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query($catcontext1, 'PR', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query($catcontext1, 'PR', '');
        $frameworkids = $DB->get_fieldset_sql("SELECT * FROM {tool_mutrain_framework} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        [$search, $params] = management::get_framework_search_query($catcontext1, 'PRG', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([], $frameworkids);
    }

    public function test_setup_index_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $framework1 = $generator->create_framework();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \core\url('/admin/tool/mutrain/management/index.php'),
            $syscontext
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \core\url('/admin/tool/mutrain/management/index.php'),
            $syscontext
        );
    }

    public function test_setup_framework_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $framework1 = $generator->create_framework();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_framework_page(
            new \core\url('/admin/tool/mutrain/management/new.php'),
            $syscontext,
            $framework1
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_framework_page(
            new \core\url('/admin/tool/mutrain/management/new.php'),
            $syscontext,
            $framework1
        );
    }
}
