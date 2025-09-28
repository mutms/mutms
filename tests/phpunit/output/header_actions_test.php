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

namespace tool_mulib\phpunit\output;

use tool_mulib\output\header_actions;

/**
 * Header actions tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\output\header_actions
 */
final class header_actions_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_has_items(): void {
        $actions = new header_actions('some title');
        $this->assertFalse($actions->has_items());

        $actions = new header_actions('some title');
        $actions->add_button('some html');
        $this->assertTrue($actions->has_items());

        $actions = new header_actions('some title');
        $actions->get_dropdown()->add_divider();
        $this->assertTrue($actions->has_items());
    }

    public function test_get_dropdown(): void {
        $actions = new header_actions('some title');
        $this->assertInstanceOf(\tool_mulib\output\dropdown::class, $actions->get_dropdown());
    }

    public function test_add_button(): void {
        $actions = new header_actions('some title');
        $button1 = '<a href="https://example.com/page.php">text</a>';
        $actions->add_button($button1);
        $button2 = new \tool_mulib\output\ajax_form\button(new \moodle_url('/'), 'other title');
        $actions->add_button($button2);

        $this->assertDebuggingNotCalled();
        $actions->add_button(new \moodle_url('/'));
        $this->assertDebuggingCalled('Invalid $button parameter, must be \core\output\renderable or string');
    }
}
