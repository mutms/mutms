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

namespace tool_muprog\phpunit\callback;

/**
 * Training credits callback test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\callback\tool_mutrain
 */
final class tool_mutrain_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        if (!\tool_mulib\local\mulib::is_mutrain_available()) {
            $this->markTestSkipped('training credits are not avilable');
        }

        $this->resetAfterTest();
    }

    public function test_framework_usage(): void {
        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1', 'name' => 'F1']
        );

        $course1 = $this->getDataGenerator()->create_course(
            ['customfield_field1' => 20, 'enablecompletion' => 1]
        );

        $framework1 = $generator->create_framework([
            'fields' => [$field1->get('id')],
            'requiredcredits' => 35,
        ]);
        $framework2 = $generator->create_framework([
            'fields' => [$field1->get('id')],
            'requiredcredits' => 20,
        ]);

        $program1 = $programgenerator->create_program([]);
        $record = [
            'programid' => $program1->id,
            'creditframeworkid' => $framework2->id,
        ];
        $item1 = $programgenerator->create_program_item($record);

        $hook = new \tool_mutrain\hook\framework_usage($framework1->id);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $hook->get_usage());

        $hook = new \tool_mutrain\hook\framework_usage($framework2->id);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(1, $hook->get_usage());

        $program2 = $programgenerator->create_program([]);
        $record = [
            'programid' => $program2->id,
            'creditframeworkid' => $framework2->id,
        ];
        $item2 = $programgenerator->create_program_item($record);

        $hook = new \tool_mutrain\hook\framework_usage($framework1->id);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(0, $hook->get_usage());

        $hook = new \tool_mutrain\hook\framework_usage($framework2->id);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame(2, $hook->get_usage());
    }
}
