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

namespace mod_mubook\local;

/**
 * HTML formatting helper.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class html_formatter {
    /**
     * Make sure the headings start with given level.
     *
     * @param string $html
     * @param int $firstheading
     * @return string
     */
    public static function normalise_headings(string $html, int $firstheading): string {
        $diff = null;
        $firstheading = min(6, max(1, $firstheading));

        $error = false;
        $callback = function (array $matches) use (&$diff, $firstheading, &$error): string {
            if ($error) {
                return $matches[0];
            }
            if ($matches[2] !== $matches[4]) {
                $error = true;
                return $matches[0];
            }
            $level = $matches[2];
            if ($diff === null) {
                $diff = $firstheading - $level;
            }
            $newlevel = min(6, max($firstheading, $level + $diff));
            return '<' . $matches[1] . $newlevel . $matches[3] . $newlevel . '>';
        };

        $html = preg_replace_callback('|<(h)([1-6])(.*</h)([1-6])>|iU', $callback, $html);

        return $html;
    }
}
