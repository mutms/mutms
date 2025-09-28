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

namespace tool_musudo\phpunit\local;

use tool_musudo\local\mfa;

/**
 * MFA helper test.
 *
 * @group      MuTMS
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\local\mfa
 */
final class mfa_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_is_mfa_enabled(): void {
        if (!get_config('tool_mfa', 'enabled')) {
            $this->assertFalse(mfa::is_mfa_enabled());
        }

        set_config('enabled', '1', 'tool_mfa');
        $this->assertTrue(mfa::is_mfa_enabled());

        $this->setAdminUser();

        if (!get_config('factor_email', 'enabled')) {
            $this->assertFalse(\tool_mfa\manager::is_ready());
        }

        set_config('enabled', '1', 'factor_email');
        $this->assertTrue(\tool_mfa\manager::is_ready());
        $this->assertTrue(mfa::is_mfa_enabled());

        set_config('enabled', '0', 'tool_mfa');
        $this->assertFalse(mfa::is_mfa_enabled());
        $this->assertFalse(\tool_mfa\manager::is_ready());
    }

    public function test_get_user_factors(): void {
        set_config('enabled', '1', 'tool_mfa');

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        if (!get_config('factor_email', 'enabled')) {
            $factors = mfa::get_user_factors();
            $this->assertSame([], $factors);
        }

        set_config('enabled', '1', 'factor_email');
        $factors = mfa::get_user_factors();
        $this->assertCount(1, $factors);
        $factor = reset($factors);
        $this->assertSame('email', $factor->name);
    }

    // NOTE: the rest is tested in behat.
}
