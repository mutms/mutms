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

namespace tool_muloginas\external;

use tool_muloginas\local\loginas;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use core_external\external_description;
use core_external\external_value;

/**
 * Generate new Incognito Log-in-as request token.
 *
 * @package     tool_muloginas
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class token_create extends \core_external\external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'targetuserid' => new external_value(PARAM_INT, 'user id to log in as', VALUE_REQUIRED),
        ]);
    }

    /**
     * Describes the external function result value.
     *
     * @return external_description
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            'token' => new external_value(PARAM_RAW, 'log in as token', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'lifetime' => new external_value(PARAM_INT, 'validity in seconds', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    /**
     * Create log-in-as token.
     *
     * @param int $targetuserid
     * @return array
     */
    public static function execute(int $targetuserid): array {
        global $DB, $SCRIPT;

        ['targetuserid' => $targetuserid] = self::validate_parameters(
            self::execute_parameters(),
            ['targetuserid' => $targetuserid]
        );

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/muloginas:loginas', $context);

        if (!PHPUNIT_TEST && $SCRIPT !== '/lib/ajax/service.php') {
            throw new \core\exception\invalid_parameter_exception('Cannot be called from WS');
        }

        $targetuser = $DB->get_record('user', ['id' => $targetuserid, 'deleted' => 0]);

        if (!$targetuser || !loginas::can_loginas($targetuser)) {
            throw new \core\exception\invalid_parameter_exception('Cannot log-in-as given user');
        }

        $request = loginas::create_request($targetuser->id);

        return [
            'token' => $request->token,
            'lifetime' => loginas::LIFETIME,
        ];
    }
}
