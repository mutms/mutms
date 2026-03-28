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

namespace tool_mutrain\phpunit\external\form_autocomplete;

use tool_mutrain\external\form_autocomplete\field_add_fieldid;
use tool_mutrain\local\framework;

/**
 * Autocompletion for adding of fields to frameworks.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\external\form_autocomplete\field_add_fieldid
 */
final class field_add_fieldid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2', 'name' => 'F2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field3', 'name' => 'F3']
        );
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4', 'name' => 'F4']
        );

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework(['contextid' => $catcontext->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutrain:manageframeworks', CAP_ALLOW, $managerroleid, $syscontext);
        role_assign($managerroleid, $user1->id, $syscontext->id);
        role_assign($managerroleid, $user2->id, $catcontext->id);

        $this->setUser($user1);

        $result = field_add_fieldid::execute('', $framework1->id);
        $expected = [
            'list' => [
                ['value' => (string)$field1->get('id'), 'label' => $field1->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field2->get('id'), 'label' => $field2->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field3->get('id'), 'label' => $field3->get('name') . ' <small>(core_course/course)</small>'],
            ],
            'overflow' => false,
            'maxitems' => 50,
        ];
        $this->assertSame($expected, $result);

        framework::field_add($framework2->id, $field2->get('id'));
        $result = field_add_fieldid::execute('', $framework2->id);
        $expected = [
            'list' => [
                ['value' => (string)$field1->get('id'), 'label' => $field1->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field3->get('id'), 'label' => $field3->get('name') . ' <small>(core_course/course)</small>'],
            ],
            'overflow' => false,
            'maxitems' => 50,
        ];
        $this->assertSame($expected, $result);

        $this->setUser($user2);
        $result = field_add_fieldid::execute('', $framework2->id);
        $expected = [
            'list' => [
                ['value' => (string)$field1->get('id'), 'label' => $field1->get('name') . ' <small>(core_course/course)</small>'],
                ['value' => (string)$field3->get('id'), 'label' => $field3->get('name') . ' <small>(core_course/course)</small>'],
            ],
            'overflow' => false,
            'maxitems' => 50,
        ];
        $this->assertSame($expected, $result);

        try {
            field_add_fieldid::execute('', $framework1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
    }

    public function test_validate_form_value(): void {
        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2', 'name' => 'F2']
        );
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field3', 'name' => 'F3']
        );
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4', 'name' => 'F4']
        );

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework(['contextid' => $catcontext->id]);

        framework::field_add($framework2->id, $field2->get('id'));

        $result = field_add_fieldid::validate_value($field1->get('id'), ['frameworkid' => $framework1->id], $syscontext);
        $this->assertNull($result);

        $result = field_add_fieldid::validate_value($field4->get('id'), ['frameworkid' => $framework1->id], $syscontext);
        $this->assertSame('Error', $result);

        $result = field_add_fieldid::validate_value($field2->get('id'), ['frameworkid' => $framework2->id], $catcontext);
        $this->assertSame('Error', $result);

        $result = field_add_fieldid::validate_value(-1, ['frameworkid' => $framework2->id], $catcontext);
        $this->assertSame('Error', $result);
    }
}
