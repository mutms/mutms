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
// phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing

/**
 * Unit tests for the block_muprogmyoverview implementation of the privacy API.
 *
 * @group      MuTMS
 * @package    block_muprogmyoverview
 * @category   test
 * @copyright  2018 Peter Dias <peter@moodle.com>
 * @copyright  2025 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_muprogmyoverview\phpunit\privacy;

use core_privacy\local\request\writer;
use block_muprogmyoverview\privacy\provider;

/**
 * Unit tests for the block_muprogmyoverview implementation of the privacy API.
 *
 * @copyright  2018 Peter Dias <peter@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Ensure that export_user_preferences returns no data if the user has not visited the muprogmyoverview block.
     */
    public function test_export_user_preferences_no_pref(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test the export_user_preferences given different inputs
     *
     * @param string $type The name of the user preference to get/set
     * @param string $value The value you are storing
     * @param mixed $expected
     *
     * @dataProvider user_preference_provider
     */
    public function test_export_user_preferences($type, $value, $expected): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        set_user_preference($type, $value, $user);
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $blockpreferences = $writer->get_user_preferences('block_muprogmyoverview');
        if (!$expected) {
            $expected = get_string($value, 'block_muprogmyoverview');
        }
        $this->assertEquals($expected, $blockpreferences->{$type}->value);
    }

    /**
     * Create an array of valid user preferences for the muprogmyoverview block.
     *
     * @return array Array of valid user preferences.
     */
    public static function user_preference_provider(): array {
        return [
            ['block_muprogmyoverview_user_sort_preference', 'duedate', ''],
            ['block_muprogmyoverview_user_sort_preference', 'title', ''],
            ['block_muprogmyoverview_user_sort_preference', 'idnumber', ''],
            ['block_muprogmyoverview_user_grouping_preference', 'allincludinghidden', ''],
            ['block_muprogmyoverview_user_grouping_preference', 'all', ''],
            ['block_muprogmyoverview_user_grouping_preference', 'inprogress', ''],
            ['block_muprogmyoverview_user_grouping_preference', 'future', ''],
            ['block_muprogmyoverview_user_grouping_preference', 'past', ''],
            ['block_muprogmyoverview_user_grouping_preference', 'hidden', ''],
            ['block_muprogmyoverview_user_grouping_preference', 'favourites', ''],
            ['block_muprogmyoverview_user_view_preference', 'card', ''],
            ['block_muprogmyoverview_user_view_preference', 'list', ''],
            ['block_muprogmyoverview_user_view_preference', 'description', ''],
            ['block_muprogmyoverview_user_paging_preference', 12, 12],
        ];
    }

    public function test_export_user_preferences_with_hidden_programs(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $name = "block_muprogmyoverview_hidden_program_1";

        set_user_preference($name, 1, $user);
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $blockpreferences = $writer->get_user_preferences('block_muprogmyoverview');

        $this->assertEquals(
            get_string("privacy:request:preference:set", 'block_muprogmyoverview', (object) [
                'name' => $name,
                'value' => 1,
            ]),
            $blockpreferences->{$name}->description
        );
    }
}
