<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This certification is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This certification is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this certification.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_mucertify\phpunit;

/**
 * Core certifications API tests.
 *
 * @group      MuTMS
 * @package    tool_mucertify
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers \tool_mucertify_get_tagged_certifications()
     */
    public function test_tool_mucertify_get_tagged_certifications(): void {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/mucertify/lib.php');

        $syscontext = \context_system::instance();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['fullname' => 'hokus']);
        $certification2 = $generator->create_certification(['fullname' => 'pokus']);

        \core_tag_tag::set_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification1->id, $syscontext, ['foo', 'bar']);
        \core_tag_tag::set_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification2->id, $syscontext, ['bar']);
        $tags1 = \core_tag_tag::get_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification1->id);
        $this->assertCount(2, $tags1);
        $tags2 = \core_tag_tag::get_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification2->id);
        $this->assertCount(1, $tags2);
        $bar = reset($tags2);

        $result = tool_mucertify_get_tagged_certifications($bar);
        $this->assertInstanceOf(\core_tag\output\tagindex::class, $result);
    }
}
