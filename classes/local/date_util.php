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
 * Date/time related helper code.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class date_util {
    /** @var int Unix timestamp indicating forever value */
    public const TIMESTAMP_FOREVER = 9999999999;

    /**
     * Formats two dates of event on one short line.
     *
     * @param int|null $timestart start of event
     * @param int|null $timeend end
     * @param mixed $tz
     * @return string
     */
    public static function format_event_date(?int $timestart, ?int $timeend, $tz = null): string {
        if (!$timestart) {
            return '';
        }

        $formattedline = userdate($timestart, get_string('strftimedate', 'langconfig'), $tz);
        $formattedline .= '&nbsp;&nbsp;&nbsp;';
        $formattedline .= userdate($timestart, get_string('strftimetime', 'langconfig'), $tz);

        if ($timestart < $timeend && $timeend != self::TIMESTAMP_FOREVER) {
            $formattedline .= '&ndash;' . userdate($timeend, get_string('strftimetime', 'langconfig'), $tz);

            $tzobject = \core_date::get_user_timezone_object($tz);
            $objstart = new \DateTime('@' . $timestart);
            $objstart->setTimezone($tzobject);
            $objstart->setTime(0, 0, 0);
            $objend = new \DateTime('@' . $timeend);
            $objend->setTimezone($tzobject);
            $objend->setTime(0, 0, 0);
            $daydiff = ($objend->getTimestamp() - $objstart->getTimestamp()) / DAYSECS;
            $daydiff = floor($daydiff);
            if ($daydiff > 0) {
                if ($daydiff == 1) {
                    $dayslater = '(+' . $daydiff . ' ' . get_string('day') . ')';
                } else {
                    $dayslater = '(+' . $daydiff . ' ' . get_string('days') . ')';
                }
                $formattedline .= '<sup> ' . $dayslater . '</sup>';
            }
        }

        return $formattedline;
    }
}
