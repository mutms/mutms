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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_mulib\phpunit\local;

use tool_mulib\local\date_util;

/**
 * Date helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\date_util
 */
final class date_util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_timestamp_forever(): void {
        $this->assertSame('9999999999', (string)date_util::TIMESTAMP_FOREVER);
        $this->assertSame(9999999999, date_util::TIMESTAMP_FOREVER);
    }

    public function test_format_event_date(): void {
        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-15T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-16T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+1 day)</sup>', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-17T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+2 days)</sup>', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-20T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+5 days)</sup>', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-14T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), 0);
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), null);
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), date_util::TIMESTAMP_FOREVER);
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = date_util::format_event_date(date_util::TIMESTAMP_FOREVER, null);
        $this->assertSame('21 November 2286&nbsp;&nbsp;&nbsp;1:46 AM', $result);

        $result = date_util::format_event_date(0, strtotime('2022-08-15T15:00:00'));
        $this->assertSame('', $result);

        $result = date_util::format_event_date(null, strtotime('2022-08-15T15:00:00'));
        $this->assertSame('', $result);

        // Plan text decoding.
        $result = date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-16T15:00:00'));
        $result = strip_tags($result);
        $result = \core_text::entities_to_utf8($result);
        $this->assertSame('15 August 2022   11:00 AM–3:00 PM (+1 day)', $result);
    }
}
