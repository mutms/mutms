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

use tool_muprog\external\form_autocomplete\item_append_trainingid;

/**
 * External API for adding of training to program.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\form_autocomplete\item_append_trainingid
 */
final class item_append_trainingid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        if (!\tool_muprog\local\util::is_mutrain_available()) {
            $this->markTestSkipped('mutrain not available');
        }
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_mutrain_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field3']
        );
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']
        );

        $data = (object)[
            'name' => 'Some framework',
            'publicaccess' => 1,
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Other framework',
            'publicaccess' => 1,
            'idnumber' => 'ofr2',
            'fields' => [$field2->get('id')],
        ];
        $framework2 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Another framework',
            'contextid' => $catcontext1->id,
            'publicaccess' => 0,
            'fields' => [],
        ];
        $framework3 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Grrr framework',
            'publicaccess' => 1,
            'archived' => 1,
            'fields' => [],
        ];
        $framework4 = $traininggenerator->create_framework($data);

        $program1 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext1->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $syscontext->id);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $fviewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutrain:viewframeworks', CAP_ALLOW, $fviewerroleid, $syscontext);
        role_assign($fviewerroleid, $user1->id, $syscontext->id);

        $this->setUser($user1);
        $response = item_append_trainingid::execute('', $program1->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework3->id, 'label' => $framework3->name],
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
            ['value' => (int)$framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = item_append_trainingid::execute('framework', $program1->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework3->id, 'label' => $framework3->name],
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
            ['value' => (int)$framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = item_append_trainingid::execute('Another', $program1->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework3->id, 'label' => $framework3->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = item_append_trainingid::execute('fr2', $program1->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = item_append_trainingid::execute('xxx', $program1->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user2);
        $response = item_append_trainingid::execute('', $program1->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
            ['value' => (int)$framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user3);
        $response = item_append_trainingid::execute('', $program2->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
            ['value' => (int)$framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user3);
        try {
            item_append_trainingid::execute('', $program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }

        $this->setUser($user4);
        try {
            item_append_trainingid::execute('', $program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
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

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $generator->create_program([]);
        $program1 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program2 = $generator->create_program(['contextid' => $tenant2catcontext->id]);

        $user0 = $this->getDataGenerator()->create_user(['tenantid' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        /** @var \tool_mutrain_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $data = (object)[
            'name' => 'Framework 0',
            'contextid' => $syscontext->id,
            'publicaccess' => 1,
        ];
        $framework0 = $traininggenerator->create_framework($data);

        $data = (object)[
            'name' => 'Framework 1',
            'contextid' => $tenant1catcontext->id,
            'publicaccess' => 1,
        ];
        $framework1 = $traininggenerator->create_framework($data);

        $data = (object)[
            'name' => 'Framework 2',
            'contextid' => $tenant2catcontext->id,
            'publicaccess' => 1,
        ];
        $framework2 = $traininggenerator->create_framework($data);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user0->id, $syscontext->id);
        role_assign($editorroleid, $user1->id, $tenant1catcontext->id);
        role_assign($editorroleid, $user2->id, $tenant2catcontext->id);

        $this->setUser($user0);
        $response = item_append_trainingid::execute('', $program0->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework0->id, 'label' => $framework0->name],
            ['value' => (int)$framework1->id, 'label' => $framework1->name],
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = item_append_trainingid::execute('', $program1->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework0->id, 'label' => $framework0->name],
            ['value' => (int)$framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = item_append_trainingid::execute('', $program2->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework0->id, 'label' => $framework0->name],
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user2);
        $response = item_append_trainingid::execute('', $program2->id);
        $results = item_append_trainingid::clean_returnvalue(
            item_append_trainingid::execute_returns(),
            $response
        );
        $this->assertFalse($results['overflow']);
        $expectedlist = [
            ['value' => (int)$framework0->id, 'label' => $framework0->name],
            ['value' => (int)$framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);
    }
}
