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

namespace tool_mulib\phpunit\local\notification;

/**
 * Notification type base tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mulib\local\notification\notificationtype
 */
final class notificationtype_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_component
     */
    public function test_get_component(): void {
        $this->assertSame('tool_mulib', \tool_mulib\local\notification\notificationtype::get_component());
    }

    /**
     * @covers ::get_classname
     */
    public function test_get_classname(): void {
        $this->assertSame('notificationtype', \tool_mulib\local\notification\notificationtype::get_notificationtype());
    }

    /**
     * @covers ::format_subject
     */
    public function test_format_subject(): void {
        $this->assertSame(
            '',
            \tool_mulib\local\notification\notificationtype::format_subject('', [])
        );
        $this->assertSame(
            'Test some subject {$a-&gt;def}',
            \tool_mulib\local\notification\notificationtype::format_subject('Test {$a->abc} subject {$a->def}', ['abc' => 'some', 'xyz' => 'opr'])
        );
        $this->assertSame(
            'Test some &lt;subject&gt;',
            \tool_mulib\local\notification\notificationtype::format_subject('Test {$a-&gt;abc} <subject>', ['abc' => 'some'])
        );
    }

    /**
     * @covers ::format_body
     */
    public function test_format_body(): void {
        $this->assertSame(
            '',
            \tool_mulib\local\notification\notificationtype::format_body('', \FORMAT_HTML, [])
        );
        $this->assertSame(
            '',
            \tool_mulib\local\notification\notificationtype::format_body('', \FORMAT_MARKDOWN, [])
        );
        $this->assertSame(
            "<span>great</span>\n\n{\$a-&gt;hmm}",
            \tool_mulib\local\notification\notificationtype::format_body("<span>{\$a->status}</span>\n\n{\$a->hmm}", \FORMAT_HTML, ['status' => 'great'])
        );
        $this->assertSame(
            "<p><span>great</span></p>\n\n<p>{\$a-&gt;hmm}</p>\n",
            \tool_mulib\local\notification\notificationtype::format_body("<span>{\$a->status}</span>\n\n{\$a->hmm}", \FORMAT_MARKDOWN, ['status' => 'great'])
        );

        try {
            \tool_mulib\local\notification\notificationtype::format_body('', \FORMAT_MOODLE, []);
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\coding_exception::class, $e);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Unknown body format: 0',
                $e->getMessage()
            );
        }
    }
}
