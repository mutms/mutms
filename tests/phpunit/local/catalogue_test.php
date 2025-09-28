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

/**
 * Program catalogue test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\catalogue
 */
final class catalogue_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_current_url(): void {
        $catalogue = new \tool_muprog\local\catalogue([]);
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muprog/catalogue/index.php',
            $catalogue->get_current_url()->out(false)
        );

        $catalogue = new \tool_muprog\local\catalogue(['searchtext' => '']);
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muprog/catalogue/index.php',
            $catalogue->get_current_url()->out(false)
        );

        $catalogue = new \tool_muprog\local\catalogue(['page' => 10, 'searchtext' => 'abc']);
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muprog/catalogue/index.php?page=10&searchtext=abc',
            $catalogue->get_current_url()->out(false)
        );

        $catalogue = new \tool_muprog\local\catalogue(['page' => 10, 'searchtext' => 'abc', 'perpage' => 12]);
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/muprog/catalogue/index.php?page=10&perpage=12&searchtext=abc',
            $catalogue->get_current_url()->out(false)
        );
    }

    public function test_is_filtering(): void {
        $catalogue = new \tool_muprog\local\catalogue([]);
        $this->assertFalse($catalogue->is_filtering());

        $catalogue = new \tool_muprog\local\catalogue(['searchtext' => '']);
        $this->assertFalse($catalogue->is_filtering());

        $catalogue = new \tool_muprog\local\catalogue(['page' => 2, 'perpage' => 11]);
        $this->assertFalse($catalogue->is_filtering());

        $catalogue = new \tool_muprog\local\catalogue(['page' => 10, 'searchtext' => 'abc']);
        $this->assertTrue($catalogue->is_filtering());
    }

    public function test_get_page(): void {
        $catalogue = new \tool_muprog\local\catalogue([]);
        $this->assertSame(0, $catalogue->get_page());

        $catalogue = new \tool_muprog\local\catalogue(['page' => '10', 'searchtext' => 'abc']);
        $this->assertSame(10, $catalogue->get_page());
    }

    public function test_get_perpage(): void {
        $catalogue = new \tool_muprog\local\catalogue([]);
        $this->assertSame(10, $catalogue->get_perpage());

        $catalogue = new \tool_muprog\local\catalogue(['page' => '10', 'searchtext' => 'abc', 'perpage' => 14]);
        $this->assertSame(14, $catalogue->get_perpage());
    }

    public function test_get_search_text(): void {
        $catalogue = new \tool_muprog\local\catalogue(['page' => '10', 'searchtext' => 'abc']);
        $this->assertSame('abc', $catalogue->get_searchtext());

        $catalogue = new \tool_muprog\local\catalogue([]);
        $this->assertSame(null, $catalogue->get_searchtext());

        $catalogue = new \tool_muprog\local\catalogue(['page' => '10', 'searchtext' => '']);
        $this->assertSame(null, $catalogue->get_searchtext());

        $catalogue = new \tool_muprog\local\catalogue(['page' => '10', 'searchtext' => 'a']);
        $this->assertSame(null, $catalogue->get_searchtext());
    }

    public function test_get_hidden_search_fields(): void {
        $catalogue = new \tool_muprog\local\catalogue([]);
        $this->assertSame([], $catalogue->get_hidden_search_fields());

        $catalogue = new \tool_muprog\local\catalogue(['searchtext' => '']);
        $this->assertSame([], $catalogue->get_hidden_search_fields());

        $catalogue = new \tool_muprog\local\catalogue(['page' => 2, 'perpage' => 11]);
        $this->assertSame(['page' => 2, 'perpage' => 11], $catalogue->get_hidden_search_fields());

        $catalogue = new \tool_muprog\local\catalogue(['page' => 10, 'searchtext' => 'abc']);
        $this->assertSame(['page' => 10], $catalogue->get_hidden_search_fields());
    }

    public function test_get_programs(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['publicaccess' => 1]);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'cohorts' => [$cohort1->id, $cohort2->id]]);
        $program3 = $generator->create_program(['publicaccess' => 1, 'archived' => 1, 'cohorts' => [$cohort1->id, $cohort2->id], 'sources' => ['manual' => []]]);
        $source3 = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program4 = $generator->create_program(['contextid' => $catcontext1->id, 'cohorts' => [$cohort1->id]]);
        $program5 = $generator->create_program(['contextid' => $catcontext1->id, 'archived' => 1, 'cohorts' => [$cohort2->id]]);
        $program6 = $generator->create_program(['contextid' => $catcontext2->id, 'sources' => ['manual' => []]]);
        $source6 = $DB->get_record('tool_muprog_source', ['programid' => $program6->id, 'type' => 'manual'], '*', MUST_EXIST);

        \cohort_add_member($cohort1->id, $user2->id);
        \cohort_add_member($cohort1->id, $user3->id);
        \tool_muprog\local\source\manual::allocate_users($program3->id, $source3->id, [$user3->id]);
        \tool_muprog\local\source\manual::allocate_users($program6->id, $source6->id, [$user3->id]);

        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());

        $this->setUser($user2);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id, (int)$program2->id, (int)$program4->id], array_keys($programs));
        $this->assertSame(3, $catalogue->count_programs());

        $this->setUser($user3);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id, (int)$program2->id, (int)$program4->id, (int)$program6->id], array_keys($programs));
        $this->assertSame(4, $catalogue->count_programs());

        $this->setUser($user3);
        $catalogue = new \tool_muprog\local\catalogue(['page' => 1, 'perpage' => 2]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program4->id, (int)$program6->id], array_keys($programs));
        $this->assertSame(4, $catalogue->count_programs());
    }

    public function test_get_programs_tenant(): void {
        global $DB;

        if (!\tool_muprog\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user();

        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['publicaccess' => 1]);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'cohorts' => [$cohort1->id, $cohort2->id]]);
        $program3 = $generator->create_program(['publicaccess' => 1, 'archived' => 1, 'cohorts' => [$cohort1->id, $cohort2->id], 'sources' => ['manual' => []]]);
        $program4 = $generator->create_program(['cohorts' => [$cohort1->id]]);
        $program5 = $generator->create_program(['archived' => 1, 'cohorts' => [$cohort2->id]]);
        $program6 = $generator->create_program(['sources' => ['manual' => []]]);

        \cohort_add_member($cohort1->id, $user2->id);
        \cohort_add_member($cohort1->id, $user3->id);
        $source3 = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program3->id, $source3->id, [$user3->id]);
        $source6 = $DB->get_record('tool_muprog_source', ['programid' => $program6->id, 'type' => 'manual'], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program6->id, $source6->id, [$user3->id]);

        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        $this->setUser($user2);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id, (int)$program2->id, (int)$program4->id], array_keys($programs));
        $this->assertSame(3, $catalogue->count_programs());

        $this->setUser($user3);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id, (int)$program2->id, (int)$program4->id, (int)$program6->id], array_keys($programs));
        $this->assertSame(4, $catalogue->count_programs());

        $program1->contextid = $catcontext1->id;
        $program1 = \tool_muprog\local\program::update_general($program1);
        $program2->contextid = $catcontext1->id;
        $program2 = \tool_muprog\local\program::update_general($program2);
        $program3->contextid = $catcontext1->id;
        $program3 = \tool_muprog\local\program::update_general($program3);
        $program4->contextid = $catcontext1->id;
        $program4 = \tool_muprog\local\program::update_general($program4);
        $program5->contextid = $catcontext1->id;
        $program5 = \tool_muprog\local\program::update_general($program5);
        $program6->contextid = $catcontext1->id;
        $program6 = \tool_muprog\local\program::update_general($program6);

        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([], array_keys($programs));
        $this->assertSame(0, $catalogue->count_programs());
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        $this->setUser($user2);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([], array_keys($programs));
        $this->assertSame(0, $catalogue->count_programs());

        $this->setUser($user3);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id, (int)$program2->id, (int)$program4->id, (int)$program6->id], array_keys($programs));
        $this->assertSame(4, $catalogue->count_programs());

        $program1->contextid = $catcontext2->id;
        $program1 = \tool_muprog\local\program::update_general($program1);
        $program2->contextid = $catcontext2->id;
        $program2 = \tool_muprog\local\program::update_general($program2);
        $program3->contextid = $catcontext2->id;
        $program3 = \tool_muprog\local\program::update_general($program3);
        $program4->contextid = $catcontext2->id;
        $program4 = \tool_muprog\local\program::update_general($program4);
        $program5->contextid = $catcontext2->id;
        $program5 = \tool_muprog\local\program::update_general($program5);
        $program6->contextid = $catcontext2->id;
        $program6 = \tool_muprog\local\program::update_general($program6);

        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([], array_keys($programs));
        $this->assertSame(0, $catalogue->count_programs());

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->setUser($user1);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id], array_keys($programs));
        $this->assertSame(1, $catalogue->count_programs());
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        $this->setUser($user2);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id, (int)$program2->id, (int)$program4->id], array_keys($programs));
        $this->assertSame(3, $catalogue->count_programs());

        $this->setUser($user3);
        $catalogue = new \tool_muprog\local\catalogue([]);
        $programs = $catalogue->get_programs();
        $this->assertSame([(int)$program1->id, (int)$program2->id, (int)$program4->id, (int)$program6->id], array_keys($programs));
        $this->assertSame(4, $catalogue->count_programs());
    }

    public function test_is_program_visible(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus', 'publicaccess' => 1]);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'cohorts' => [$cohort1->id, $cohort2->id]]);
        $program3 = $generator->create_program(['publicaccess' => 1, 'archived' => 1, 'cohorts' => [$cohort1->id, $cohort2->id], 'sources' => ['manual' => []]]);
        $source3 = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program4 = $generator->create_program(['contextid' => $catcontext1->id, 'cohorts' => [$cohort1->id]]);
        $program5 = $generator->create_program(['contextid' => $catcontext1->id, 'archived' => 1, 'cohorts' => [$cohort2->id]]);
        $program6 = $generator->create_program(['contextid' => $catcontext2->id, 'sources' => ['manual' => []]]);
        $source6 = $DB->get_record('tool_muprog_source', ['programid' => $program6->id, 'type' => 'manual'], '*', MUST_EXIST);

        \cohort_add_member($cohort1->id, $user2->id);
        \cohort_add_member($cohort1->id, $user3->id);
        \tool_muprog\local\source\manual::allocate_users($program3->id, $source3->id, [$user3->id]);
        \tool_muprog\local\source\manual::allocate_users($program6->id, $source6->id, [$user3->id]);

        $this->setUser($user1);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6));

        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program2, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program4, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user2->id));

        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program2, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program4, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program6, $user3->id));
    }

    public function test_is_program_visible_tenant(): void {
        global $DB;

        if (!\tool_muprog\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user();

        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus', 'publicaccess' => 1]);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'cohorts' => [$cohort1->id, $cohort2->id]]);
        $program3 = $generator->create_program(['publicaccess' => 1, 'archived' => 1, 'cohorts' => [$cohort1->id, $cohort2->id], 'sources' => ['manual' => []]]);
        $program4 = $generator->create_program(['cohorts' => [$cohort1->id]]);
        $program5 = $generator->create_program(['archived' => 1, 'cohorts' => [$cohort2->id]]);
        $program6 = $generator->create_program(['sources' => ['manual' => []]]);
        \cohort_add_member($cohort1->id, $user2->id);
        \cohort_add_member($cohort1->id, $user3->id);
        $source3 = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program3->id, $source3->id, [$user3->id]);
        $source6 = $DB->get_record('tool_muprog_source', ['programid' => $program6->id, 'type' => 'manual'], '*', MUST_EXIST);
        \tool_muprog\local\source\manual::allocate_users($program6->id, $source6->id, [$user3->id]);

        $this->setUser($user1);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6));
        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program2, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program4, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program2, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program4, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program6, $user3->id));

        $program1->contextid = $catcontext1->id;
        $program1 = \tool_muprog\local\program::update_general($program1);
        $program2->contextid = $catcontext1->id;
        $program2 = \tool_muprog\local\program::update_general($program2);
        $program3->contextid = $catcontext1->id;
        $program3 = \tool_muprog\local\program::update_general($program3);
        $program4->contextid = $catcontext1->id;
        $program4 = \tool_muprog\local\program::update_general($program4);
        $program5->contextid = $catcontext1->id;
        $program5 = \tool_muprog\local\program::update_general($program5);
        $program6->contextid = $catcontext1->id;
        $program6 = \tool_muprog\local\program::update_general($program6);

        $this->setUser($user1);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6));
        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program1, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program2, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program4, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program6, $user3->id));

        $program1->contextid = $catcontext2->id;
        $program1 = \tool_muprog\local\program::update_general($program1);
        $program2->contextid = $catcontext2->id;
        $program2 = \tool_muprog\local\program::update_general($program2);
        $program3->contextid = $catcontext2->id;
        $program3 = \tool_muprog\local\program::update_general($program3);
        $program4->contextid = $catcontext2->id;
        $program4 = \tool_muprog\local\program::update_general($program4);
        $program5->contextid = $catcontext2->id;
        $program5 = \tool_muprog\local\program::update_general($program5);
        $program6->contextid = $catcontext2->id;
        $program6 = \tool_muprog\local\program::update_general($program6);

        $this->setUser($user1);
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program1));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6));
        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program2, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program4, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user1->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user1->id));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program2, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program4, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user2->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program6, $user2->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program1, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program2, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program3, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program4, $user3->id));
        $this->assertFalse(\tool_muprog\local\catalogue::is_program_visible($program5, $user3->id));
        $this->assertTrue(\tool_muprog\local\catalogue::is_program_visible($program6, $user3->id));
    }

    public function test_get_catalogue_url(): void {
        $this->setUser(null);
        $this->assertNull(\tool_muprog\local\catalogue::get_catalogue_url());

        $this->setUser(guest_user());
        $this->assertNull(\tool_muprog\local\catalogue::get_catalogue_url());

        $this->setUser(get_admin());
        $this->assertNull(\tool_muprog\local\catalogue::get_catalogue_url());

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['archived' => 1]);
        $this->setUser(get_admin());
        $this->assertNull(\tool_muprog\local\catalogue::get_catalogue_url());

        $program2 = $generator->create_program(['archived' => 0]);
        $this->setUser(get_admin());
        $expected = new \moodle_url('/admin/tool/muprog/catalogue/index.php');
        $this->assertSame((string)$expected, (string)\tool_muprog\local\catalogue::get_catalogue_url());

        $viewer = $this->getDataGenerator()->create_user();
        $this->setUser($viewer);
        $expected = new \moodle_url('/admin/tool/muprog/catalogue/index.php');
        $this->assertSame((string)$expected, (string)\tool_muprog\local\catalogue::get_catalogue_url());

        $syscontext = \context_system::instance();
        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:viewcatalogue', CAP_PROHIBIT, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $viewer->id, $syscontext->id);
        $this->setUser($viewer);
        $this->assertNull(\tool_muprog\local\catalogue::get_catalogue_url());
    }

    public function test_get_tagged_programs(): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus', 'publicaccess' => 1]);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'cohorts' => [$cohort1->id, $cohort2->id]]);
        $program3 = $generator->create_program(['publicaccess' => 1, 'archived' => 1, 'cohorts' => [$cohort1->id, $cohort2->id], 'sources' => ['manual' => []]]);
        $source3 = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program4 = $generator->create_program(['contextid' => $catcontext1->id, 'cohorts' => [$cohort1->id]]);
        $program5 = $generator->create_program(['contextid' => $catcontext1->id, 'archived' => 1, 'cohorts' => [$cohort2->id]]);
        $program6 = $generator->create_program(['contextid' => $catcontext2->id, 'sources' => ['manual' => []]]);
        $source6 = $DB->get_record('tool_muprog_source', ['programid' => $program6->id, 'type' => 'manual'], '*', MUST_EXIST);

        \cohort_add_member($cohort1->id, $user2->id);
        \cohort_add_member($cohort1->id, $user3->id);
        \tool_muprog\local\source\manual::allocate_users($program3->id, $source3->id, [$user3->id]);
        \tool_muprog\local\source\manual::allocate_users($program6->id, $source6->id, [$user3->id]);

        $this->setUser($user1);

        // Just make sure there are no fatal errors in sql, behat will test the logic.
        $html = \tool_muprog\local\catalogue::get_tagged_programs(1, true, 0, 1);
        $html = \tool_muprog\local\catalogue::get_tagged_programs(2, false, 1, 3);
    }
}
