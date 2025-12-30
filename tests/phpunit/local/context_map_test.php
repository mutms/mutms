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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\context_map;
use context, context_system, context_coursecat, context_course, context_module;
use tool_mulib\local\sql;

/**
 * Context map tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\context_map
 */
final class context_map_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            \tool_mutenancy\local\tenancy::deactivate();
        }
    }

    public function test_get_contexts_by_capability_join(): void {
        global $DB, $CFG;

        $category0 = \core_course_category::get_default();
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $course0 = $DB->get_record('course', []);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course1->id]);
        $page2 = $this->getDataGenerator()->create_module('page', ['course' => $course2->id]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $categorycontext0 = context_coursecat::instance($category0->id);
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $coursecontext0 = context_course::instance($course0->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $pagecontentext1 = context_module::instance($page1->cmid);
        $pagecontentext2 = context_module::instance($page2->cmid);

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        role_assign($managerroleid, $user1->id, $coursecontext1->id);
        role_assign($managerroleid, $user2->id, $categorycontext1->id);
        role_assign($managerroleid, $user3->id, $syscontext->id);
        role_assign($managerroleid, $user4->id, $categorycontext1->id);

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        assign_capability('moodle/course:view', CAP_PROHIBIT, $managerroleid, $categorycontext1->id);
        role_assign($managerroleid, $user3->id, $syscontext->id);

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('moodle/course:view', CAP_PROHIBIT, $studentroleid, $syscontext->id);
        role_assign($studentroleid, $user4->id, $syscontext->id);

        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_course::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user1->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext1->id], $contextids);

        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_course::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user2->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext1->id, $coursecontext2->id], $contextids);

        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_course::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user3->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id], $contextids);

        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_course::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user4->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', $user1->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$syscontext->id], $contextids);

        unset_config('defaultuserroleid');
        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', $user1->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        $guest = guest_user();
        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', $guest->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$syscontext->id], $contextids);

        assign_capability('moodle/blog:view', CAP_PROHIBIT, $CFG->guestroleid, $syscontext->id, true);
        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', $guest->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        unset_config('guestroleid');
        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', $guest->id, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        assign_capability('moodle/blog:view', CAP_ALLOW, $CFG->notloggedinroleid, $syscontext->id, true);
        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', 0, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$syscontext->id], $contextids);

        assign_capability('moodle/blog:view', CAP_PROHIBIT, $CFG->notloggedinroleid, $syscontext->id, true);
        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', 0, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        unset_config('notloggedinroleid');
        $sql = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_system::LEVEL]
        );
        $joins = context_map::get_contexts_by_capability_join('moodle/blog:view', 0, 'ctx');
        $sql = $sql->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);
    }

    public function test_get_contexts_by_capability_query(): void {
        global $DB, $CFG;

        $category0 = \core_course_category::get_default();
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $this->getDataGenerator()->create_module('page', ['course' => $course1->id]);
        $this->getDataGenerator()->create_module('page', ['course' => $course2->id]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $categorycontext1 = context_coursecat::instance($category1->id);
        $coursecontext1 = context_course::instance($course1->id);

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        role_assign($managerroleid, $user1->id, $coursecontext1->id);
        role_assign($managerroleid, $user2->id, $categorycontext1->id);
        role_assign($managerroleid, $user3->id, $syscontext->id);
        role_assign($managerroleid, $user4->id, $categorycontext1->id);

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        assign_capability('moodle/course:view', CAP_PROHIBIT, $managerroleid, $categorycontext1->id);
        role_assign($managerroleid, $user3->id, $syscontext->id);

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('moodle/course:view', CAP_PROHIBIT, $studentroleid, $syscontext->id);
        role_assign($studentroleid, $user4->id, $syscontext->id);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id], $contextids);

        unset_config('defaultuserroleid');
        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_system::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        $admin = get_admin();
        $where = new sql("");
        $sql = context_map::get_contexts_by_capability_query('moodle/blog:view', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);

        $this->assertEqualsCanonicalizing($DB->get_fieldset_select('context', 'id', 'contextlevel <= 50'), $contextids);

        $guest = guest_user();
        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_system::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/blog:view', $guest->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$syscontext->id], $contextids);

        $where = new sql("");
        $sql = context_map::get_contexts_by_capability_query('moodle/blog:view', $guest->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing($DB->get_fieldset_select('context', 'id', 'contextlevel <= 50'), $contextids);

        assign_capability('moodle/blog:view', CAP_PROHIBIT, $CFG->guestroleid, $syscontext->id, true);
        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_system::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/blog:view', $guest->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        unset_config('guestroleid');
        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_system::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/blog:view', $guest->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        $where = new sql("");
        $sql = context_map::get_contexts_by_capability_query('moodle/blog:view', $guest->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);
    }

    public function test_get_contexts_by_capability_query_locking(): void {
        global $DB;

        $course0 = $DB->get_record('course', []);
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $coursecontext0 = context_course::instance($course0->id);
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        assign_capability('moodle/site:managecontextlocks', CAP_ALLOW, $managerroleid, $coursecontext1);
        role_assign($managerroleid, $user1->id, $coursecontext1->id);
        role_assign($managerroleid, $user1->id, $coursecontext2->id);
        role_assign($managerroleid, $admin->id, $coursecontext1->id);

        set_config('contextlocking', 0);
        set_config('contextlockappliestoadmin', 1);
        $DB->set_field('context', 'locked', 1, ['id' => $coursecontext1->id]);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $admin->id, $where, false);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id], $contextids);

        set_config('contextlocking', 1);
        set_config('contextlockappliestoadmin', 1);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/site:managecontextlocks', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/site:managecontextlocks', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $admin->id, $where, false);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/site:managecontextlocks', $admin->id, $where, false);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id], $contextids);

        set_config('contextlockappliestoadmin', 0);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/site:managecontextlocks', $user1->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:view', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/site:managecontextlocks', $admin->id, $where);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/course:update', $admin->id, $where, false);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id], $contextids);

        $where = new sql("ctx.contextlevel = :contextlevel", ['contextlevel' => context_course::LEVEL]);
        $sql = context_map::get_contexts_by_capability_query('moodle/site:managecontextlocks', $admin->id, $where, false);
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEqualsCanonicalizing([$coursecontext1->id], $contextids);
    }

    public function test_get_contexts_by_capability_query_tenant(): void {
        global $DB, $CFG;
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('multi-tenancy not available');
        }

        $this->assertSame('0', get_config('tool_mutenancy', 'allowguests'));

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $guest = guest_user();
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $course0 = $DB->get_record('course', []);
        $course1 = $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $tenant2->categoryid]);

        $syscontext = context_system::instance();
        $coursecontext0 = context_course::instance($course0->id);
        $this->assertNull($coursecontext0->tenantid);
        $coursecontext1 = context_course::instance($course1->id);
        $this->assertEquals($tenant1->id, $coursecontext1->tenantid);
        $coursecontext2 = context_course::instance($course2->id);
        $this->assertEquals($tenant2->id, $coursecontext2->tenantid);

        assign_capability('moodle/course:view', CAP_ALLOW, $CFG->defaultuserroleid, $syscontext->id);

        $sql0 = new sql(
            "SELECT DISTINCT ctx.id
               FROM {context} ctx
             /* capjoin */
              WHERE ctx.contextlevel = :contextlevel /* capwhere */
           ORDER BY ctx.id ASC",
            ['contextlevel' => context_course::LEVEL]
        );

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user0->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user1->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext1->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user2->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext2->id], $contextids);

        assign_capability('moodle/course:view', CAP_ALLOW, $CFG->guestroleid, $syscontext->id);
        assign_capability('moodle/course:update', CAP_ALLOW, $CFG->guestroleid, $syscontext->id);
        assign_capability('moodle/course:view', CAP_ALLOW, $CFG->notloggedinroleid, $syscontext->id);
        assign_capability('moodle/course:update', CAP_ALLOW, $CFG->notloggedinroleid, $syscontext->id);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', 0, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:update', 0, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $guest->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:update', $guest->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        set_config('allowguests', '1', 'tool_mutenancy');

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user0->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user1->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext1->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $user2->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext2->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', 0, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:update', 0, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:view', $guest->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([$coursecontext0->id, $coursecontext1->id, $coursecontext2->id], $contextids);

        $joins = context_map::get_contexts_by_capability_join('moodle/course:update', $guest->id, 'ctx', false);
        $sql = $sql0->replace_comment('capjoin', $joins['join']);
        $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
        $contextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
        $this->assertEquals([], $contextids);
    }

    /**
     * Use random roles and overrides to test against regular has_capability().
     */
    public function test_get_contexts_by_capability_join_random(): void {
        global $DB, $CFG;
        $this->preventResetByRollback(); // This helps with debugging in case of any failures!

        $admin = get_admin();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $guestroleid = $CFG->guestroleid;
        $defaultfrontpageroleid = $CFG->defaultfrontpageroleid;
        $notloggedinroleid = $CFG->notloggedinroleid;

        for ($i = 0; $i < 100; $i++) {
            $this->getDataGenerator()->create_user();
        }
        $categories = [0];
        for ($i = 0; $i < 10; $i++) {
            $parent = $categories[rand(0, array_key_last($categories))];
            $category = $this->getDataGenerator()->create_category(['parent' => $parent]);
            $categories[] = $category->id;
        }
        array_shift($categories);

        for ($i = 0; $i < 20; $i++) {
            $categoryid = $categories[rand(0, array_key_last($categories))];
            $course = $this->getDataGenerator()->create_course(['category' => $categoryid]);
            for ($f = 0; $f < 30; $f++) {
                $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
            }
        }

        $allcontextids = $DB->get_fieldset_select('context', 'id', "contextlevel <= 50");
        $alluserids = $DB->get_fieldset('user', 'id', []);

        $funcoveriderole = function (string $capability, int $roleid, array $allcontextids): void {
            $permissions = [CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT];
            for ($i = 0; $i < 50; $i++) {
                $permission = $permissions[rand(0, array_key_last($permissions))];
                $contextid = $allcontextids[rand(0, array_key_last($allcontextids))];
                assign_capability($capability, $permission, $roleid, $contextid);
            }
        };
        $funcoveriderole('moodle/course:view', $managerroleid, $allcontextids);
        $funcoveriderole('moodle/course:view', $studentroleid, $allcontextids);
        $funcoveriderole('moodle/course:view', $defaultfrontpageroleid, $allcontextids);
        $funcoveriderole('moodle/course:view', $guestroleid, $allcontextids);
        $funcoveriderole('moodle/course:view', $notloggedinroleid, $allcontextids);

        $funcoveriderole('moodle/course:update', $managerroleid, $allcontextids);
        $funcoveriderole('moodle/course:update', $studentroleid, $allcontextids);
        $funcoveriderole('moodle/course:update', $defaultfrontpageroleid, $allcontextids);
        $funcoveriderole('moodle/course:update', $guestroleid, $allcontextids);
        $funcoveriderole('moodle/course:update', $notloggedinroleid, $allcontextids);

        $funcassignrole = function (int $roleid, array $alluserids, array $allcontextids): void {
            global $CFG;
            for ($i = 0; $i < 20; $i++) {
                $userid = $alluserids[rand(0, array_key_last($alluserids))];
                if ($userid == $CFG->siteguest) {
                    continue;
                }
                $contextid = $allcontextids[rand(0, array_key_last($allcontextids))];
                role_assign($roleid, $userid, $contextid);
            }
        };
        $funcassignrole($managerroleid, $alluserids, $allcontextids);
        $funcassignrole($studentroleid, $alluserids, $allcontextids);
        $funcassignrole($guestroleid, $alluserids, $allcontextids);

        $alluserids[] = 0;
        $alluserids[] = -$admin->id;
        $capabilities = ['moodle/course:view', 'moodle/course:view', 'moodle/site:config'];
        foreach ($capabilities as $capability) {
            foreach ($alluserids as $userid) {
                $doanything = true;
                if ($userid == -$admin->id) {
                    $userid = -$userid;
                    $doanything = false;
                }
                $this->setUser($userid); // Speed up has_capability().
                $sql = new sql(
                    "SELECT DISTINCT ctx.id
                       FROM {context} ctx
                     /* capjoin */
                      WHERE 1=1 /* capwhere */
                   ORDER BY ctx.id ASC"
                );
                $joins = context_map::get_contexts_by_capability_join($capability, $userid, 'ctx', $doanything);
                $sql = $sql->replace_comment('capjoin', $joins['join']);
                $sql = $sql->replace_comment('capwhere', $joins['where']->wrap("AND ", ""));
                $allowcontextids = $DB->get_fieldset_sql($sql->sql, $sql->params);
                foreach ($allcontextids as $contextid) {
                    $context = context::instance_by_id($contextid);
                    if (in_array($contextid, $allowcontextids)) {
                        $this->assertTrue(has_capability($capability, $context, null, $doanything));
                    } else {
                        $this->assertFalse(has_capability($capability, $context, null, $doanything));
                    }
                }
            }
        }
    }
}
