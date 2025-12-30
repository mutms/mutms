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

use tool_mutenancy\hook\pre_tenant_delete;
use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy pre tenant deletion hook tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\hook\pre_tenant_delete
 */
final class pre_tenant_delete_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_hook(): void {
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant1 = \tool_mutenancy\local\tenant::archive($tenant1->id);

        $calledtenant = null;
        $this->redirectHook(pre_tenant_delete::class, static function (pre_tenant_delete $hook) use (&$calledtenant) {
            $calledtenant = $hook->tenant;
        });

        \tool_mutenancy\local\tenant::delete($tenant1->id);
        $this->assertEquals($tenant1, $calledtenant);
    }
}
