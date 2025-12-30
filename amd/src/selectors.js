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

/**
 * Javascript to initialise the selectors for the muprogmyoverview block.
 *
 * @copyright  2018 Peter Dias <peter@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default {
    programView: {
        region: '[data-region="programs-view"]',
        regionContent: '[data-region="program-view-content"]'
    },
    FILTERS: '[data-region="filter"]',
    FILTER_OPTION: '[data-filter]',
    DISPLAY_OPTION: '[data-display-option]',
    ACTION_HIDE_PROGRAM: '[data-action="hide-program"]',
    ACTION_SHOW_PROGRAM: '[data-action="show-program"]',
    ACTION_ADD_FAVOURITE: '[data-action="add-favourite"]',
    ACTION_REMOVE_FAVOURITE: '[data-action="remove-favourite"]',
    FAVOURITE_ICON: '[data-region="favourite-icon"]',
    ICON_IS_FAVOURITE: '[data-region="is-favourite"]',
    ICON_NOT_FAVOURITE: '[data-region="not-favourite"]',
    region: {
        selectBlock: '[data-region="muprogmyoverview"]',
        clearIcon: '[data-action="clearsearch"]',
        searchInput: '[data-action="search"]',
    },
};
