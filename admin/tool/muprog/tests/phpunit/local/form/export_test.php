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

namespace tool_muprog\phpunit\local\form;

use tool_muprog\local\form\export;

/**
 * Program export form test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\form\export
 */
final class export_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_category_capability(): void {
        global $PAGE;

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:export', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, $catcontext1->id);
        $this->setUser($user);
        $PAGE->set_url('/admin/tool/muprog/management/export.php');

        $customdata = ['program' => null, 'context' => $catcontext1, 'contextid' => $catcontext1->id, 'archived' => 0];
        $submit = ['contextid' => $catcontext1->id, 'includesubcontexts' => 0, 'archived' => 0, 'format' => 'json',
            'delimiter_name' => 'comma', 'encoding' => 'UTF-8'];

        export::mock_submit($submit);
        $form = new export(null, $customdata);
        $data = $form->get_data();
        $this->assertNotNull($data);
        $this->assertEquals($catcontext1->id, $data->contextid);

        // Category without the export capability.
        export::mock_submit(['contextid' => $catcontext2->id] + $submit);
        $form = new export(null, $customdata);
        $this->assertNull($form->get_data());

        // System context without the export capability.
        export::mock_submit(['contextid' => \context_system::instance()->id] + $submit);
        $form = new export(null, $customdata);
        $this->assertNull($form->get_data());
    }
}
