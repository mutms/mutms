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

namespace tool_muprog\phpunit\local;

use tool_muprog\local\management;

/**
 * Program management helper test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\management
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
        \role_assign($managerrole->id, $manager->id, $catcontext2->id);

        $viewer = $this->getDataGenerator()->create_user();
        $viewerroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        \role_assign($viewerroleid, $viewer->id, $catcontext1->id);

        $this->setUser(null);
        $this->assertNull(management::get_management_url());

        $this->setUser($guest);
        $this->assertNull(management::get_management_url());

        $this->setUser($admin);
        $expected = new \moodle_url('/admin/tool/muprog/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($manager);
        $this->assertNull(management::get_management_url());

        $this->setUser($viewer);
        $this->assertNull(management::get_management_url());
    }

    public function test_get_management_url_tenant(): void {
        if (!\tool_muprog\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('multitenancy not available');
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant = $tenantgenerator->create_tenant();
        $tenantcatcontext = \context_coursecat::instance($tenant->categoryid);
        $syscontext = \context_system::instance();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);

        $viewer0 = $this->getDataGenerator()->create_user();
        role_assign($viewerroleid, $viewer0->id, $syscontext->id);

        $viewer1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        role_assign($viewerroleid, $viewer1->id, $tenantcatcontext->id);

        $this->setUser($viewer0);
        $expected = new \moodle_url('/admin/tool/muprog/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($viewer1);
        $expected = new \moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $tenantcatcontext->id]);
        $this->assertSame((string)$expected, (string)management::get_management_url());
    }

    public function test_get_program_search_query(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $program1 = $generator->create_program(['fullname' => 'First program', 'idnumber' => 'PRG1', 'description' => 'prvni popis']);
        $program2 = $generator->create_program(['fullname' => 'Second program', 'idnumber' => 'PRG2', 'description' => 'druhy popis']);
        $program3 = $generator->create_program(['fullname' => 'Third program', 'idnumber' => 'PR3', 'description' => 'treti popis', 'contextid' => $catcontext1->id]);

        [$search, $params] = management::get_program_search_query(null, 'First', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id], $programids);

        [$search, $params] = management::get_program_search_query(null, 'First', '');
        $programids = $DB->get_fieldset_sql("SELECT * FROM {tool_muprog_program} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$program1->id], $programids);

        [$search, $params] = management::get_program_search_query(null, 'PRG', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id, $program2->id], $programids);

        [$search, $params] = management::get_program_search_query(null, 'popis', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id, $program2->id, $program3->id], $programids);

        [$search, $params] = management::get_program_search_query(null, '', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id, $program2->id, $program3->id], $programids);

        [$search, $params] = management::get_program_search_query($catcontext1, '', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program3->id], $programids);

        [$search, $params] = management::get_program_search_query($catcontext1, 'PR', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program3->id], $programids);

        [$search, $params] = management::get_program_search_query($catcontext1, 'PR', '');
        $programids = $DB->get_fieldset_sql("SELECT * FROM {tool_muprog_program} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$program3->id], $programids);

        [$search, $params] = management::get_program_search_query($catcontext1, 'PRG', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([], $programids);
    }

    public function test_fetch_current_cohorts_menu(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort A']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort B']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort C']);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();

        \tool_muprog\local\program::update_visibility((object)[
            'id' => $program1->id,
            'publicaccess' => 0,
            'cohortids' => [$cohort1->id, $cohort2->id],
        ]);
        \tool_muprog\local\program::update_visibility((object)[
            'id' => $program2->id,
            'publicaccess' => 1,
            'cohortids' => [$cohort3->id],
        ]);

        $expected = [
            $cohort1->id => $cohort1->name,
            $cohort2->id => $cohort2->name,
        ];
        $menu = management::fetch_current_cohorts_menu($program1->id);
        $this->assertSame($expected, $menu);

        $menu = management::fetch_current_cohorts_menu($program3->id);
        $this->assertSame([], $menu);
    }

    public function test_setup_index_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/muprog/management/index.php'),
            $syscontext,
            0
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/muprog/management/index.php'),
            $syscontext,
            $syscontext->id
        );
    }

    public function test_setup_program_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_program_page(
            new \moodle_url('/admin/tool/muprog/management/new.php'),
            $syscontext,
            $program1,
            'program_general'
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_program_page(
            new \moodle_url('/admin/tool/muprog/management/new.php'),
            $syscontext,
            $program1,
            'program_general'
        );
    }
}
