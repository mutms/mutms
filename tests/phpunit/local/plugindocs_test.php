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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\plugindocs;

/**
 * Plugin documentation helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\plugindocs
 */
final class plugindocs_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_file_regex(): void {
        $this->assertSame(1, preg_match(plugindocs::FILE_REGEX, 'index.md'));
        $this->assertSame(1, preg_match(plugindocs::FILE_REGEX, 'some_page.md'));
        $this->assertSame(1, preg_match(plugindocs::FILE_REGEX, 'some/page.md'));
        $this->assertSame(1, preg_match(plugindocs::FILE_REGEX, 'img/image.png'));
        $this->assertSame(1, preg_match(plugindocs::FILE_REGEX, 'img/other_image.png'));

        $this->assertSame(0, preg_match(plugindocs::FILE_REGEX, 'Index.md'));
        $this->assertSame(0, preg_match(plugindocs::FILE_REGEX, 'some-page.md'));
        $this->assertSame(0, preg_match(plugindocs::FILE_REGEX, 'some page.md'));
        $this->assertSame(0, preg_match(plugindocs::FILE_REGEX, 'some page/.md'));
        $this->assertSame(0, preg_match(plugindocs::FILE_REGEX, 'img/other_image.jpg'));
        $this->assertSame(0, preg_match(plugindocs::FILE_REGEX, 'index.txt'));
    }

    public function test_set(): void {
        global $PAGE;
        $PAGE->set_docs_path('abc');
        $this->assertSame('abc', $PAGE->docspath);

        $this->assertDebuggingNotCalled();

        plugindocs::set_path('tool_mulib', 'non_existent_page.md');
        $this->assertSame(
            'https://www.example.com/moodle/admin/tool/mulib/plugindocs.php/tool_mulib/non_existent_page.md',
            $PAGE->docspath
        );
        $this->assertDebuggingCalled('plugin docs file does not exist: non_existent_page.md');
    }

    public function test_render_github_markdown(): void {
        $this->assertSame(
            "<h1>header</h1>\n<ul>\n<li>list</li>\n</ul>",
            plugindocs::render_github_markdown("#header\n\n* list")
        );
    }
}
