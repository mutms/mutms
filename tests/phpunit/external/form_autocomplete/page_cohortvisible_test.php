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

namespace tool_muhome\phpunit\external\form_autocomplete;

use tool_muhome\external\form_autocomplete\page_cohortvisible;
use tool_mulib\local\mulib;
use tool_muhome\local\page;

/**
 * Autocomplete WS for visible to cohorts tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muhome\external\form_autocomplete\page_cohortvisible
 */
final class page_cohortvisible_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execution(): void {
        global $DB;

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $page1 = $generator->create_page();
        $page2 = $generator->create_page(['contextid' => $catcontext1->id]);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);
        role_assign($managerrole->id, $user2->id, $catcontext1);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muhome:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $this->setUser($user1);

        $result = page_cohortvisible::execute('', 0, $syscontext->id);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = page_cohortvisible::execute('', $page1->id, $page1->contextid);
        $this->assertFalse($result['overflow']);
        $this->assertSame($expected, $result['list']);

        $result = page_cohortvisible::execute('ta 1', $page1->id, $page1->contextid);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort1->id, 'label' => $cohort1->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($user2);

        $result = page_cohortvisible::execute('', $page2->id, $page2->contextid);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = page_cohortvisible::execute('', 0, $catcontext1->id);
        $this->assertFalse($result['overflow']);
        $this->assertSame($expected, $result['list']);

        $this->setUser($user3);

        $result = page_cohortvisible::execute('', $page2->id, $page2->contextid);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort3->id, 'label' => $cohort3->name],
        ];
        $this->assertSame($expected, $result['list']);
    }

    public function test_execution_tenant(): void {
        global $DB;

        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $tenantcohort1 = $DB->get_record('cohort', ['id' => $tenant1->cohortid]);
        $tenantcohort2 = $DB->get_record('cohort', ['id' => $tenant2->cohortid]);

        $page0 = $generator->create_page([]);
        $page1 = $generator->create_page(['contextid' => $tenant1catcontext->id]);
        $page2 = $generator->create_page(['contextid' => $tenant2catcontext->id]);

        $cohort0 = $this->getDataGenerator()->create_cohort(['visible' => 1]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant1catcontext->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant2catcontext->id]);

        $user1 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);

        $this->setUser($user1);

        // NOTE: tenant cohorts are created in system context - they should be visible here.

        $result = page_cohortvisible::execute('', $page0->id, $page0->contextid);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort0->id, 'label' => $cohort0->name],
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $cohort2->id, 'label' => $cohort2->name],
            ['value' => $tenantcohort1->id, 'label' => $tenantcohort1->name],
            ['value' => $tenantcohort2->id, 'label' => $tenantcohort2->name],
        ];
        $this->assertSame($expected, $result['list']);

        $result = page_cohortvisible::execute('', 0, $page0->contextid);
        $this->assertFalse($result['overflow']);
        $this->assertSame($expected, $result['list']);

        $result = page_cohortvisible::execute('', $page1->id, $page1->contextid);
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => $cohort0->id, 'label' => $cohort0->name],
            ['value' => $cohort1->id, 'label' => $cohort1->name],
            ['value' => $tenantcohort1->id, 'label' => $tenantcohort1->name],
            ['value' => $tenantcohort2->id, 'label' => $tenantcohort2->name],
        ];
        $this->assertSame($expected, $result['list']);
    }

    public function test_validate_value(): void {
        global $DB;

        /** @var \tool_muhome_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muhome');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $page1 = $generator->create_page();
        $page2 = $generator->create_page(['contextid' => $catcontext1->id]);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);
        role_assign($managerrole->id, $user2->id, $catcontext1);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muhome:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $this->setUser($user1);

        $this->assertNull(page_cohortvisible::validate_value($cohort1->id, ['pageid' => $page1->id], $syscontext));
        $this->assertNull(page_cohortvisible::validate_value($cohort2->id, ['pageid' => $page1->id], $syscontext));
        $this->assertNull(page_cohortvisible::validate_value($cohort3->id, ['pageid' => $page1->id], $syscontext));
        $this->assertNull(page_cohortvisible::validate_value($cohort1->id, ['pageid' => $page2->id], $catcontext1));
        $this->assertNull(page_cohortvisible::validate_value($cohort2->id, ['pageid' => $page2->id], $catcontext1));
        $this->assertNull(page_cohortvisible::validate_value($cohort3->id, ['pageid' => $page2->id], $catcontext1));

        $this->setUser($user2);

        $this->assertSame('Error', page_cohortvisible::validate_value($cohort1->id, ['pageid' => $page1->id], $syscontext));
        $this->assertNull(page_cohortvisible::validate_value($cohort2->id, ['pageid' => $page1->id], $syscontext));
        $this->assertNull(page_cohortvisible::validate_value($cohort3->id, ['pageid' => $page1->id], $syscontext));
        $this->assertSame('Error', page_cohortvisible::validate_value($cohort1->id, ['pageid' => $page2->id], $catcontext1));
        $this->assertNull(page_cohortvisible::validate_value($cohort2->id, ['pageid' => $page2->id], $catcontext1));
        $this->assertNull(page_cohortvisible::validate_value($cohort3->id, ['pageid' => $page2->id], $catcontext1));

        $page1 = page::update((object)['id' => $page1->id, 'uservisible' => 0, 'cohortvisible' => [$cohort1->id]]);
        $this->assertNull(page_cohortvisible::validate_value($cohort1->id, ['pageid' => $page1->id], $syscontext));
    }
}
