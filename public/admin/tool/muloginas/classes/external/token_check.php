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
 * Check status of previously generated token.
 *
 * @package     tool_muloginas
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class token_check extends \core_external\external_api {
    /** @var int token is invalid */
    public const STATUS_ERROR = 0;
    /** @var int token still valid for current user */
    public const STATUS_VALID = 1;
    /** @var int token has expired */
    public const STATUS_EXPIRED = 2;
    /** @var int token was already used */
    public const STATUS_USED = 3;

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'token' => new external_value(PARAM_RAW, 'token to check', VALUE_REQUIRED),
        ]);
    }

    /**
     * Describes the external function result value.
     *
     * @return external_description
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, 'token status', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    /**
     * Get token status.
     *
     * @param string $token
     * @return array
     */
    public static function execute(string $token): array {
        global $DB, $USER, $SCRIPT;

        ['token' => $token] = self::validate_parameters(
            self::execute_parameters(),
            ['token' => $token]
        );

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/muloginas:loginas', $context);

        if (!PHPUNIT_TEST && $SCRIPT !== '/lib/ajax/service.php') {
            throw new \core\exception\invalid_parameter_exception('Cannot be called from WS');
        }

        $token = $DB->get_record('tool_muloginas_request', ['token' => $token]);
        if (!$token || $token->userid != $USER->id) {
            return ['status' => self::STATUS_ERROR];
        }

        if ($token->timeused) {
            return ['status' => self::STATUS_USED];
        }

        if (time() - $token->timecreated > loginas::LIFETIME) {
            return ['status' => self::STATUS_EXPIRED];
        }

        return ['status' => self::STATUS_VALID];
    }
}
