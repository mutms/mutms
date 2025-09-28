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

namespace tool_mutenancy\phpunit\patch\context;

/**
 * Multi-tenancy core modifications tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \core\context\tenant
 */
final class tenant_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::instance
     */
    public function test_constructor(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant1 = $generator->create_tenant();

        $this->assertSame(12, \core\context\tenant::LEVEL);

        $context = \core\context\tenant::instance($tenant1->id);
        $this->assertSame($tenant1->id, $context->instanceid);
        $this->assertSame(\core\context\tenant::LEVEL, $context->contextlevel);
        $this->assertSame((int)$tenant1->id, $context->tenantid);

        $context3 = \core\context\tenant::instance($tenant1->id + 1, IGNORE_MISSING);
        $this->assertFalse($context3);

        try {
            \core\context\tenant::instance($tenant1->id + 1);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $ex);
        }
    }

    /**
     * @covers ::get_short_name
     */
    public function test_get_short_name(): void {
        $this->assertSame('tenant', \core\context\tenant::get_short_name());
    }

    /**
     * @covers ::get_level_name
     */
    public function test_get_level_name(): void {
        $this->assertSame('Tenant', \core\context\tenant::get_level_name());
    }

    /**
     * @covers ::get_context_name
     */
    public function test_get_context_name(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant1 = $generator->create_tenant([
            'name' => 'Some tenant 1',
            'idnumber' => 'te1',
        ]);
        $context = \core\context\tenant::instance($tenant1->id);

        $this->assertSame('Tenant: Some tenant 1', $context->get_context_name());
        $this->assertSame('Some tenant 1', $context->get_context_name(false));
        $this->assertSame('Tenant: te1', $context->get_context_name(true, true));
        $this->assertSame('te1', $context->get_context_name(false, true));
    }

    /**
     * @covers ::get_url
     */
    public function test_get_url(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant1 = $generator->create_tenant([
            'name' => 'Some tenant 1',
            'idnumber' => 'te1',
        ]);
        $context = \core\context\tenant::instance($tenant1->id);

        $url = $context->get_url();
        $this->assertInstanceOf(\moodle_url::class, $url);
        $this->assertSame('https://www.example.com/moodle/admin/tool/mutenancy/tenant.php?id=' . $tenant1->id, $url->out(false));
    }

    /**
     * @covers ::get_possible_parent_levels
     */
    public function test_get_possible_parent_levels(): void {
        $this->assertSame([\core\context\system::LEVEL], \core\context\tenant::get_possible_parent_levels());
    }

    /**
     * @covers ::get_capabilities
     */
    public function test_get_capabilities(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant1 = $generator->create_tenant([
            'name' => 'Some tenant 1',
            'idnumber' => 'te1',
        ]);
        $context = \core\context\tenant::instance($tenant1->id);

        $capabilities = $context->get_capabilities();
        foreach ($capabilities as $capability) {
            $this->assertLessThanOrEqual(\core\context\user::LEVEL, $capability->contextlevel);
        }
    }

    /**
     * @covers ::create_level_instances
     */
    public function test_create_level_instances(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $context1 = \core\context\tenant::instance($tenant1->id);
        $context2 = \core\context\tenant::instance($tenant2->id);
        $context3 = \core\context\tenant::instance($tenant3->id);

        \context_helper::create_instances(\core\context\tenant::LEVEL);
        $this->assertTrue($DB->record_exists('context', ['id' => $context1->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context2->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context3->id, 'contextlevel' => \core\context\tenant::LEVEL]));

        $DB->delete_records('context', ['id' => $context1->id]);
        \context_helper::create_instances(\core\context\tenant::LEVEL);
        $this->assertTrue($DB->record_exists('context', ['instanceid' => $tenant1->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertFalse($DB->record_exists('context', ['id' => $context1->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context2->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context3->id, 'contextlevel' => \core\context\tenant::LEVEL]));
    }

    /**
     * @covers ::get_cleanup_sql
     */
    public function test_get_cleanup_sql(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $context1 = \core\context\tenant::instance($tenant1->id);
        $context2 = \core\context\tenant::instance($tenant2->id);
        $context3 = \core\context\tenant::instance($tenant3->id);

        \context_helper::cleanup_instances();
        $this->assertTrue($DB->record_exists('context', ['id' => $context1->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context2->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context3->id, 'contextlevel' => \core\context\tenant::LEVEL]));

        $DB->delete_records('tool_mutenancy_tenant', ['id' => $tenant1->id]);
        \context_helper::cleanup_instances();
        $this->assertFalse($DB->record_exists('context', ['instanceid' => $tenant1->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertFalse($DB->record_exists('context', ['id' => $context1->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context2->id, 'contextlevel' => \core\context\tenant::LEVEL]));
        $this->assertTrue($DB->record_exists('context', ['id' => $context3->id, 'contextlevel' => \core\context\tenant::LEVEL]));
    }

    /**
     * @covers ::build_paths
     */
    public function test_build_paths(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \core\context\system::instance();
        $category0 = $DB->get_record('course_categories', []);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $context1 = \core\context\tenant::instance($tenant1->id);
        $context2 = \core\context\tenant::instance($tenant2->id);
        $context3 = \core\context\tenant::instance($tenant3->id);

        $contextcat0 = \core\context\coursecat::instance($category0->id);
        $contextcat1 = \core\context\coursecat::instance($tenant1->categoryid);
        $contextcat2 = \core\context\coursecat::instance($tenant2->categoryid);
        $contextcat3 = \core\context\coursecat::instance($tenant3->categoryid);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $contextuser0 = \core\context\user::instance($user0->id);
        $contextuser1 = \core\context\user::instance($user1->id);
        $contextuser2 = \core\context\user::instance($user2->id);

        $course0 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);

        $contextcourse0 = \core\context\course::instance($course0->id);
        $contextcourse1 = \core\context\course::instance($course1->id);

        \core\context_helper::build_all_paths(false);

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);

        \core\context_helper::build_all_paths(true);

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);

        $DB->set_field_select('context', 'path', null, "id <> ?", [$syscontext->id]);
        \core\context_helper::build_all_paths(false);

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);

        $DB->set_field_select('context', 'depth', 0, "id <> ?", [$syscontext->id]);
        \core\context_helper::build_all_paths(false);

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);

        $DB->set_field('context', 'tenantid', null, []);
        \core\context_helper::build_all_paths(false);

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);
    }

    /**
     * @covers ::fix_all_tenantids
     */
    public function test_fix_all_tenantids(): void {
        global $DB;

        \core\context\tenant::fix_all_tenantids();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \core\context\system::instance();
        $category0 = $DB->get_record('course_categories', []);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $context1 = \core\context\tenant::instance($tenant1->id);
        $context2 = \core\context\tenant::instance($tenant2->id);
        $context3 = \core\context\tenant::instance($tenant3->id);

        $contextcat0 = \core\context\coursecat::instance($category0->id);
        $contextcat1 = \core\context\coursecat::instance($tenant1->categoryid);
        $contextcat2 = \core\context\coursecat::instance($tenant2->categoryid);
        $contextcat3 = \core\context\coursecat::instance($tenant3->categoryid);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $contextuser0 = \core\context\user::instance($user0->id);
        $contextuser1 = \core\context\user::instance($user1->id);
        $contextuser2 = \core\context\user::instance($user2->id);

        $course0 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);

        $contextcourse0 = \core\context\course::instance($course0->id);
        $contextcourse1 = \core\context\course::instance($course1->id);

        \core\context\tenant::fix_all_tenantids();

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);

        $DB->set_field('context', 'tenantid', null, []);
        \core\context\tenant::fix_all_tenantids();

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);

        $DB->set_field('context', 'tenantid', $tenant1->id, []);
        \core\context\tenant::fix_all_tenantids();

        $cu0 = $DB->get_record('context', ['id' => $contextuser0->id]);
        $this->assertSame("$syscontext->path/$cu0->id", $cu0->path);
        $this->assertSame('2', $cu0->depth);
        $this->assertSame(null, $cu0->tenantid);

        $c1 = $DB->get_record('context', ['id' => $context1->id]);
        $this->assertSame("$syscontext->path/$c1->id", $c1->path);
        $this->assertSame('2', $c1->depth);
        $this->assertSame($tenant1->id, $c1->tenantid);

        $cc1 = $DB->get_record('context', ['id' => $contextcat1->id]);
        $this->assertSame("$syscontext->path/$cc1->id", $cc1->path);
        $this->assertSame('2', $cc1->depth);
        $this->assertSame($tenant1->id, $cc1->tenantid);

        $cu1 = $DB->get_record('context', ['id' => $contextuser1->id]);
        $this->assertSame("$syscontext->path/$c1->id/$cu1->id", $cu1->path);
        $this->assertSame('3', $cu1->depth);
        $this->assertSame($tenant1->id, $cu1->tenantid);

        $co0 = $DB->get_record('context', ['id' => $contextcourse0->id]);
        $this->assertSame("$contextcat0->path/$co0->id", $co0->path);
        $this->assertSame('3', $co0->depth);
        $this->assertSame(null, $co0->tenantid);

        $co1 = $DB->get_record('context', ['id' => $contextcourse1->id]);
        $this->assertSame("$contextcat1->path/$co1->id", $co1->path);
        $this->assertSame('3', $co1->depth);
        $this->assertSame($tenant1->id, $co1->tenantid);
    }

    /**
     * @covers ::guess_tenantid
     */
    public function test_guess_tenantid(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $syscontext = \core\context\system::instance();
        $category0 = $DB->get_record('course_categories', []);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();

        $context1 = \core\context\tenant::instance($tenant1->id);
        $context2 = \core\context\tenant::instance($tenant2->id);
        $context3 = \core\context\tenant::instance($tenant3->id);

        $contextcat0 = \core\context\coursecat::instance($category0->id);
        $contextcat1 = \core\context\coursecat::instance($tenant1->categoryid);
        $contextcat2 = \core\context\coursecat::instance($tenant2->categoryid);
        $contextcat3 = \core\context\coursecat::instance($tenant3->categoryid);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $contextuser0 = \core\context\user::instance($user0->id);
        $contextuser1 = \core\context\user::instance($user1->id);
        $contextuser2 = \core\context\user::instance($user2->id);

        $course0 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);

        $contextcourse0 = \core\context\course::instance($course0->id);
        $contextcourse1 = \core\context\course::instance($course1->id);

        $bi0 = $this->getDataGenerator()->create_block('online_users', [
            'parentcontextid' => $contextuser0->id,
            'pagetypepattern' => 'user-profile',
            'subpagepattern' => $contextuser0->id,
        ]);
        $bi1 = $this->getDataGenerator()->create_block('online_users', [
            'parentcontextid' => $contextuser1->id,
            'pagetypepattern' => 'user-profile',
            'subpagepattern' => $contextuser1->id,
        ]);

        $contextblock0 = \core\context\block::instance($bi0->id);
        $contextblock1 = \core\context\block::instance($bi1->id);

        $this->assertSame(
            null,
            \core\context\tenant::guess_tenantid($syscontext->contextlevel, $syscontext->instanceid, $syscontext->path)
        );
        $this->assertSame(
            null,
            \core\context\tenant::guess_tenantid($contextcat0->contextlevel, $contextcat0->instanceid, $contextcat0->path)
        );
        $this->assertSame(
            null,
            \core\context\tenant::guess_tenantid($contextcourse0->contextlevel, $contextcourse0->instanceid, $contextcourse0->path)
        );
        $this->assertSame(
            null,
            \core\context\tenant::guess_tenantid($contextuser0->contextlevel, $contextuser0->instanceid, $contextuser0->path)
        );
        $this->assertSame(
            null,
            \core\context\tenant::guess_tenantid($contextblock0->contextlevel, $contextblock0->instanceid, $contextblock0->path)
        );

        $this->assertSame(
            (int)$tenant1->id,
            \core\context\tenant::guess_tenantid($contextcat1->contextlevel, $contextcat1->instanceid, $contextcat1->path)
        );
        $this->assertSame(
            (int)$tenant1->id,
            \core\context\tenant::guess_tenantid($contextcourse1->contextlevel, $contextcourse1->instanceid, $contextcourse1->path)
        );
        $this->assertSame(
            (int)$tenant1->id,
            \core\context\tenant::guess_tenantid($contextuser1->contextlevel, $contextuser1->instanceid, $contextuser1->path)
        );
        $this->assertSame(
            (int)$tenant1->id,
            \core\context\tenant::guess_tenantid($contextblock1->contextlevel, $contextblock1->instanceid, $contextblock1->path)
        );
    }
}
