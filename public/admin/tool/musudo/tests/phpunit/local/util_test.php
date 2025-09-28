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

use tool_musudo\local\util;
use tool_musudo\local\sudoer;

/**
 * Sudo util helper test.
 *
 * @group      MuTMS
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\local\util
 */
final class util_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_fix_musudo_active(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(util::is_musudo_active());

        sudoer::create((object)['userid' => $user->id, 'contextid' => [$syscontext->id], 'roleid' => [$managerrole->id]]);
        $this->assertTrue(util::is_musudo_active());

        unset_config('active', 'tool_musudo');
        $this->assertFalse(util::is_musudo_active());
        util::fix_musudo_active();
        $this->assertTrue(util::is_musudo_active());
    }

    public function test_is_musudo_active(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(util::is_musudo_active());

        sudoer::create((object)['userid' => $user->id, 'contextid' => [$syscontext->id], 'roleid' => [$managerrole->id]]);
        $this->assertTrue(util::is_musudo_active());
    }

    public function test_require_admin(): void {
        global $DB;

        $syscontext = \context_system::instance();

        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        assign_capability('moodle/site:config', CAP_ALLOW, $managerrole->id, $syscontext);
        role_assign($managerrole->id, $manager->id, $syscontext->id);

        $this->setUser($admin);
        util::require_admin();

        $this->setUser($manager);
        try {
            util::require_admin();
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid role', $ex->getMessage());
        }

        $this->setUser($user);
        try {
            util::require_admin();
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Sorry, but you do not currently have permissions to do that (Change site configuration).', $ex->getMessage());
        }

        $this->setUser(null);
        try {
            util::require_admin();
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
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
