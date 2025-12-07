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

namespace tool_muprog\phpunit\external\form_autocomplete;

use tool_muprog\external\form_autocomplete\source_extdb_edit_queryid;
use tool_mulib\local\mulib;

/**
 * External API for program external db sync query selection.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\form_autocomplete\source_extdb_edit_queryid
 */
final class source_extdb_edit_queryid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        global $CFG;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $programgenerator->create_program(['contextid' => $syscontext->id]);
        $program1 = $programgenerator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $programgenerator->create_program(['contextid' => $catcontext2->id]);

        $server = $generator->create_extdb_server([]);
        $query0 = $generator->create_extdb_query([
            'contextid' => $syscontext->id,
            'name' => 'System query',
            'note' => 'grrr',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query1 = $generator->create_extdb_query([
            'contextid' => $catcontext1->id,
            'name' => 'Category 1 query',
            'note' => 'argh',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query2 = $generator->create_extdb_query([
            'contextid' => $catcontext2->id,
            'name' => 'Kategoie 2 query',
            'note' => '',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);

        $progroleid = $this->getDataGenerator()->create_role();
        $queryroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $progroleid, $syscontext->id);
        assign_capability('tool/mulib:useextdb', CAP_ALLOW, $queryroleid, $syscontext->id);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        role_assign($progroleid, $user0->id, $syscontext->id);
        role_assign($queryroleid, $user0->id, $syscontext->id);

        role_assign($progroleid, $user1->id, $syscontext->id);
        role_assign($queryroleid, $user1->id, $catcontext1->id);

        role_assign($queryroleid, $user2->id, $syscontext->id);

        $this->setUser($user0);

        $result = source_extdb_edit_queryid::execute('', $program0->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(1, $result['list']);
        $this->assert_result_contains($query0->id, $result);

        $result = source_extdb_edit_queryid::execute('', $program1->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(2, $result['list']);
        $this->assert_result_contains($query0->id, $result);
        $this->assert_result_contains($query1->id, $result);

        $result = source_extdb_edit_queryid::execute('System', $program1->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(1, $result['list']);
        $this->assert_result_contains($query0->id, $result);

        $result = source_extdb_edit_queryid::execute('argh', $program1->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(1, $result['list']);
        $this->assert_result_contains($query1->id, $result);

        $this->setUser($user1);

        $result = source_extdb_edit_queryid::execute('', $program1->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(1, $result['list']);
        $this->assert_result_contains($query1->id, $result);

        $result = source_extdb_edit_queryid::execute('', $program2->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(0, $result['list']);

        $this->setUser($user2);

        try {
            source_extdb_edit_queryid::execute('', $program1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }
    }

    public function test_execution_tenant(): void {
        global $CFG;

        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $syscontext = \context_system::instance();
        $tenantcatcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenantcatcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $programgenerator->create_program(['contextid' => $syscontext->id]);
        $program1 = $programgenerator->create_program(['contextid' => $tenantcatcontext1->id]);
        $program2 = $programgenerator->create_program(['contextid' => $tenantcatcontext2->id]);

        $server = $generator->create_extdb_server([]);
        $query0 = $generator->create_extdb_query([
            'contextid' => $syscontext->id,
            'name' => 'System query',
            'note' => 'grrr',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query1 = $generator->create_extdb_query([
            'contextid' => $tenantcatcontext1->id,
            'name' => 'Category 1 query',
            'note' => 'argh',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query2 = $generator->create_extdb_query([
            'contextid' => $tenantcatcontext2->id,
            'name' => 'Kategoie 2 query',
            'note' => '',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);

        $progroleid = $this->getDataGenerator()->create_role();
        $queryroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $progroleid, $syscontext->id);
        assign_capability('tool/mulib:useextdb', CAP_ALLOW, $queryroleid, $syscontext->id);

        $user0 = $this->getDataGenerator()->create_user();
        role_assign($progroleid, $user0->id, $syscontext->id);
        role_assign($queryroleid, $user0->id, $syscontext->id);

        $this->setUser($user0);

        $result = source_extdb_edit_queryid::execute('', $program0->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(1, $result['list']);
        $this->assert_result_contains($query0->id, $result);

        $result = source_extdb_edit_queryid::execute('', $program1->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(2, $result['list']);
        $this->assert_result_contains($query0->id, $result);
        $this->assert_result_contains($query1->id, $result);

        $result = source_extdb_edit_queryid::execute('System', $program1->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(1, $result['list']);
        $this->assert_result_contains($query0->id, $result);

        $result = source_extdb_edit_queryid::execute('argh', $program1->id);
        $result = source_extdb_edit_queryid::clean_returnvalue(source_extdb_edit_queryid::execute_returns(), $result);
        $this->assertCount(1, $result['list']);
        $this->assert_result_contains($query1->id, $result);
    }

    /**
     * Assert query is in result.
     *
     * @param int $queryid
     * @param array $result
     * @return void
     */
    protected function assert_result_contains(int $queryid, array $result): void {
        foreach ($result['list'] as $item) {
            if ($item['value'] == $queryid) {
                return;
            }
        }
        $this->fail("Result does not contain item $queryid");
    }
}
