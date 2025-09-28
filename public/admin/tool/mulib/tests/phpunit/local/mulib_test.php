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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\mulib;

/**
 * MuTMS helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\mulib
 */
final class mulib_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_is_mutenancy_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/mutenancy/version.php'),
            mulib::is_mutenancy_available()
        );
    }

    public function test_is_mutenancy_active(): void {
        if (!mulib::is_mutenancy_available()) {
            $this->assertFalse(mulib::is_mutenancy_active());
            return;
        }

        \tool_mutenancy\local\tenancy::deactivate();
        $this->assertFalse(mulib::is_mutenancy_active());

        \tool_mutenancy\local\tenancy::activate();
        $this->assertTrue(mulib::is_mutenancy_active());
    }
}
