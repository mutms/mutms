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

namespace tool_mutrain\phpunit\local;

use tool_mutrain\local\util;

/**
 * Training framework helper test.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\local\util
 */
final class util_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_is_mutenancy_available(): void {
        $this->assertSame(
            file_exists(__DIR__ . '/../../../../../tool/mutenancy/version.php'),
            util::is_mutenancy_available()
        );
    }

    public function test_is_mutenancy_active(): void {
        if (!util::is_mutenancy_available()) {
            $this->assertFalse(util::is_mutenancy_active());
            return;
        }

        \tool_mutenancy\local\tenancy::deactivate();
        $this->assertFalse(util::is_mutenancy_active());

        \tool_mutenancy\local\tenancy::activate();
        $this->assertTrue(util::is_mutenancy_active());
    }
}
