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

namespace tool_mutenancy\phpunit\patch;

use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy upstream patch test.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core\context
 */
final class core_context_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::preload_from_record
     */
    public function test_preload_from_record(): void {
        global $DB;

        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $select = \core\context_helper::get_preload_record_columns_sql('c');
        $sql = "SELECT $select
                  FROM {context} c";
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            \core\context_helper::preload_from_record($record);
        }

        tenancy::activate();

        $select = \core\context_helper::get_preload_record_columns_sql('c');
        $sql = "SELECT $select
                  FROM {context} c";
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            \core\context_helper::preload_from_record($record);
        }
    }

    /**
     * @covers ::update_moved
     */
    public function test_update_moved(): void {
        if (tenancy::is_active()) {
            tenancy::deactivate();
        }

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $categorycontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category();
        $categorycontext2 = \context_coursecat::instance($category2->id);

        $course = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course]);
        $cm = get_coursemodule_from_instance('page', $page->id);

        $course->category = $category2->id;
        update_course($course);
        $coursecontext = \context_course::instance($course->id);
        $this->assertSame("/$syscontext->id/$categorycontext2->id/$coursecontext->id", $coursecontext->path);
        $pagecontext = \context_module::instance($cm->id);
        $this->assertSame("/$syscontext->id/$categorycontext2->id/$coursecontext->id/$pagecontext->id", $pagecontext->path);

        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $categorycontext3 = \context_coursecat::instance($tenant1->categoryid);
        $course->category = $tenant1->categoryid;
        update_course($course);
        $coursecontext = \context_course::instance($course->id);
        $this->assertSame("/$syscontext->id/$categorycontext3->id/$coursecontext->id", $coursecontext->path);
        $this->assertSame((int)$tenant1->id, $coursecontext->tenantid);
        $pagecontext = \context_module::instance($cm->id);
        $this->assertSame("/$syscontext->id/$categorycontext3->id/$coursecontext->id/$pagecontext->id", $pagecontext->path);
        $this->assertSame((int)$tenant1->id, $pagecontext->tenantid);

        $tenant2 = $generator->create_tenant();
        $categorycontext4 = \context_coursecat::instance($tenant2->categoryid);
        $course->category = $tenant2->categoryid;
        update_course($course);
        $coursecontext = \context_course::instance($course->id);
        $this->assertSame("/$syscontext->id/$categorycontext4->id/$coursecontext->id", $coursecontext->path);
        $this->assertSame((int)$tenant2->id, $coursecontext->tenantid);
        $pagecontext = \context_module::instance($cm->id);
        $this->assertSame("/$syscontext->id/$categorycontext4->id/$coursecontext->id/$pagecontext->id", $pagecontext->path);
        $this->assertSame((int)$tenant2->id, $pagecontext->tenantid);

        $course->category = $category2->id;
        update_course($course);
        $coursecontext = \context_course::instance($course->id);
        $this->assertSame("/$syscontext->id/$categorycontext2->id/$coursecontext->id", $coursecontext->path);
        $this->assertSame(null, $coursecontext->tenantid);
        $pagecontext = \context_module::instance($cm->id);
        $this->assertSame("/$syscontext->id/$categorycontext2->id/$coursecontext->id/$pagecontext->id", $pagecontext->path);
        $this->assertSame(null, $pagecontext->tenantid);
    }
}
