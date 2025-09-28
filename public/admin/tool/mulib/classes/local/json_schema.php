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

namespace tool_mulib\local;

/**
 * JSON Schema validation related helper code.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class json_schema {
    /**
     * Validate data.
     *
     * @param mixed $data
     * @param mixed $schema
     * @return array
     */
    public static function validate($data, $schema): array {
        require_once(__DIR__ . '/../../vendor/autoload.php');

        $validator = new \Opis\JsonSchema\Validator();
        try {
            $result = $validator->validate($data, $schema);
            $valid = $result->isValid();
            if ($valid) {
                $errors = [];
            } else {
                $error = $result->error();
                $formatter = new \Opis\JsonSchema\Errors\ErrorFormatter();
                $errors = $formatter->formatKeyed($error);
            }
        } catch (\Opis\JsonSchema\Exceptions\SchemaException $e) {
            $valid = false;
            $errors = [];
            $errors['/'][] = $e->getMessage();
        }
        return [$valid, $errors];
    }

    /**
     * Normalise objects and arrays for JSON processing.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function normalise_data($data) {
        require_once(__DIR__ . '/../../vendor/autoload.php');

        return \Opis\JsonSchema\Helper::toJSON($data);
    }
}
