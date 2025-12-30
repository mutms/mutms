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
 * A javascript module to retrieve allocated coruses from the server.
 *
 * @module block_muprogmyoverview/repository
 * @copyright  2018 Bas Brands <base@moodle.com>
 * @copyright  2025 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Retrieve a list of allocated programs.
 *
 * Valid args are:
 * string classification    future, inprogress, past
 * int limit                number of records to retreive
 * int Offset               offset for pagination
 * int sort                 sort by duedate or name
 *
 * @method getAllocatedProgramsByTimeline
 * @param {object} args The request arguments
 * @return {promise} Resolved with an array of programs
 */
export const getAllocatedProgramsByTimeline = args => {
    const request = {
        methodname: 'block_muprogmyoverview_get_active_programs',
        args: args
    };

    return Ajax.call([request])[0];
};

/**
 * Set the favourite state on a list of programs.
 *
 * Valid args are: id and favourite
 *
 * @param {Object} args Arguments send to the webservice.
 * @return {Promise} Resolve with warnings.
 */
export const setFavouriteProgram = args => {
    const request = {
        methodname: 'block_muprogmyoverview_set_favourite_program',
        args: args
    };

    return Ajax.call([request])[0];
};

/**
 * These program fields are the only ones needed to be included in the results for the card and list views.
 *
 * @type {string[]}
 */
export const CARDLIST_REQUIRED_FIELDS = [
    'id',
];

/**
 * These program fields are the only ones needed to be included in the results for the card and list views.
 *
 * @type {string[]}
 */
export const DESCRIPTION_REQUIRED_FIELDS = [
    'id',
    'description',
];
