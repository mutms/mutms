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

use tool_muprog\external\form_autocomplete\source_program_edit_programid;

/**
 * Autocompletion support for completed program selection.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\form_autocomplete\source_program_edit_programid
 */
final class source_program_edit_programid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $program1 = $generator->create_program([
            'fullname' => 'hokus',
            'idnumber' => 'p1',
            'contextid' => $syscontext->id,
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'pokus',
            'idnumber' => 'p2',
            'contextid' => $catcontext1->id,
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog3',
            'idnumber' => 'p3',
            'contextid' => $syscontext->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();

        $this->setAdminUser();
        $response = source_program_edit_programid::execute('', $program1->id);
        $results = source_program_edit_programid::clean_returnvalue(
            source_program_edit_programid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $this->assertCount(2, $results['list']);

        $this->setUser($user1);
        try {
            $response = source_program_edit_programid::execute('', $program1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }
    }

    public function test_execute_tenant(): void {
        if (!\tool_muprog\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program1 = $generator->create_program([]);
        $program2 = $generator->create_program([]);
        $program3 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program4 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program5 = $generator->create_program(['contextid' => $tenant2catcontext->id]);

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/muprog:allocate', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $tenant1catcontext->id);

        $this->setAdminUser();
        $response = source_program_edit_programid::execute('', $program1->id);
        $this->assertSame([['value' => $program2->id, 'label' => $program2->fullname]], $response['list']);

        $response = source_program_edit_programid::execute('', $program3->id);
        $this->assertSame([['value' => $program4->id, 'label' => $program4->fullname]], $response['list']);

        $response = source_program_edit_programid::execute('', $program5->id);
        $this->assertSame([], $response['list']);

        $this->setUser($user1);
        $response = source_program_edit_programid::execute('', $program3->id);
        $this->assertSame([['value' => $program4->id, 'label' => $program4->fullname]], $response['list']);
        try {
            source_program_edit_programid::execute('', $program1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }
    }
}
