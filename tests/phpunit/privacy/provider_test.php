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

namespace tool_musudo\phpunit\privacy;

use tool_musudo\privacy\provider;
use tool_musudo\local\sudoer;
use core_privacy\local\request\writer;

/**
 * Privacy provider tests.
 *
 * @group       MuTMS
 * @package     tool_musudo
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Set up some test data.
     *
     * @return array users.
     */
    public function set_up_data(): array {
        global $DB, $SITE;

        $syscontext = \context_system::instance();
        $sitecontext = \context_course::instance($SITE->id);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

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

        return [$user1, $user2, $user3];
    }

    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('tool_musudo'));

        $itemcollection = $collection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('tool_musudo_sudoer', $table->get_name());

        // Make sure lang strings exist.
        get_string($table->get_summary(), 'tool_musudo');
        foreach ($table->get_privacy_fields() as $str) {
            get_string($str, 'tool_musudo');
        }
    }

    public function test_get_contexts_for_userid(): void {
        [$user1, $user2, $user3] = $this->set_up_data();

        $syscontext = \context_system::instance();

        $list = provider::get_contexts_for_userid($user1->id);
        $this->assertSame([(string)$syscontext->id], $list->get_contextids());

        $list = provider::get_contexts_for_userid($user3->id);
        $this->assertSame([], $list->get_contextids());
    }

    public function test_export_user_data(): void {
        [$user1, $user2, $user3] = $this->set_up_data();
        $syscontext = \context_system::instance();

        $subcontexts = [get_string('sudoer', 'tool_musudo')];

        $writer = writer::with_context($syscontext);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($user1->id, $syscontext, 'tool_musudo');
        $data = $writer->get_related_data($subcontexts, 'data');
        $this->assertCount(1, $data);
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        [$user1, $user2, $user3] = $this->set_up_data();

        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user3->id]));

        [$user1, $user2, $user3] = $this->set_up_data();

        $syscontext = \context_system::instance();
        provider::delete_data_for_all_users_in_context($syscontext);

        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user3->id]));
    }

    public function test_delete_data_for_user(): void {
        global $DB;
        [$user1, $user2, $user3] = $this->set_up_data();

        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user3->id]));

        $syscontext = \context_system::instance();

        $list = new \core_privacy\local\request\approved_contextlist($user1, 'tool_musudo', [$syscontext->id]);
        provider::delete_data_for_user($list);

        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user3->id]));
    }

    public function test_get_users_in_context(): void {
        [$user1, $user2, $user3] = $this->set_up_data();

        $syscontext = \context_system::instance();

        $userlist = new \core_privacy\local\request\userlist($syscontext, 'tool_musudo');
        provider::get_users_in_context($userlist);
        $this->assertSame([(int)$user1->id, (int)$user2->id], $userlist->get_userids());
    }

    public function test_delete_data_for_users(): void {
        global $DB;
        [$user1, $user2, $user3] = $this->set_up_data();

        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user3->id]));

        $syscontext = \context_system::instance();

        $userlist = new \core_privacy\local\request\approved_userlist($syscontext, 'tool_musudo', [$user1->id]);
        provider::delete_data_for_users($userlist);

        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_musudo_sudoer', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_musudo_sudoer', ['userid' => $user3->id]));
    }
}
