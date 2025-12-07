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
// phpcs:disable moodle.Commenting.MissingDocblock.MissingTestcaseMethodDescription

namespace tool_mulib\phpunit\local\extdb;

use tool_mulib\local\extdb\query_manager;

/**
 * External database query manager tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\extdb\query_manager
 */
final class query_manager_test extends \advanced_testcase {
    public function test_di(): void {
        $qman = \core\di::get(query_manager::class);
        $this->assertInstanceOf(query_manager::class, $qman);
    }

    public function test_get_classes(): void {
        $qman = \core\di::get(query_manager::class);

        $classes = $qman->get_classes();
        $this->assertIsArray($classes);

        foreach ($classes as $component => $types) {
            foreach ($types as $type => $classname) {
                $this->assertSame($component, $classname::get_component());
                $this->assertSame($type, $classname::get_type());
            }
        }

        if (get_config('tool_muprog', 'version')) {
            $this->assertSame('tool_muprog\local\extdb\query\allocation', $classes['tool_muprog']['allocation']);
        }
    }

    public function test_get_class(): void {
        $qman = \core\di::get(query_manager::class);

        $classname = $qman->get_class('tool_muprog', 'allocation');

        if (get_config('tool_muprog', 'version')) {
            $this->assertSame('tool_muprog\local\extdb\query\allocation', $classname);
        } else {
            $this->assertSame(null, $classname);
        }
    }
}
