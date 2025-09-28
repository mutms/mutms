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

namespace tool_murelation\phpunit\local;

use tool_murelation\local\util;
use tool_murelation\local\framework;

/**
 * Utility class tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\local\util
 */
final class util_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_fix_murelation_active(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $this->assertFalse(get_config('tool_murelation', 'active'));
        util::fix_murelation_active();
        $this->assertSame('0', get_config('tool_murelation', 'active'));

        $framework1 = $generator->create_framework([
            'uimode' => 'teams',
        ]);
        $this->assertSame('1', get_config('tool_murelation', 'active'));

        unset_config('active', 'tool_murelation');
        util::fix_murelation_active();
        $this->assertSame('1', get_config('tool_murelation', 'active'));

        framework::delete($framework1->id);
        $this->assertSame('0', get_config('tool_murelation', 'active'));
        util::fix_murelation_active();
        $this->assertSame('0', get_config('tool_murelation', 'active'));
    }

    public function test_is_murelation_active(): void {
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $this->assertFalse(util::is_murelation_active());

        $framework1 = $generator->create_framework([
            'uimode' => 'teams',
        ]);
        $this->assertTrue(util::is_murelation_active());

        $framework2 = $generator->create_framework([
            'uimode' => 'teams',
        ]);

        framework::delete($framework1->id);
        $this->assertTrue(util::is_murelation_active());
        framework::delete($framework2->id);
        $this->assertFalse(util::is_murelation_active());
    }
}
