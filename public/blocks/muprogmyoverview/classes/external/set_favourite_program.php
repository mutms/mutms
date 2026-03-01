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

namespace block_muprogmyoverview\external;

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use tool_mulib\local\mulib;
use core\exception\invalid_parameter_exception;
use block_muprogmyoverview\local\util;
use tool_mulib\local\sql;
use core_external\external_warnings;

/**
 * Set/unset programs as favourite.
 *
 * @package     block_muprogmyoverview
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class set_favourite_program extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Program id'),
            'favourite' => new external_value(PARAM_BOOL, 'Favourite status'),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $id
     * @param int $favourite
     * @return array
     */
    public static function execute(int $id, int $favourite): array {
        global $USER, $DB;

        if (!mulib::is_muprog_available()) {
            throw new invalid_parameter_exception('programs not available');
        }

        [
            'id' => $id,
            'favourite' => $favourite,
        ] = self::validate_parameters(self::execute_parameters(), [
            'id' => $id,
            'favourite' => $favourite,
        ]);

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        // Do not waste time with warnings here!
        $result = ['warnings' => []];

        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));

        $record = $DB->get_record('tool_muprog_program', ['id' => $id]);
        if (!$record) {
            return $result;
        }

        if ($ufservice->favourite_exists('tool_muprog', 'programs', $record->id, $syscontext)) {
            if (!$favourite) {
                $ufservice->delete_favourite('tool_muprog', 'programs', $record->id, $syscontext);
            }
        } else {
            if ($favourite) {
                $ufservice->create_favourite('tool_muprog', 'programs', $record->id, $syscontext);
            }
        }

        return $result;
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'warnings' => new external_warnings(),
        ]);
    }
}
