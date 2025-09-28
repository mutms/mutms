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

use tool_musudo\local\sudoer;
use tool_musudo\local\util;

/**
 * Test for sudoer class.
 *
 * @group      MuTMS
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\local\sudoer
 */
final class sudoer_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        global $DB, $SITE;

        $syscontext = \context_system::instance();
        $sitecontext = \context_course::instance($SITE->id);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setCurrentTimeStart();
        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);
        $this->assertSame($user1->id, $sudoer1->userid);
        $this->assertSame('0', $sudoer1->mfarequired);
        $privileges = json_encode([['contextid' => $syscontext->id, 'roleid' => (int)$managerrole->id]]);
        $this->assertSame($privileges, $sudoer1->privilegesjson);
        $this->assertTimeCurrent($sudoer1->timecreated);

        $this->setCurrentTimeStart();
        $sudoer2 = sudoer::create((object)[
            'userid' => $user2->id,
            'mfarequired' => '1',
            'note' => 'trusted user',
            'contextid' => [$syscontext->id, $sitecontext->id],
            'roleid' => [$managerrole->id, $teacherrole->id],
        ]);
        $this->assertSame($user2->id, $sudoer2->userid);
        $this->assertSame('1', $sudoer2->mfarequired);
        $this->assertSame('trusted user', $sudoer2->note);
        $privileges = json_encode([
            ['contextid' => $syscontext->id, 'roleid' => (int)$managerrole->id],
            ['contextid' => $sitecontext->id, 'roleid' => (int)$teacherrole->id],
        ]);
        $this->assertSame($privileges, $sudoer2->privilegesjson);
        $this->assertTimeCurrent($sudoer2->timecreated);
    }

    public function test_update(): void {
        global $DB, $SITE;

        $syscontext = \context_system::instance();
        $sitecontext = \context_course::instance($SITE->id);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
            ]);

        $sudoer2 = sudoer::update((object)[
            'id' => $sudoer1->id,
            'userid' => $user2->id,
            'mfarequired' => '1',
            'note' => 'trusted user',
            'contextid' => [$syscontext->id, $sitecontext->id],
            'roleid' => [$managerrole->id, $teacherrole->id],
        ]);
        $this->assertSame($sudoer1->id, $sudoer2->id);
        $this->assertSame($user1->id, $sudoer2->userid);
        $this->assertSame('1', $sudoer2->mfarequired);
        $this->assertSame('trusted user', $sudoer2->note);
        $privileges = json_encode([
            ['contextid' => $syscontext->id, 'roleid' => (int)$managerrole->id],
            ['contextid' => $sitecontext->id, 'roleid' => (int)$teacherrole->id],
        ]);
        $this->assertSame($privileges, $sudoer2->privilegesjson);
        $this->assertSame($sudoer1->timecreated, $sudoer2->timecreated);
    }

    public function test_delete(): void {
        global $DB, $SITE;

        $syscontext = \context_system::instance();
        $sitecontext = \context_course::instance($SITE->id);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $sudoer2 = sudoer::create((object)[
            'userid' => $user2->id,
            'mfarequired' => '1',
            'note' => 'trusted user',
            'contextid' => [$syscontext->id, $sitecontext->id],
            'roleid' => [$managerrole->id, $teacherrole->id],
            ]);

        sudoer::delete($sudoer1->id);
        sudoer::delete($sudoer1->id);

        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['id' => $sudoer1->id]));
        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['id' => $sudoer2->id]));
    }

    public function test_get_role_options(): void {
        global $DB;

        $options = sudoer::get_role_options();
        foreach ($options as $roleid => $rolename) {
            $this->assertTrue($DB->record_exists('role', ['id' => $roleid]));
            $this->assertIsString($rolename);
        }
    }

    public function test_get_privileges_description(): void {
        global $DB, $SITE;

        $syscontext = \context_system::instance();
        $sitecontext = \context_course::instance($SITE->id);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $sudoer2 = sudoer::create((object)[
            'userid' => $user2->id,
            'mfarequired' => '1',
            'note' => 'trusted user',
            'contextid' => [$syscontext->id, $sitecontext->id],
            'roleid' => [$managerrole->id, $teacherrole->id],
        ]);

        $description1 = sudoer::get_privileges_description($sudoer1, false);
        $this->assertSame('Manager in System', $description1);

        $description1 = sudoer::get_privileges_description($sudoer1, true);
        $this->assertSame('Manager in System', $description1);

        $description2 = sudoer::get_privileges_description($sudoer2, false);
        $this->assertSame('Manager in System<br />Non-editing teacher in Site home', $description2);

        $description2 = sudoer::get_privileges_description($sudoer2, true);
        $this->assertSame('Manager in System<br />Non-editing teacher in <a href="https://www.example.com/moodle/">Site home</a>', $description2);
    }

    public function test_can_sudo(): void {
        global $DB, $USER;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $this->setUser($user1);
        $this->assertFalse(isset($USER->access['sudoer']));
        $this->assertTrue(sudoer::can_sudo(false));
        $this->assertSame(true, $USER->access['sudoer']);
        $this->assertTrue(sudoer::can_sudo(true));

        $USER->access['sudoer'] = false;
        $this->assertTrue(sudoer::can_sudo(false));

        $USER->access['sudoer'] = false;
        $this->assertTrue(sudoer::can_sudo());

        $USER->access['sudoer'] = false;
        $this->assertFalse(sudoer::can_sudo(true));

        unset_config('active', 'tool_musudo');
        $this->assertFalse(sudoer::can_sudo(false));
        util::fix_musudo_active();
        $this->assertTrue(sudoer::can_sudo(false));

        $USER->realuser = 1;
        $this->assertFalse(sudoer::can_sudo(false));
        unset($USER->realuser);
        $this->assertTrue(sudoer::can_sudo(false));

        set_config('siteadmins', $user1->id);
        $this->assertFalse(sudoer::can_sudo(false));
        set_config('siteadmins', $admin->id);
        $this->assertTrue(sudoer::can_sudo(false));

        $this->setUser($user2);
        $this->assertFalse(sudoer::can_sudo(false));

        $this->setUser($admin);
        $this->assertFalse(sudoer::can_sudo(false));
    }

    public function test_is_sudo_started(): void {
        global $DB, $USER;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $this->setUser($user1);
        $this->assertFalse(sudoer::is_sudo_started());

        $USER->access['sudosince'] = time();
        $this->assertTrue(sudoer::is_sudo_started());
    }

    public function test_start_sudo(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $this->setUser($user1);
        $this->assertFalse(sudoer::is_sudo_started());
        $this->assertFalse(has_capability('moodle/site:configview', $syscontext));

        sudoer::start_sudo();
        $this->assertTrue(sudoer::is_sudo_started());
        $this->assertTrue(has_capability('moodle/site:configview', $syscontext));
    }

    public function test_end_sudo(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $this->setUser($user1);
        $this->assertTrue(sudoer::start_sudo());
        $this->assertTrue(sudoer::is_sudo_started());
        $this->assertTrue(has_capability('moodle/site:configview', $syscontext));

        @sudoer::end_sudo();
        $this->assertFalse(sudoer::is_sudo_started());
        $this->assertFalse(has_capability('moodle/site:configview', $syscontext));

        $this->setUser($user2);
        $this->assertFalse(sudoer::start_sudo());
        $this->assertFalse(sudoer::is_sudo_started());

        $this->setUser($admin);
        $this->assertFalse(sudoer::start_sudo());
        $this->assertFalse(sudoer::is_sudo_started());
    }

    public function test_user_deleted(): void {
        global $DB, $SITE;

        $syscontext = \context_system::instance();
        $sitecontext = \context_course::instance($SITE->id);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $sudoer2 = sudoer::create((object)[
            'userid' => $user2->id,
            'mfarequired' => '1',
            'note' => 'trusted user',
            'contextid' => [$syscontext->id, $sitecontext->id],
            'roleid' => [$managerrole->id, $teacherrole->id],
        ]);

        delete_user($user1);

        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['id' => $sudoer1->id]));
        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['id' => $sudoer2->id]));
    }

    public function test_after_config(): void {
        global $DB, $USER;

        $hook = new \core\hook\after_config();
        sudoer::after_config($hook);

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        sudoer::after_config($hook);

        $this->setUser($user1);
        $this->assertTrue(sudoer::start_sudo());
        $this->assertTrue(sudoer::is_sudo_started());
        $this->assertTrue(has_capability('moodle/site:configview', $syscontext));

        sudoer::after_config($hook);

        // This is it - test for undoing role switch at course level!
        $USER->access['rsw'] = [];
        try {
            sudoer::after_config($hook);
            $this->fail('redirect expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
    }

    public function test_extend_user_menu(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();

        $sudoer1 = sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);

        $this->setUser($user2);

        $hook = new \core_user\hook\extend_user_menu();
        sudoer::extend_user_menu($hook);
        $items = $hook->get_navitems();
        $this->assertSame([], $items);

        $this->setUser($user1);

        $hook = new \core_user\hook\extend_user_menu();
        sudoer::extend_user_menu($hook);
        $items = $hook->get_navitems();
        $this->assertCount(1, $items);
        $item = reset($items);
        $this->assertSame('link', $item->itemtype);
        $this->assertSame('/admin/tool/musudo/sudo_start.php', $item->url->out_as_local_url());

        sudoer::start_sudo();
        $hook = new \core_user\hook\extend_user_menu();
        sudoer::extend_user_menu($hook);
        $items = $hook->get_navitems();
        $this->assertCount(1, $items);
        $item = reset($items);
        $this->assertSame('link', $item->itemtype);
        $this->assertSame('/admin/tool/musudo/sudo_end.php?sesskey=' . sesskey(), $item->url->out_as_local_url());
    }

    public function test_has_capability(): void {
        global $DB;

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $categorycontext2 = \context_coursecat::instance($category2->id);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $coursecontext1 = \context_course::instance($course1->id);

        $user1 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');

        sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$categorycontext1->id, $categorycontext2->id],
            'roleid' => [$teacherrole->id, $managerrole->id],
        ]);

        $this->setUser($user1);

        $this->assertTrue(has_capability('mod/assign:submit', $coursecontext1));
        $this->assertFalse(has_capability('mod/assign:grade', $coursecontext1));
        $this->assertFalse(has_capability('moodle/course:view', $coursecontext1));
        $this->assertFalse(has_capability('moodle/course:view', $categorycontext1));
        $this->assertTrue(has_capability('moodle/my:manageblocks', $syscontext));

        sudoer::start_sudo();

        $this->assertFalse(has_capability('mod/assign:submit', $coursecontext1));
        $this->assertTrue(has_capability('mod/assign:grade', $coursecontext1));
        $this->assertFalse(has_capability('moodle/course:view', $coursecontext1));
        $this->assertTrue(has_capability('moodle/course:view', $categorycontext2));
        $this->assertTrue(has_capability('moodle/my:manageblocks', $syscontext));
    }
}
