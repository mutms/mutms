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

namespace tool_mutrain\phpunit;

/**
 * Framework generator test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain_generator
 */
final class generator_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        global $DB;

        $syscontext = \context_system::instance();

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');
        $this->assertInstanceOf('tool_mutrain_generator', $generator);

        $this->setCurrentTimeStart();
        $framework = $generator->create_framework();
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame((string)$syscontext->id, $framework->contextid);
        $this->assertSame('Framework 1', $framework->name);
        $this->assertSame(null, $framework->idnumber);
        $this->assertSame('', $framework->description);
        $this->assertSame('1', $framework->descriptionformat);
        $this->assertSame('1', $framework->publicaccess);
        $this->assertSame('100.00000', $framework->requiredcredits);
        $this->assertSame(null, $framework->restrictafter);
        $this->assertSame('0', $framework->restrictcontext);
        $this->assertSame('0', $framework->restrictcontext);
        $this->assertSame('0', $framework->archived);
        $this->assertTimeCurrent($framework->timecreated);

        $fields = $DB->get_records('tool_mutrain_field', ['frameworkid' => $framework->id]);
        $this->assertCount(0, $fields);

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
        $data = (object)[
            'name' => 'Some other framework',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'publicaccess' => '1',
            'requiredcredits' => '50.3',
            'restrictafter' => (string)(time() - DAYSECS),
            'restrictcontext' => 1,
            'archived' => '1',
            'fields' => [$field1->get('id')],
        ];

        $this->setCurrentTimeStart();
        $framework = $generator->create_framework($data);
        $this->assertInstanceOf('stdClass', $framework);
        $this->assertSame((string)$catcontext->id, $framework->contextid);
        $this->assertSame($data->name, $framework->name);
        $this->assertSame($data->idnumber, $framework->idnumber);
        $this->assertSame($data->description, $framework->description);
        $this->assertSame($data->descriptionformat, $framework->descriptionformat);
        $this->assertSame($data->publicaccess, $framework->publicaccess);
        $this->assertSame((float)$data->requiredcredits, (float)$framework->requiredcredits);
        $this->assertSame($data->restrictafter, $framework->restrictafter);
        $this->assertSame('1', $framework->restrictcontext);
        $this->assertSame($data->archived, $framework->archived);
        $this->assertTimeCurrent($framework->timecreated);

        $fields = $DB->get_records('tool_mutrain_field', ['frameworkid' => $framework->id]);
        $this->assertCount(1, $fields);
        $field = reset($fields);
        $this->assertSame((string)$field1->get('id'), $field->fieldid);

        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $framework = $generator->create_framework([
            'category' => $category2->name,
            'requiredcredits' => 100,
            'fields' => $field1->get('shortname') . ',' . $field2->get('shortname'),
        ]);
        $this->assertSame((string)$catcontext2->id, $framework->contextid);
        $fields = $DB->get_records('tool_mutrain_field', ['frameworkid' => $framework->id]);
        $this->assertCount(2, $fields);
    }
}
