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

namespace tool_murelation\phpunit\privacy;

use tool_murelation\privacy\provider;
use core_privacy\local\request\writer;
use tool_murelation\local\framework;

/**
 * Privacy provider tests.
 *
 * @group       MuTMS
 * @package     tool_murelation
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_murelation\privacy\provider
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
        /** @var \tool_murelation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_murelation');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $framework1 = $generator->create_framework([
            'uimode' => framework::UIMODE_SUPERVISORS,
        ]);
        $framework2 = $generator->create_framework([
            'uimode' => framework::UIMODE_TEAMS,
        ]);

        $supervisor1 = $generator->create_supervisor([
            'frameworkid' => $framework1->id,
            'userid' => $user1->id,
            'subuserid' => $user2->id,
        ]);
        $supervisor2 = $generator->create_supervisor([
            'frameworkid' => $framework1->id,
            'userid' => $user2->id,
            'subuserid' => $user3->id,
        ]);
        $team1 = $generator->create_team([
            'frameworkid' => $framework2->id,
            'userid' => $user1->id,
            'subuserids' => [$user3->id, $user4->id],
        ]);
        $team2 = $generator->create_team([
            'frameworkid' => $framework2->id,
            'userid' => $user2->id,
            'subuserids' => [$user1->id, $user2->id],
        ]);

        return [$user1, $user2, $user3, $user4, $supervisor1, $supervisor2, $team1, $team2];
    }

    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new \core_privacy\local\metadata\collection('tool_murelation'));

        $itemcollection = $collection->get_collection();
        $this->assertCount(2, $itemcollection);

        $tables = array_values($itemcollection);
        $this->assertEquals('tool_murelation_supervisor', $tables[0]->get_name());
        $this->assertEquals('tool_murelation_subordinate', $tables[1]->get_name());

        // Make sure lang strings exist.
        foreach ($tables as $table) {
            get_string($table->get_summary(), 'tool_murelation');
            foreach ($table->get_privacy_fields() as $str) {
                get_string($str, 'tool_murelation');
            }
        }
    }

    public function test_get_contexts_for_userid(): void {
        [$user1, $user2, $user3, $user4, $supervisor1, $supervisor2, $team1, $team2] = $this->set_up_data();
        $user5 = $this->getDataGenerator()->create_user();

        $syscontext = \context_system::instance();

        $list = provider::get_contexts_for_userid($user1->id);
        $this->assertSame([(string)$syscontext->id], $list->get_contextids());

        $list = provider::get_contexts_for_userid($user5->id);
        $this->assertSame([], $list->get_contextids());
    }

    public function test_export_user_data(): void {
        [$user1, $user2, $user3, $user4, $supervisor1, $supervisor2, $team1, $team2] = $this->set_up_data();
        $syscontext = \context_system::instance();

        $writer = writer::with_context($syscontext);
        $this->assertFalse($writer->has_any_data());

        $this->export_context_data_for_user($user1->id, $syscontext, 'tool_murelation');
        $data = $writer->get_related_data([get_string('supervisor', 'tool_murelation')], 'data');
        $this->assertCount(2, $data);
        $data = $writer->get_related_data([get_string('subordinate', 'tool_murelation')], 'data');
        $this->assertCount(1, $data);
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        [$user1, $user2, $user3, $user4, $supervisor1, $supervisor2, $team1, $team2] = $this->set_up_data();

        $syscontext = \context_system::instance();
        provider::delete_data_for_all_users_in_context($syscontext);

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user4->id]));

        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['userid' => $user1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['userid' => $user4->id]));

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team2->id]));
    }

    public function test_delete_data_for_user(): void {
        global $DB;
        [$user1, $user2, $user3, $user4, $supervisor1, $supervisor2, $team1, $team2] = $this->set_up_data();

        $syscontext = \context_system::instance();
        $list = new \core_privacy\local\request\approved_contextlist($user1, 'tool_murelation', [$syscontext->id]);
        provider::delete_data_for_user($list);

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user4->id]));

        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['userid' => $user3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['userid' => $user4->id]));

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team2->id]));
    }

    public function test_get_users_in_context(): void {
        [$user1, $user2, $user3, $user4, $supervisor1, $supervisor2, $team1, $team2] = $this->set_up_data();
        $user5 = $this->getDataGenerator()->create_user();

        $syscontext = \context_system::instance();

        $userlist = new \core_privacy\local\request\userlist($syscontext, 'tool_murelation');
        provider::get_users_in_context($userlist);
        $this->assertSame([(int)$user1->id, (int)$user2->id, (int)$user3->id, (int)$user4->id], $userlist->get_userids());
    }

    public function test_delete_data_for_users(): void {
        global $DB;
        [$user1, $user2, $user3, $user4, $supervisor1, $supervisor2, $team1, $team2] = $this->set_up_data();

        $syscontext = \context_system::instance();
        $userlist = new \core_privacy\local\request\approved_userlist($syscontext, 'tool_murelation', [$user1->id, $user3->id]);
        provider::delete_data_for_users($userlist);

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user3->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['userid' => $user4->id]));

        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_subordinate', ['userid' => $user3->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_subordinate', ['userid' => $user4->id]));

        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor1->id]));
        $this->assertFalse($DB->record_exists('tool_murelation_supervisor', ['id' => $supervisor2->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team1->id]));
        $this->assertTrue($DB->record_exists('tool_murelation_supervisor', ['id' => $team2->id]));
    }
}
