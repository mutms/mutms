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

use tool_mutrain\local\framework;

/**
 * Training framework helper test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\local\framework
 */
final class framework_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        $syscontext = \context_system::instance();

        $data = [
            'name' => 'Some framework',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
        ];
        $this->setCurrentTimeStart();
        $framework = framework::create($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame(null, $framework->idnumber);
        $this->assertSame('', $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame('0', $framework->publicaccess);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame('0', $framework->restrictedcompletion);
        $this->assertSame('0', $framework->archived);
        $this->assertTimeCurrent($framework->timecreated);

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $data = [
            'contextid' => (string)$categorycontext->id,
            'name' => 'Some framework 2',
            'idnumber' => 'f2',
            'requiredtraining' => '10',
            'description' => 'pokus',
            'publicaccess' => '1',
            'restrictedcompletion' => '1',
            'archived' => '1',
        ];
        $this->setCurrentTimeStart();
        $framework = framework::create($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['idnumber'], $framework->idnumber);
        $this->assertSame($data['description'], $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame($data['publicaccess'], $framework->publicaccess);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame($data['restrictedcompletion'], $framework->restrictedcompletion);
        $this->assertSame($data['archived'], $framework->archived);
        $this->assertTimeCurrent($framework->timecreated);

        try {
            $data = [
                'name' => 'Some framework 3',
                'idnumber' => 'f2',
                'contextid' => (string)$syscontext->id,
                'requiredtraining' => '101',
            ];
            framework::create($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework idnumber must be unique)', $e->getMessage());
        }

        try {
            $data = [
                'name' => 'Some framework 4',
                'contextid' => (string)$syscontext->id,
                'requiredtraining' => 0,
            ];
            framework::create($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }

        try {
            $data = [
                'name' => 'Some framework 4',
                'contextid' => (string)$syscontext->id,
                'requiredtraining' => -2,
            ];
            framework::create($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }
    }

    public function test_update(): void {
        $syscontext = \context_system::instance();
        $data = [
            'name' => 'Some framework',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
        ];
        $framework = framework::create($data);

        $category = $this->getDataGenerator()->create_category();
        $categorycontext = \context_coursecat::instance($category->id);
        $data = [
            'id' => $framework->id,
            'contextid' => (string)$categorycontext->id,
            'name' => 'Some framework 2',
            'idnumber' => 'f2',
            'requiredtraining' => '10',
            'description' => 'pokus',
            'publicaccess' => '1',
            'restrictedcompletion' => '1',
        ];
        $framework = framework::update($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['idnumber'], $framework->idnumber);
        $this->assertSame($data['description'], $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame($data['publicaccess'], $framework->publicaccess);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame($data['restrictedcompletion'], $framework->restrictedcompletion);
        $this->assertSame('0', $framework->archived);

        $data = [
            'id' => $framework->id,
            'contextid' => (string)$categorycontext->id,
            'name' => 'Some framework 2',
            'idnumber' => 'f2',
            'requiredtraining' => '10',
            'description' => 'pokus',
            'publicaccess' => '1',
            'restrictedcompletion' => '0',
        ];
        $framework = framework::update($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame($data['contextid'], $framework->contextid);
        $this->assertSame($data['name'], $framework->name);
        $this->assertSame($data['idnumber'], $framework->idnumber);
        $this->assertSame($data['description'], $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame($data['publicaccess'], $framework->publicaccess);
        $this->assertSame($data['requiredtraining'], $framework->requiredtraining);
        $this->assertSame($data['restrictedcompletion'], $framework->restrictedcompletion);
        $this->assertSame('0', $framework->archived);

        $this->assertDebuggingNotCalled();
        $data = [
            'id' => $framework->id,
            'archived' => '1',
        ];
        $framework = framework::update($data);
        $this->assertDebuggingCalled('Use framework::archive() and framework::restore() to change archived flag');
        $this->assertSame('0', $framework->archived);

        $data = [
            'name' => 'Some framework 2',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
        ];
        $framework2 = framework::create($data);

        try {
            $data = [
                'id' => $framework2->id,
                'idnumber' => 'f2',
            ];
            framework::update($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework idnumber must be unique)', $e->getMessage());
        }

        try {
            $data = [
                'id' => $framework2->id,
                'requiredtraining' => '0',
            ];
            framework::update($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }

        try {
            $data = [
                'id' => $framework2->id,
                'requiredtraining' => '-2',
            ];
            framework::update($data);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (framework requiredtraining must be positive integer)', $e->getMessage());
        }
    }

    public function test_archive(): void {
        $syscontext = \context_system::instance();
        $data = [
            'name' => 'Some framework',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
        ];
        $framework = framework::create($data);
        $this->assertSame('0', $framework->archived);

        $framework = framework::archive($framework->id);
        $this->assertSame('1', $framework->archived);

        $framework = framework::archive($framework->id);
        $this->assertSame('1', $framework->archived);
    }

    public function test_restore(): void {
        $syscontext = \context_system::instance();
        $data = [
            'name' => 'Some framework',
            'contextid' => (string)$syscontext->id,
            'requiredtraining' => '101',
            'archived' => '1',
        ];
        $framework = framework::create($data);
        $this->assertSame('1', $framework->archived);

        $framework = framework::restore($framework->id);
        $this->assertSame('0', $framework->archived);

        $framework = framework::restore($framework->id);
        $this->assertSame('0', $framework->archived);
    }

    public function test_is_deletable(): void {
        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');
        /** @var \tool_muprog_generator $program1generator */
        $program1generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework(['archived' => 1]);
        $program1 = $program1generator->create_program();
        $program2 = $program1generator->create_program();

        $this->assertFalse(framework::is_deletable($framework1->id));
        $this->assertTrue(framework::is_deletable($framework2->id));

        $framework1 = framework::archive($framework1->id);

        $this->assertTrue(framework::is_deletable($framework1->id));
        $this->assertTrue(framework::is_deletable($framework2->id));

        $top = \tool_muprog\local\program::load_content($program1->id);
        $top->append_training($top, $framework1->id);

        $this->assertFalse(framework::is_deletable($framework1->id));
        $this->assertTrue(framework::is_deletable($framework2->id));

        $top = \tool_muprog\local\program::load_content($program2->id);
        $top->append_training($top, $framework2->id);

        $this->assertFalse(framework::is_deletable($framework1->id));
        $this->assertFalse(framework::is_deletable($framework2->id));

        \tool_muprog\local\program::delete($program1->id);

        $this->assertTrue(framework::is_deletable($framework1->id));
        $this->assertFalse(framework::is_deletable($framework2->id));
    }

    public function test_get_all_training_fields(): void {
        global $DB;

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
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3']
        );

        $result = framework::get_all_training_fields();
        $this->assertArrayHasKey($field1->get('id'), $result);
        $this->assertArrayHasKey($field2->get('id'), $result);
        $this->assertCount(2, $result);

        $f1 = $DB->get_record('customfield_field', ['id' => $field1->get('id')]);
        $f1->component = 'core_course';
        $f1->area = 'course';
        $this->assertEquals($f1, $result[$f1->id]);
    }

    public function test_field_add(): void {
        global $DB;

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

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
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field3']
        );

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework();

        $record1 = framework::field_add($framework1->id, $field1->get('id'));
        $this->assertSame($framework1->id, $record1->frameworkid);
        $this->assertSame((string)$field1->get('id'), $record1->fieldid);

        $record1x = framework::field_add($framework1->id, $field1->get('id'));
        $this->assertEquals($record1, $record1x);

        $record2 = framework::field_add($framework1->id, $field2->get('id'));
        $record3 = framework::field_add($framework2->id, $field1->get('id'));

        $this->assertCount(3, $DB->get_records('tool_mutrain_field', []));

        try {
            framework::field_add(-10, $field2->get('id'));
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $e);
        }

        try {
            framework::field_add($framework1->id, -10);
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (Invalid field: -10)', $e->getMessage());
        }

        try {
            framework::field_add($framework1->id, $field3->get('id'));
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
            $this->assertSame('Invalid parameter value detected (Invalid field: ' . $field3->get('id') . ')', $e->getMessage());
        }

        $this->assertCount(3, $DB->get_records('tool_mutrain_field', []));
    }

    public function test_field_remove(): void {
        global $DB;

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework();

        $record1 = framework::field_add($framework1->id, $field1->get('id'));
        $record2 = framework::field_add($framework1->id, $field2->get('id'));
        $record3 = framework::field_add($framework2->id, $field1->get('id'));

        framework::field_remove($record1->frameworkid, $record1->fieldid);
        $this->assertCount(2, $DB->get_records('tool_mutrain_field', []));
        framework::field_remove($record1->frameworkid, $record1->fieldid);
        $this->assertCount(2, $DB->get_records('tool_mutrain_field', []));
        framework::field_remove($record2->frameworkid, $record2->fieldid);
        $this->assertCount(1, $DB->get_records('tool_mutrain_field', []));
        framework::field_remove($record3->frameworkid, $record3->fieldid);
        $this->assertCount(0, $DB->get_records('tool_mutrain_field', []));
    }

    public function test_delete(): void {
        global $DB;

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );

        $admin = \get_admin();
        $site = \get_site();
        $sitecontext = \context_course::instance($site->id);
        $framework1 = $generator->create_framework();
        framework::field_add($framework1->id, $field1->get('id'));
        framework::field_add($framework1->id, $field2->get('id'));
        $framework2 = $generator->create_framework();
        framework::field_add($framework2->id, $field1->get('id'));
        $DB->insert_record('tool_mutrain_completion', ['fieldid' => $field1->get('id'), 'userid' => $admin->id,
            'instanceid' => $site->id, 'timecompleted' => time(), 'contextid' => $sitecontext->id]);

        framework::delete($framework1->id);

        $this->assertFalse($DB->record_exists('tool_mutrain_framework', ['id' => $framework1->id]));
        $this->assertCount(0, $DB->get_records('tool_mutrain_field', ['frameworkid' => $framework1->id]));
        $this->assertTrue($DB->record_exists('tool_mutrain_framework', ['id' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('tool_mutrain_field', ['frameworkid' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('tool_mutrain_completion', []));

        framework::delete($framework1->id);

        $this->assertFalse($DB->record_exists('tool_mutrain_framework', ['id' => $framework1->id]));
        $this->assertCount(0, $DB->get_records('tool_mutrain_field', ['frameworkid' => $framework1->id]));
        $this->assertTrue($DB->record_exists('tool_mutrain_framework', ['id' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('tool_mutrain_field', ['frameworkid' => $framework2->id]));
        $this->assertCount(1, $DB->get_records('tool_mutrain_completion', []));
    }

    public function test_is_area_compatible(): void {
        $this->assertTrue(framework::is_area_compatible('core_course', 'course'));
        $this->assertFalse(framework::is_area_compatible('core_course', 'group'));
    }
}
