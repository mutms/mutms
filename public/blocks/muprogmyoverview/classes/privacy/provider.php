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

/**
 * Privacy subsystem implementation for block_muprogmyoverview.
 *
 * @package    block_muprogmyoverview
 * @copyright  2018 Zig Tan <zig@moodle.com>
 * @copyright  2025 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_muprogmyoverview\privacy;

use core_privacy\local\request\user_preference_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for block_muprogmyoverview.
 *
 * @copyright  2018 Zig Tan <zig@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, user_preference_provider {
    /**
     * Returns meta-data information about the muprogmyoverview block.
     *
     * @param  \core_privacy\local\metadata\collection $collection A collection of meta-data.
     * @return \core_privacy\local\metadata\collection Return the collection of meta-data.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('block_muprogmyoverview_user_sort_preference', 'privacy:metadata:overviewsortpreference');
        $collection->add_user_preference('block_muprogmyoverview_user_view_preference', 'privacy:metadata:overviewviewpreference');
        $collection->add_user_preference(
            'block_muprogmyoverview_user_grouping_preference',
            'privacy:metadata:overviewgroupingpreference'
        );
        $collection->add_user_preference(
            'block_muprogmyoverview_user_paging_preference',
            'privacy:metadata:overviewpagingpreference'
        );
        return $collection;
    }
    /**
     * Export all user preferences for the muprogmyoverview block
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $preference = get_user_preferences('block_muprogmyoverview_user_sort_preference', null, $userid);
        if (isset($preference)) {
            writer::export_user_preference(
                'block_muprogmyoverview',
                'block_muprogmyoverview_user_sort_preference',
                get_string($preference, 'block_muprogmyoverview'),
                get_string('privacy:metadata:overviewsortpreference', 'block_muprogmyoverview')
            );
        }

        $preference = get_user_preferences('block_muprogmyoverview_user_view_preference', null, $userid);
        if (isset($preference)) {
            writer::export_user_preference(
                'block_muprogmyoverview',
                'block_muprogmyoverview_user_view_preference',
                get_string($preference, 'block_muprogmyoverview'),
                get_string('privacy:metadata:overviewviewpreference', 'block_muprogmyoverview')
            );
        }

        $preference = get_user_preferences('block_muprogmyoverview_user_grouping_preference', null, $userid);
        if (isset($preference)) {
            writer::export_user_preference(
                'block_muprogmyoverview',
                'block_muprogmyoverview_user_grouping_preference',
                get_string($preference, 'block_muprogmyoverview'),
                get_string('privacy:metadata:overviewgroupingpreference', 'block_muprogmyoverview')
            );
        }

        $preferences = get_user_preferences(null, null, $userid);
        foreach ($preferences as $name => $value) {
            if (str_starts_with($name, 'block_muprogmyoverview_hidden_program_')) {
                writer::export_user_preference(
                    'block_muprogmyoverview',
                    $name,
                    $value,
                    get_string('privacy:request:preference:set', 'block_muprogmyoverview', (object) [
                        'name' => $name,
                        'value' => $value,
                    ])
                );
            }
        }

        $preference = get_user_preferences('block_muprogmyoverview_user_paging_preference', null, $userid);
        if (isset($preference)) {
            \core_privacy\local\request\writer::export_user_preference(
                'block_muprogmyoverview',
                'block_muprogmyoverview_user_paging_preference',
                $preference,
                get_string('privacy:metadata:overviewpagingpreference', 'block_muprogmyoverview')
            );
        }
    }
}
