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

namespace tool_mulib\local;

/**
 * Utility class for MuTMS plugins.
 *
 * NOTE: this is not called util to help with IDE autocompletion.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mulib {
    /**
     * Are training frameworks available?
     *
     * @return bool
     */
    public static function is_mutrain_available(): bool {
        return class_exists(\tool_mutrain\local\util::class);
    }

    /**
     * Are teams and supervisors available?
     *
     * @return bool
     */
    public static function is_murelatio_available(): bool {
        return class_exists(\tool_murelation\local\util::class);
    }

    /**
     * Are teams and supervisors active?
     *
     * @return bool
     */
    public static function is_murelatio_active(): bool {
        if (!self::is_murelatio_available()) {
            return false;
        }
        return \tool_murelation\local\util::is_murelation_active();
    }

    /**
     * Is multi-tenancy available?
     *
     * @return bool
     */
    public static function is_mutenancy_available(): bool {
        return class_exists(\tool_mutenancy\local\tenancy::class);
    }

    /**
     * Is multi-tenancy active?
     *
     * @return bool
     */
    public static function is_mutenancy_active(): bool {
        if (!self::is_mutenancy_available()) {
            return false;
        }
        return \tool_mutenancy\local\tenancy::is_active();
    }

    /**
     * Encode all dangerous characters and named html entities as
     * numeric html entities.
     *
     * The result of this function can be used safely in both {{ }} and {{{ }}} tags in Mustache templates
     * because it is not modified by s() function and it is equivalent to htmlentities() escaping.
     *
     * @param string|null $string
     * @return string|null html string without any tags or dangerous characters
     */
    public static function clean_string(?string $string): ?string {
        if ($string === null || $string === '') {
            return $string;
        }

        $replace = [
            '"' => '&#34;',
            '\'' => '&#39;',
            '<' => '&#60;',
            '>' => '&#62;',
        ];
        $string = strtr($string, $replace);
        $string = preg_replace('/&(?![a-zA-Z0-9#]{1,8};)/', '&#38;', $string);

        static $translationtable = null;

        if (!isset($translationtable)) {
            $translationtable = [];
            // NOTE: do not use ENT_HTML5 here because it adds way too many items.
            $entities = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            foreach ($entities as $char => $entity) {
                $translationtable[$entity] = '&#' . \IntlChar::ord($char) . ';';
            }
        }

        return strtr($string, $translationtable);
    }
}
