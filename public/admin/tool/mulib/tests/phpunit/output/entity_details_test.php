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

namespace tool_mulib\phpunit\output;

use tool_mulib\output\entity_details;

/**
 * Entity details rendering tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\output\entity_details
 */
final class entity_details_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_has_details(): void {
        $details = new entity_details();
        $this->assertFalse($details->has_details());

        $details->add('Hello', 'World');
        $this->assertTrue($details->has_details());
    }

    public function test_render(): void {
        global $OUTPUT;

        $details = new entity_details();
        $this->assertSame("<dl class=\"row\">\n</dl>", $OUTPUT->render($details));

        $details->add('Hello', 'World');
        $this->assertSame(
            "<dl class=\"row\">\n    <dt class=\"col-3\">Hello</dt><dd class=\"col-9\">World</dd>\n</dl>",
            $OUTPUT->render($details)
        );
    }
}
