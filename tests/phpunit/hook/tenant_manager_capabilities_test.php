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

namespace tool_mutenancy\phpunit\hook;

use tool_mutenancy\hook\tenant_manager_capabilities;
use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy tenant manager capabilities hook tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\hook\tenant_manager_capabilities
 */
final class tenant_manager_capabilities_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_hook(): void {
        tenancy::activate();

        $corecaps = [
            'some/plugin:whatever' => CAP_ALLOW,
            'other/plugin:something' => CAP_PROHIBIT,
        ];
        $hook = new tenant_manager_capabilities($corecaps);

        $hook->add_capability('my/plugin:view', CAP_ALLOW);
        $hook->remove_capability('other/plugin:something');

        $this->assertSame([
            'some/plugin:whatever' => CAP_ALLOW,
            'my/plugin:view' => CAP_ALLOW,
        ], $hook->get_capabilities());
    }
}
