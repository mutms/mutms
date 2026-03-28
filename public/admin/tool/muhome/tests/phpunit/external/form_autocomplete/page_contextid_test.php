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

namespace tool_muhome\phpunit\external\form_autocomplete;

use tool_muhome\external\form_autocomplete\page_contextid;

/**
 * Autocomplete WS for page contextid tests.
 *
 * @group       MuTMS
 * @package     tool_muhome
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muhome\external\form_autocomplete\page_contextid
 */
final class page_contextid_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muhome:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);

        $result = page_contextid::execute('');
        $this->assertFalse($result['overflow']);
        $expected = [
            ['value' => (string)$catcontext1->id, 'label' => $category1->name],
        ];
        $this->assertSame($expected, $result['list']);

        $this->setUser($user2);

        $result = page_contextid::execute('');
        $this->assertFalse($result['overflow']);
        $this->assertSame([], $result['list']);
    }

    public function test_validate_value(): void {
        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext2 = \context_coursecat::instance($category2->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muhome:manage', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1);

        $this->assertSame('Invalid context', page_contextid::validate_value($syscontext->id, [], $syscontext));
        $this->assertSame(null, page_contextid::validate_value($catcontext1->id, [], $syscontext));
        $this->assertSame('Invalid context', page_contextid::validate_value($catcontext2->id, [], $syscontext));
        $this->assertSame('Invalid context', page_contextid::validate_value(-1, [], $syscontext));

        $this->setUser($user2);

        $this->assertSame('Invalid context', page_contextid::validate_value($syscontext->id, [], $syscontext));
        $this->assertSame('Invalid context', page_contextid::validate_value($catcontext1->id, [], $syscontext));
        $this->assertSame('Invalid context', page_contextid::validate_value($catcontext2->id, [], $syscontext));
        $this->assertSame('Invalid context', page_contextid::validate_value(-1, [], $syscontext));
    }
}
