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
 * Library functions for My programs overview.
 *
 * @package   block_muprogmyoverview
 * @copyright 2018 Peter Dias
 * @copyright 2025 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Constants for the user preferences grouping options
 */
define('BLOCK_MUPROGMYOVERVIEW_GROUPING_ALLINCLUDINGHIDDEN', 'allincludinghidden');
define('BLOCK_MUPROGMYOVERVIEW_GROUPING_ALL', 'all');
define('BLOCK_MUPROGMYOVERVIEW_GROUPING_INPROGRESS', 'inprogress');
define('BLOCK_MUPROGMYOVERVIEW_GROUPING_FUTURE', 'future');
define('BLOCK_MUPROGMYOVERVIEW_GROUPING_PAST', 'past');
define('BLOCK_MUPROGMYOVERVIEW_GROUPING_FAVOURITES', 'favourites');
define('BLOCK_MUPROGMYOVERVIEW_GROUPING_HIDDEN', 'hidden');

/**
 * Constants for the user preferences sorting options
 * timeline
 */
define('BLOCK_MUPROGMYOVERVIEW_SORTING_TITLE', 'title');
define('BLOCK_MUPROGMYOVERVIEW_SORTING_DUEDATE', 'duedate');
define('BLOCK_MUPROGMYOVERVIEW_SORTING_IDNUMBER', 'idnumber');

/**
 * Constants for the user preferences view options
 */
define('BLOCK_MUPROGMYOVERVIEW_VIEW_CARD', 'card');
define('BLOCK_MUPROGMYOVERVIEW_VIEW_LIST', 'list');
define('BLOCK_MUPROGMYOVERVIEW_VIEW_DESCRIPTION', 'description');

/**
 * Constants for the user paging preferences
 */
define('BLOCK_MUPROGMYOVERVIEW_PAGING_12', 12);
define('BLOCK_MUPROGMYOVERVIEW_PAGING_24', 24);
define('BLOCK_MUPROGMYOVERVIEW_PAGING_48', 48);
define('BLOCK_MUPROGMYOVERVIEW_PAGING_96', 96);
define('BLOCK_MUPROGMYOVERVIEW_PAGING_ALL', 0);

/**
 * Constants for the admin category display setting
 */
define('BLOCK_MUPROGMYOVERVIEW_DISPLAY_CATEGORIES_ON', 'on');
define('BLOCK_MUPROGMYOVERVIEW_DISPLAY_CATEGORIES_OFF', 'off');

/**
 * Get the current user preferences that are available
 *
 * @uses core_user::is_current_user
 *
 * @return array[] Array representing current options along with defaults
 */
function block_muprogmyoverview_user_preferences(): array {
    $preferences['block_muprogmyoverview_user_grouping_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_MUPROGMYOVERVIEW_GROUPING_ALL,
        'type' => PARAM_ALPHA,
        'choices' => [
            BLOCK_MUPROGMYOVERVIEW_GROUPING_ALLINCLUDINGHIDDEN,
            BLOCK_MUPROGMYOVERVIEW_GROUPING_ALL,
            BLOCK_MUPROGMYOVERVIEW_GROUPING_INPROGRESS,
            BLOCK_MUPROGMYOVERVIEW_GROUPING_FUTURE,
            BLOCK_MUPROGMYOVERVIEW_GROUPING_PAST,
            BLOCK_MUPROGMYOVERVIEW_GROUPING_FAVOURITES,
            BLOCK_MUPROGMYOVERVIEW_GROUPING_HIDDEN,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['block_muprogmyoverview_user_sort_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_MUPROGMYOVERVIEW_SORTING_DUEDATE,
        'type' => PARAM_ALPHA,
        'choices' => [
            BLOCK_MUPROGMYOVERVIEW_SORTING_TITLE,
            BLOCK_MUPROGMYOVERVIEW_SORTING_DUEDATE,
            BLOCK_MUPROGMYOVERVIEW_SORTING_IDNUMBER,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['block_muprogmyoverview_user_view_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_MUPROGMYOVERVIEW_VIEW_CARD,
        'type' => PARAM_ALPHA,
        'choices' => [
            BLOCK_MUPROGMYOVERVIEW_VIEW_CARD,
            BLOCK_MUPROGMYOVERVIEW_VIEW_LIST,
            BLOCK_MUPROGMYOVERVIEW_VIEW_DESCRIPTION,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['/^block_muprogmyoverview_hidden_program_(\d+)$/'] = [
        'isregex' => true,
        'choices' => [0, 1],
        'type' => PARAM_INT,
        'null' => NULL_NOT_ALLOWED,
        'default' => 0,
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['block_muprogmyoverview_user_paging_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_MUPROGMYOVERVIEW_PAGING_12,
        'type' => PARAM_INT,
        'choices' => [
            BLOCK_MUPROGMYOVERVIEW_PAGING_12,
            BLOCK_MUPROGMYOVERVIEW_PAGING_24,
            BLOCK_MUPROGMYOVERVIEW_PAGING_48,
            BLOCK_MUPROGMYOVERVIEW_PAGING_96,
            BLOCK_MUPROGMYOVERVIEW_PAGING_ALL,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    return $preferences;
}
