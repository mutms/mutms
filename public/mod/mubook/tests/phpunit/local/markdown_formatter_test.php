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

use mod_mubook\local\markdown_formatter;

/**
 * Markdown formatter test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\local\markdown_formatter
 */
final class markdown_formatter_test extends \advanced_testcase {
    public function test_get_html_options(): void {
        $options = markdown_formatter::get_html_options();
        $this->assertSame(
            [markdown_formatter::HTML_STRIP, markdown_formatter::HTML_ESCAPE, markdown_formatter::HTML_ALLOW],
            array_keys($options)
        );
    }

    /**
     * Data provider for convert_to_html tests.
     * @return array[]
     */
    public static function dataprovider_convert_to_html(): array {
        return [
            [
                "# Main heading\n\nsome test",
                [],
                "<h1>Main heading</h1>\n<p>some test</p>\n",
            ],
            [
                "# Main heading\n\nsome test",
                ['firstheading' => 2],
                "<h2>Main heading</h2>\n<p>some test</p>\n",
            ],
            [
                "## Main heading\n\nsome test\n### Subheading",
                ['firstheading' => 3, 'headingoffset' => 1],
                "<h3 class=\"h4\">Main heading</h3>\n<p>some test</p>\n<h4 class=\"h5\">Subheading</h4>\n",
            ],
            [
                "![Image 1](logo.png)\n![Image 2](./logo.png)\n![Image 2](@@PLUGINFILE@@/logo.png)",
                [],
                '<p><img class="img-fluid" src="logo.png" alt="" />
<img class="img-fluid" src="./logo.png" alt="" />
<img class="img-fluid" src="@@PLUGINFILE@@/logo.png" alt="" /></p>
',
            ],
            [
                "![Image 1](logo.png)\n![Image 2](./logo.png)\n![Image 2](@@PLUGINFILE@@/logo.png)",
                ['filebase' => 'XX/'],
                '<p><img class="img-fluid" src="XX/logo.png" alt="" />
<img class="img-fluid" src="XX/logo.png" alt="" />
<img class="img-fluid" src="XX/logo.png" alt="" /></p>
',
            ],
            [
                "[File 1](Filefile.pdf)\n[File 2](./Filefile.pdf)\n[File 2](@@PLUGINFILE@@/Filefile.pdf)",
                [],
                "<p><a href=\"Filefile.pdf\">File 1</a>\n<a href=\"./Filefile.pdf\">File 2</a>\n<a href=\"@@PLUGINFILE@@/Filefile.pdf\">File 2</a></p>\n",
            ],
            [
                "[File 1](Filefile.pdf)\n[File 2](./Filefile.pdf)\n[File 2](@@PLUGINFILE@@/Filefile.pdf)",
                ['filebase' => 'XX/'],
                "<p><a href=\"XX/Filefile.pdf\">File 1</a>\n<a href=\"XX/Filefile.pdf\">File 2</a>\n<a href=\"XX/Filefile.pdf\">File 2</a></p>\n",
            ],
        ];
    }

    /**
     * @dataProvider dataprovider_convert_to_html
     *
     * @param string $markdown
     * @param array $options
     * @param string $html
     * @return void
     */
    public function test_convert_to_html(string $markdown, array $options, string $html): void {
        $this->assertSame($html, markdown_formatter::convert_to_html($markdown, $options));
    }

    // TODO: add test for each added feature.
}
