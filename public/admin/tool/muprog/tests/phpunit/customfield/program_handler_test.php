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

namespace tool_muprog\phpunit\customfield;

use tool_muprog\customfield\program_handler;
use tool_muprog\local\program;

/**
 * Program custom field handler test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\customfield\program_handler
 */
final class program_handler_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_program_create(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $managerroleid, $catcontext);

        $manager = $this->getDataGenerator()->create_user();
        role_assign($managerroleid, $manager->id, $catcontext->id);
        $this->setUser($manager);

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Test custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Custom field 1',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $field2 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield2',
            'name' => 'Custom field 2',
            'type' => 'checkbox',
            'categoryid' => $fieldcategory->get('id'),
        ]);

        $data = [
            'fullname' => 'Program 1',
            'idnumber' => 'p1',
            'contextid' => $catcontext->id,
            'customfield_testfield1' => 'Test value 1',
            'customfield_testfield2' => '1',
        ];
        $program1 = program::create((object)$data);
        $data1 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field1->get('id')], '*', MUST_EXIST);
        $this->assertSame('Test value 1', $data1->charvalue);
        $this->assertSame($data1->charvalue, $data1->value);
        $this->assertSame((string)$catcontext->id, $data1->contextid);
        $data2 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field2->get('id')], '*', MUST_EXIST);
        $this->assertSame('1', $data2->intvalue);
        $this->assertSame($data2->intvalue, $data2->value);
        $this->assertSame((string)$catcontext->id, $data2->contextid);
    }

    public function test_program_update(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $managerroleid, $catcontext);

        $adminroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:admin', CAP_ALLOW, $adminroleid, $catcontext);

        $manager = $this->getDataGenerator()->create_user();
        role_assign($managerroleid, $manager->id, $catcontext->id);
        $certadmin = $this->getDataGenerator()->create_user();
        role_assign($adminroleid, $certadmin->id, $catcontext->id);

        $this->setUser($manager);

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Test custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Custom field 1',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $field2 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield2',
            'name' => 'Custom field 2',
            'type' => 'checkbox',
            'categoryid' => $fieldcategory->get('id'),
        ]);

        $data = [
            'fullname' => 'Program 1',
            'idnumber' => 'p1',
            'contextid' => $catcontext->id,
            'customfield_testfield1' => 'Test value 1',
        ];
        $program1 = program::create((object)$data);

        $data = [
            'id' => $program1->id,
            'customfield_testfield1' => 'Test value 1x',
            'customfield_testfield2' => '1',
        ];
        $program1 = program::update_general((object)$data);
        $data1 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field1->get('id')], '*', MUST_EXIST);
        $this->assertSame('Test value 1x', $data1->charvalue);
        $this->assertSame($data1->charvalue, $data1->value);
        $this->assertSame((string)$catcontext->id, $data1->contextid);
        $data2 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field2->get('id')], '*', MUST_EXIST);
        $this->assertSame('1', $data2->intvalue);
        $this->assertSame($data2->intvalue, $data2->value);
        $this->assertSame((string)$catcontext->id, $data2->contextid);

        $data = [
            'id' => $program1->id,
            'customfield_testfield1' => 'Test value 1',
            'customfield_testfield2' => '0',
        ];
        $program1 = program::update_general((object)$data);
        $data1 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field1->get('id')], '*', MUST_EXIST);
        $this->assertSame('Test value 1', $data1->charvalue);
        $this->assertSame($data1->charvalue, $data1->value);
        $this->assertSame((string)$catcontext->id, $data1->contextid);
        $data2 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field2->get('id')], '*', MUST_EXIST);
        $this->assertSame('0', $data2->intvalue);
        $this->assertSame($data2->intvalue, $data2->value);
        $this->assertSame((string)$catcontext->id, $data2->contextid);

        $this->setUser($certadmin);

        $data = [
            'id' => $program1->id,
            'customfield_testfield1' => 'Test value 1x',
            'customfield_testfield2' => '1',
        ];
        $program1 = program::update_general((object)$data);
        $data1 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field1->get('id')], '*', MUST_EXIST);
        $this->assertSame('Test value 1', $data1->charvalue);
        $this->assertSame($data1->charvalue, $data1->value);
        $this->assertSame((string)$catcontext->id, $data1->contextid);
        $data2 = $DB->get_record('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field2->get('id')], '*', MUST_EXIST);
        $this->assertSame('0', $data2->intvalue);
        $this->assertSame($data2->intvalue, $data2->value);
        $this->assertSame((string)$catcontext->id, $data2->contextid);
    }

    public function test_visibility(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category = $this->getDataGenerator()->create_category();
        $catcontext = \context_coursecat::instance($category->id);

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $managerroleid, $catcontext);

        $adminroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:admin', CAP_ALLOW, $adminroleid, $catcontext);

        $manager = $this->getDataGenerator()->create_user();
        role_assign($managerroleid, $manager->id, $catcontext->id);
        $certadmin = $this->getDataGenerator()->create_user();
        role_assign($adminroleid, $certadmin->id, $catcontext->id);
        $allocated = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'tool_muprog',
            'area' => 'program',
            'name' => 'Test custom fields',
        ]);
        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Custom field 1',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
            'configdata' => ['visibilitymanagers' => 1],
        ]);
        $field2 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield2',
            'name' => 'Custom field 2',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
            'configdata' => ['visibilityallocated' => 1],
        ]);
        $field3 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield3',
            'name' => 'Custom field 3',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
            'configdata' => ['visibilityeveryone' => 1],
        ]);
        $field4 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield4',
            'name' => 'Custom field 4',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);

        $this->setAdminUser();
        $data = [
            'fullname' => 'Program 1',
            'idnumber' => 'p1',
            'contextid' => $catcontext->id,
            'publicaccess' => 0,
            'customfield_testfield1' => 'Test value 1',
            'customfield_testfield2' => 'Test value 2',
            'customfield_testfield3' => 'Test value 3',
            'customfield_testfield4' => 'Test value 4',
        ];
        $program1 = $generator->create_program((object)$data);
        $allocation = $generator->create_program_allocation(
            ['programid' => $program1->id, 'userid' => $allocated->id]
        );

        $handler = program_handler::create();

        $this->setGuestUser();
        $datas = $handler->get_instance_data($program1->id);
        $this->assertCount(1, $datas);
        foreach ($datas as $d) {
            if ($d->get('fieldid') == $field3->get('id')) {
                $this->assertSame('Test value 3', $d->get_value());
            } else {
                $this->fail('Unexpected field data: ' . $d->get('fieldid'));
            }
        }

        $this->setUser($other);
        $datas = $handler->get_instance_data($program1->id);
        $this->assertCount(1, $datas);
        foreach ($datas as $d) {
            if ($d->get('fieldid') == $field3->get('id')) {
                $this->assertSame('Test value 3', $d->get_value());
            } else {
                $this->fail('Unexpected field data: ' . $d->get('fieldid'));
            }
        }

        $this->setUser($allocated);
        $datas = $handler->get_instance_data($program1->id);
        $this->assertCount(2, $datas);
        foreach ($datas as $d) {
            if ($d->get('fieldid') == $field3->get('id')) {
                $this->assertSame('Test value 3', $d->get_value());
            } else if ($d->get('fieldid') == $field2->get('id')) {
                $this->assertSame('Test value 2', $d->get_value());
            } else {
                $this->fail('Unexpected field data: ' . $d->get('fieldid'));
            }
        }

        $this->setUser($manager);
        $datas = $handler->get_instance_data($program1->id);
        $this->assertCount(4, $datas);

        $this->setAdminUser();
        $datas = $handler->get_instance_data($program1->id);
        $this->assertCount(4, $datas);

        $this->setUser($certadmin);
        $datas = $handler->get_instance_data($program1->id);
        $this->assertCount(1, $datas);
        foreach ($datas as $d) {
            if ($d->get('fieldid') == $field3->get('id')) {
                $this->assertSame('Test value 3', $d->get_value());
            } else {
                $this->fail('Unexpected field data: ' . $d->get('fieldid'));
            }
        }
    }
}
