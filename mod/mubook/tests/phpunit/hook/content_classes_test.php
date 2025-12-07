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

namespace mod_mubook\phpunit\hook;

use mod_mubook\hook\content_classes;

/**
 * Content classes hook test.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_mubook\hook\content_classes;
 */
final class content_classes_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_classes(): void {
        $cc = new content_classes();

        $classes = $cc->get_classes();
        foreach ($classes as $type => $classname) {
            $this->assertSame($type, $classname::get_type());
            $classname::get_name();
        }
        $this->assertArrayNotHasKey('unknown', $classes);
        $this->assertArrayHasKey('html', $classes);
        $this->assertArrayHasKey('markdown', $classes);
    }

    public function test_register(): void {
        $cc = new content_classes();
        $this->assertDebuggingNotCalled();

        $cc->register('html', \mod_mubook\local\content\html::class);
        $this->assertDebuggingCalled("Class type 'html' is already registered to 'mod_mubook\local\content\html' class");

        $cc->register('xurlx', \core\url::class);
        $this->assertDebuggingCalled("Class 'core\url' for type 'xurlx' is invalid");

        $classes = $cc->get_classes();
        $this->assertArrayNotHasKey('xurlx', $classes);
    }
}
