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

namespace tool_mulib\phpunit\external\form_autocomplete;

use tool_mulib\external\form_autocomplete\extdb_query_contextid;

/**
 * External database query contextid autocomplete tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\external\form_autocomplete\extdb_query_contextid
 */
final class extdb_query_contextid_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
        global $DB;

        $category0 = $DB->get_record('course_categories', []);
        $catcontext0 = \context_coursecat::instance($category0->id);
        $category1 = $this->getDataGenerator()->create_category([
            'name' => 'Kategorie 1',
            'idnumber' => 'KAT1',
            'description' => 'Popis 1',
        ]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([
            'name' => 'Kategorie 2',
            'idnumber' => 'KAT2',
            'description' => 'Popis 2',
        ]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $this->setAdminUser();

        $result = extdb_query_contextid::execute('');
        $result = extdb_query_contextid::clean_returnvalue(extdb_query_contextid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertCount(3, $result['list']);
        $this->assertSame($catcontext0->id, $result['list'][0]['value']);
        $this->assertSame($category0->name, $result['list'][0]['label']);
        $this->assertSame($catcontext1->id, $result['list'][1]['value']);
        $this->assertSame($category1->name, $result['list'][1]['label']);
        $this->assertSame($catcontext2->id, $result['list'][2]['value']);
        $this->assertSame($category2->name, $result['list'][2]['label']);

        $result = extdb_query_contextid::execute('AT1');
        $result = extdb_query_contextid::clean_returnvalue(extdb_query_contextid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertCount(1, $result['list']);
        $this->assertSame($catcontext1->id, $result['list'][0]['value']);
        $this->assertSame($category1->name, $result['list'][0]['label']);
    }

    public function test_execute_tenant(): void {
        global $DB;

        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multitenancy not available');
        }

        $category0 = $DB->get_record('course_categories', []);
        $catcontext0 = \context_coursecat::instance($category0->id);

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $category1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $this->setAdminUser();

        $result = extdb_query_contextid::execute('');
        $result = extdb_query_contextid::clean_returnvalue(extdb_query_contextid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertCount(3, $result['list']);

        \tool_mutenancy\local\tenancy::switch($tenant1->id);

        $result = extdb_query_contextid::execute('');
        $result = extdb_query_contextid::clean_returnvalue(extdb_query_contextid::execute_returns(), $result);
        $this->assertSame(false, $result['overflow']);
        $this->assertCount(2, $result['list']);
    }
}
