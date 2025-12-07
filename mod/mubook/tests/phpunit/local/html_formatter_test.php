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

namespace mod_mubook\phpunit\local;

use mod_mubook\local\html_formatter;

/**
 * HTML formatter test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\local\html_formatter
 */
final class html_formatter_test extends \advanced_testcase {
    public function test_normalise_headings(): void {
        $html = '<h1 class="h2">Main heading</h1>
test
<h2>Sub-chapter</h2>
test
';
        $this->assertSame(
            $html,
            html_formatter::normalise_headings($html, 1)
        );

        $this->assertSame(
            '<h2 class="h2">Main heading</h2>
test
<h3>Sub-chapter</h3>
test
',
            html_formatter::normalise_headings($html, 2)
        );

        $html = '<h2 class="h2">Main heading</h2>
test
<h3>Sub-chapter</h3>
test
';
        $this->assertSame(
            $html,
            html_formatter::normalise_headings($html, 2)
        );

        $this->assertSame(
            '<h3 class="h2">Main heading</h3>
test
<h4>Sub-chapter</h4>
test
',
            html_formatter::normalise_headings($html, 3)
        );

        $this->assertSame(
            '<h1 class="h2">Main heading</h1>
test
<h2>Sub-chapter</h2>
test
',
            html_formatter::normalise_headings($html, 1)
        );
    }
}
