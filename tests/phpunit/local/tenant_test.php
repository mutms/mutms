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

namespace tool_mutenancy\phpunit\local;

use tool_mutenancy\local\tenant;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\manager;
use tool_mutenancy\local\config;

/**
 * Multi-tenancy tenant tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mutenancy\local\tenant
 */
final class tenant_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @coversNothing
     */
    public function test_idnumber_regex(): void {
        $this->assertSame(1, preg_match(tenant::IDNUMBER_REGEX, 'abcxyz01234567890'));
        $this->assertSame(1, preg_match(tenant::IDNUMBER_REGEX, 'AbC'));
        $this->assertSame(0, preg_match(tenant::IDNUMBER_REGEX, '123'));
        $this->assertSame(0, preg_match(tenant::IDNUMBER_REGEX, '0a'));
        $this->assertSame(0, preg_match(tenant::IDNUMBER_REGEX, 'a1 '));
        $this->assertSame(0, preg_match(tenant::IDNUMBER_REGEX, '1A'));
        $this->assertSame(0, preg_match(tenant::IDNUMBER_REGEX, 'a1š'));
    }

    /**
     * @covers ::create
     */
    public function test_create(): void {
        global $DB;
        tenancy::activate();

        $syscontext = \context_system::instance();
        $acohort = $this->getDataGenerator()->create_cohort();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $this->setCurrentTimeStart();
        $tenant1 = tenant::create($data);
        $this->assertSame('Some tenant 1', $tenant1->name);
        $this->assertSame('t1', $tenant1->idnumber);
        $this->assertSame('0', $tenant1->loginshow);
        $this->assertSame(null, $tenant1->memberlimit);
        $this->assertSame(null, $tenant1->assoccohortid);
        $this->assertSame(null, $tenant1->sitefullname);
        $this->assertSame(null, $tenant1->siteshortname);
        $this->assertSame('0', $tenant1->archived);
        $this->assertTimeCurrent($tenant1->timecreated);
        $this->assertSame($tenant1->timecreated, $tenant1->timemodified);
        $tenantcontext = \context_tenant::instance($tenant1->id);
        $category = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $this->assertSame('Some tenant 1', $category->name);
        $this->assertSame('', $category->idnumber);
        $this->assertSame('', $category->description);
        $this->assertSame('0', $category->parent);
        $this->assertSame('1', $category->visible);
        $catcontext = \context_coursecat::instance($category->id);
        $this->assertSame((int)$tenant1->id, $catcontext->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('Tenant users: Some tenant 1', $cohort->name);
        $this->assertSame('', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('tool_mutenancy', $cohort->component);

        $data = (object)[
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
            'loginshow' => 1,
            'memberlimit' => 11,
            'assoccohortid' => $acohort->id,
            'sitefullname' => 'site full n2',
            'siteshortname' => 'sfn2',
            'categoryname' => 'New cat 2',
            'categoryidnumber' => 'NC2',
            'cohortname' => 'New kohorta 2',
            'cohortidnumber' => 'NK2',
        ];
        $this->setCurrentTimeStart();
        $tenant1 = tenant::create($data);
        $this->assertSame('Some tenant 2', $tenant1->name);
        $this->assertSame('t2', $tenant1->idnumber);
        $this->assertSame('1', $tenant1->loginshow);
        $this->assertSame('11', $tenant1->memberlimit);
        $this->assertSame($acohort->id, $tenant1->assoccohortid);
        $this->assertSame('site full n2', $tenant1->sitefullname);
        $this->assertSame('sfn2', $tenant1->siteshortname);
        $this->assertSame('0', $tenant1->archived);
        $this->assertTimeCurrent($tenant1->timecreated);
        $this->assertSame($tenant1->timecreated, $tenant1->timemodified);
        $tenantcontext = \context_tenant::instance($tenant1->id);
        $category = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $this->assertSame('New cat 2', $category->name);
        $this->assertSame('NC2', $category->idnumber);
        $this->assertSame('', $category->description);
        $this->assertSame('0', $category->parent);
        $this->assertSame('1', $category->visible);
        $catcontext = \context_coursecat::instance($category->id);
        $this->assertSame((int)$tenant1->id, $catcontext->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('New kohorta 2', $cohort->name);
        $this->assertSame('NK2', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('tool_mutenancy', $cohort->component);

        $category3 = $this->getDataGenerator()->create_category();
        $data = (object)[
            'name' => 'Some tenant 3',
            'idnumber' => 't3',
            'categoryid' => $category3->id,
        ];
        $tenant3 = tenant::create($data);
        $this->assertSame($category3->id, $tenant3->categoryid);
        $catcontext3 = \context_coursecat::instance($category3->id);
        $this->assertSame((int)$tenant3->id, $catcontext3->tenantid);

        $data = (object)[
            'name' => 'Some tenant 3b',
            'idnumber' => 't3b',
            'assoccohortcreate' => 1,
        ];
        $tenant3b = tenant::create($data);
        $cohort = $DB->get_record('cohort', ['id' => $tenant3b->assoccohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('Associated users: Some tenant 3b', $cohort->name);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('', $cohort->component);

        $data = (object)[
            'name' => 'Some tenant 4',
            'idnumber' => 't4',
            'categoryid' => $category3->id,
        ];
        try {
            tenant::create($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Cannot use other tenant category)', $ex->getMessage());
        }

        $category4 = $this->getDataGenerator()->create_category(['parent' => $category3->id]);
        $data = (object)[
            'name' => 'Some tenant 4',
            'idnumber' => 't4',
            'categoryid' => $category4->id,
        ];
        try {
            tenant::create($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Only top level category can be tenant category)', $ex->getMessage());
        }

        $data = (object)[
            'name' => 'Some tenant 1x',
            'idnumber' => 't1',
        ];
        try {
            tenant::create($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (duplicate tenant idnumber)', $ex->getMessage());
        }

        $data = (object)[
            'name' => 'Some tenant 1x',
            'idnumber' => 'T1',
        ];
        try {
            tenant::create($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (duplicate tenant idnumber)', $ex->getMessage());
        }

        $data = (object)[
            'name' => 'Some tenant 1x',
            'idnumber' => '1t',
        ];
        try {
            tenant::create($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (invalid tenant idnumber format)', $ex->getMessage());
        }

        $data = (object)[
            'name' => 'Some tenant 1x',
            'idnumber' => '1t',
        ];
        try {
            tenant::create($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (invalid tenant idnumber format)', $ex->getMessage());
        }

        $data = (object)[
            'name' => ' ',
            'idnumber' => 'x1',
        ];
        try {
            tenant::create($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (missing tenant name)', $ex->getMessage());
        }
    }

    /**
     * @covers ::update
     */
    public function test_update(): void {
        global $DB;
        tenancy::activate();

        $syscontext = \context_system::instance();
        $acohort = $this->getDataGenerator()->create_cohort();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1old = tenant::create($data);

        $data = (object)[
            'id' => $tenant1old->id,
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
            'loginshow' => 1,
            'memberlimit' => 11,
            'assoccohortid' => $acohort->id,
            'sitefullname' => 'site full n2',
            'siteshortname' => 'sfn2',
            'categoryname' => 'New cat 2',
            'categoryidnumber' => 'NC2',
            'cohortname' => 'New kohorta 2',
            'cohortidnumber' => 'NK2',
        ];
        $this->setCurrentTimeStart();
        $tenant1 = tenant::update($data);
        $this->assertSame('Some tenant 2', $tenant1->name);
        $this->assertSame('t2', $tenant1->idnumber);
        $this->assertSame('1', $tenant1->loginshow);
        $this->assertSame('11', $tenant1->memberlimit);
        $this->assertSame($tenant1old->cohortid, $tenant1->cohortid);
        $this->assertSame($tenant1old->categoryid, $tenant1->categoryid);
        $this->assertSame($acohort->id, $tenant1->assoccohortid);
        $this->assertSame('site full n2', $tenant1->sitefullname);
        $this->assertSame('sfn2', $tenant1->siteshortname);
        $this->assertSame('0', $tenant1->archived);
        $this->assertSame($tenant1old->timecreated, $tenant1->timecreated);
        $this->assertTimeCurrent($tenant1->timemodified);
        $category = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $this->assertSame('New cat 2', $category->name);
        $this->assertSame('NC2', $category->idnumber);
        $this->assertSame('', $category->description);
        $this->assertSame('0', $category->parent);
        $this->assertSame('1', $category->visible);
        $catcontext = \context_coursecat::instance($category->id);
        $this->assertSame((int)$tenant1->id, $catcontext->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('New kohorta 2', $cohort->name);
        $this->assertSame('NK2', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('tool_mutenancy', $cohort->component);

        $data = (object)[
            'id' => $tenant1old->id,
            'name' => 'Some tenant 1',
            'idnumber' => 't2',
            'loginshow' => 0,
            'memberlimit' => 0,
            'assoccohortid' => '',
            'sitefullname' => '',
            'siteshortname' => '',
        ];
        $this->setCurrentTimeStart();
        $tenant1 = tenant::update($data);
        $this->assertSame('Some tenant 1', $tenant1->name);
        $this->assertSame('t2', $tenant1->idnumber);
        $this->assertSame('0', $tenant1->loginshow);
        $this->assertSame(null, $tenant1->memberlimit);
        $this->assertSame($tenant1old->cohortid, $tenant1->cohortid);
        $this->assertSame($tenant1old->categoryid, $tenant1->categoryid);
        $this->assertSame(null, $tenant1->assoccohortid);
        $this->assertSame(null, $tenant1->sitefullname);
        $this->assertSame(null, $tenant1->siteshortname);
        $this->assertSame('0', $tenant1->archived);
        $this->assertSame($tenant1old->timecreated, $tenant1->timecreated);
        $this->assertTimeCurrent($tenant1->timemodified);
        $category = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $this->assertSame('New cat 2', $category->name);
        $this->assertSame('NC2', $category->idnumber);
        $this->assertSame('', $category->description);
        $this->assertSame('0', $category->parent);
        $this->assertSame('1', $category->visible);
        $catcontext = \context_coursecat::instance($category->id);
        $this->assertSame((int)$tenant1->id, $catcontext->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('New kohorta 2', $cohort->name);
        $this->assertSame('NK2', $cohort->idnumber);
        $this->assertSame('', $cohort->description);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('tool_mutenancy', $cohort->component);

        $data = (object)[
            'id' => $tenant1->id,
            'assoccohortcreate' => 1,
        ];
        $tenant1 = tenant::update($data);
        $cohort = $DB->get_record('cohort', ['id' => $tenant1->assoccohortid], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $cohort->contextid);
        $this->assertSame('Associated users: Some tenant 1', $cohort->name);
        $this->assertSame('0', $cohort->visible);
        $this->assertSame('', $cohort->component);

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant2 = tenant::create($data);

        $data = (object)[
            'id' => $tenant1->id,
            'idnumber' => 't1',
        ];
        try {
            tenant::update($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (duplicate tenant idnumber)', $ex->getMessage());
        }

        $data = (object)[
            'id' => $tenant1->id,
            'idnumber' => '1t',
        ];
        try {
            tenant::update($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (invalid tenant idnumber format)', $ex->getMessage());
        }

        $data = (object)[
            'id' => $tenant2->id,
            'categoryidnumber' => 'NC2',
        ];
        try {
            tenant::update($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Category idnumber already exists)', $ex->getMessage());
        }

        $data = (object)[
            'id' => $tenant2->id,
            'cohortidnumber' => 'NK2',
        ];
        try {
            tenant::update($data);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (Cohort idnumber already exists)', $ex->getMessage());
        }
    }

    /**
     * @covers ::archive
     */
    public function test_archive(): void {
        global $DB;

        tenancy::activate();

        set_config('registerauth', 'email');

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1old = tenant::create($data);
        $category1old = \core_course_category::get($tenant1old->categoryid, MUST_EXIST, true);
        $this->assertSame('1', $category1old->visible);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1old->id]);
        $this->assertSame('email', config::get($tenant1old->id, 'core', 'registerauth'));

        $this->setCurrentTimeStart();
        $tenant1 = tenant::archive($tenant1old->id);
        $this->assertSame('Some tenant 1', $tenant1->name);
        $this->assertSame('t1', $tenant1->idnumber);
        $this->assertSame('1', $tenant1->archived);
        $this->assertSame($tenant1old->timecreated, $tenant1->timecreated);
        $this->assertTimeCurrent($tenant1->timemodified);
        $category1 = \core_course_category::get($tenant1->categoryid, MUST_EXIST, true);
        $this->assertSame('0', $category1->visible);
        $this->assertSame('1', $category1->visibleold);
        $user1 = $DB->get_record('user', ['id' => $user1->id]);
        $this->assertSame('0', $user1->suspended);
        $this->assertSame('', config::get($tenant1->id, 'core', 'registerauth'));
    }

    /**
     * @covers ::restore
     */
    public function test_restore(): void {
        global $DB;
        tenancy::activate();

        set_config('registerauth', 'email');

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1old = tenant::create($data);
        $category1old = \core_course_category::get($tenant1old->categoryid, MUST_EXIST, true);
        $this->assertSame('1', $category1old->visible);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1old->id]);
        $this->assertSame('email', config::get($tenant1old->id, 'core', 'registerauth'));

        $tenant1old = tenant::archive($tenant1old->id);

        $this->setCurrentTimeStart();
        $tenant1 = tenant::restore($tenant1old->id);
        $this->assertSame('Some tenant 1', $tenant1->name);
        $this->assertSame('t1', $tenant1->idnumber);
        $this->assertSame('0', $tenant1->archived);
        $this->assertSame($tenant1old->timecreated, $tenant1->timecreated);
        $this->assertTimeCurrent($tenant1->timemodified);
        $category1 = \core_course_category::get($tenant1->categoryid, MUST_EXIST, true);
        $this->assertSame('1', $category1->visible);
        $this->assertSame('1', $category1->visibleold);
        $user1 = $DB->get_record('user', ['id' => $user1->id]);
        $this->assertSame('0', $user1->suspended);
        $this->assertSame('', config::get($tenant1->id, 'core', 'registerauth'));
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        global $DB;

        tenancy::activate();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1 = tenant::create($data);
        $data = (object)[
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
        ];
        $tenant2 = tenant::create($data);

        $acohort = $this->getDataGenerator()->create_cohort();
        $category = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $catcontext1 = \context_coursecat::instance($category->id);
        $tenantcontext1 = \context_tenant::instance($tenant1->id);
        $cohort = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $this->assertSame(null, $user0->tenantid);
        $this->assertSame($tenant1->id, $user1->tenantid);
        $this->assertSame($tenant2->id, $user2->tenantid);
        manager::add($tenant1->id, $user0->id);
        manager::add($tenant2->id, $user0->id);
        config::override($tenant1->id, 'set', '1', 'tool_xyz');
        config::override($tenant2->id, 'set', '2', 'tool_xyz');
        $t1 = tenant::fetch($tenant1->id);
        $t2 = tenant::fetch($tenant2->id);

        $tenant1 = tenant::archive($tenant1->id);
        tenant::delete($tenant1->id);

        $this->assertFalse($DB->record_exists('tool_mutenancy_tenant', ['id' => $tenant1->id]));
        $category = $DB->get_record('course_categories', ['id' => $category->id], '*', MUST_EXIST);
        $catcontext1 = \context_coursecat::instance($category->id);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);
        $this->assertSame(null, $catcontext1->tenantid);
        $cohort = $DB->get_record('cohort', ['id' => $cohort->id], '*', MUST_EXIST);
        $this->assertSame('', $cohort->component);
        $acohort = $DB->get_record('cohort', ['id' => $acohort->id], '*', MUST_EXIST);
        $user1 = $DB->get_record('user', ['id' => $user1->id], '*', MUST_EXIST);
        $user2 = $DB->get_record('user', ['id' => $user2->id], '*', MUST_EXIST);
        $this->assertSame(null, $user1->tenantid);
        $this->assertSame('1', $user1->suspended);
        $this->assertSame($tenant2->id, $user2->tenantid);
        $this->assertSame('0', $user2->suspended);
        $usercontext1 = \context_user::instance($user1->id);
        $usercontext2 = \context_user::instance($user2->id);
        $this->assertSame(null, $usercontext1->tenantid);
        $this->assertSame((int)$tenant2->id, $usercontext2->tenantid);
        $this->assertFalse($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant1->id, 'userid' => $user0->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_manager', ['tenantid' => $tenant2->id, 'userid' => $user0->id]));
        $this->assertFalse($DB->record_exists('tool_mutenancy_config', ['tenantid' => $tenant1->id]));
        $this->assertTrue($DB->record_exists('tool_mutenancy_config', ['tenantid' => $tenant2->id]));
        $t1 = tenant::fetch($tenant1->id);
        $this->assertNull($t1);
        $t2 = tenant::fetch($tenant2->id);
        $this->assertNotNull($t2);
        $this->assertFalse(has_capability('tool/mutenancy:membercreate', $catcontext1, $user0));
        $this->assertTrue(has_capability('tool/mutenancy:membercreate', $catcontext2, $user0));
        $this->assertTrue(has_capability('tool/mutenancy:membercreate', \context_tenant::instance($tenant2->id), $user0));
        $this->assertFalse(\core\context\tenant::instance($tenant1->id, IGNORE_MISSING));

        try {
            tenant::delete($tenant2->id);
            $this->assertFalse('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Only archived tenants can be deleted.', $ex->getMessage());
        }

        tenant::archive($tenant2->id);
        tenant::delete($tenant2->id, false);
        $user2 = $DB->get_record('user', ['id' => $user2->id], '*', MUST_EXIST);
        $this->assertSame(null, $user2->tenantid);
        $this->assertSame('0', $user2->suspended);
    }

    /**
     * @covers ::fetch
     */
    public function test_fetch(): void {
        global $DB;

        tenancy::activate();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1 = tenant::create($data);
        $data = (object)[
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
        ];
        $tenant2 = tenant::create($data);

        $t1 = tenant::fetch($tenant1->id);
        $this->assertInstanceOf(\stdClass::class, $t1);
        $this->assertFalse($tenant1 === $t1);
        $this->assertSame((array)$tenant1, (array)$t1);

        $this->assertNull(tenant::fetch($tenant2->id + 10));

        $DB->delete_records('tool_mutenancy_tenant', ['id' => $tenant1->id]);
        $t1 = tenant::fetch($tenant1->id);
        $this->assertSame((array)$tenant1, (array)$t1);

        \cache_helper::purge_by_event('tool_mutenancy_invalidatecaches');
        $this->assertNull(tenant::fetch($tenant1->id));
    }

    /**
     * @covers ::fetch_by_idnumber
     */
    public function test_fetch_by_idnumber(): void {
        global $DB;

        tenancy::activate();

        $data = (object)[
            'name' => 'Some tenant 1',
            'idnumber' => 't1',
        ];
        $tenant1 = tenant::create($data);
        $data = (object)[
            'name' => 'Some tenant 2',
            'idnumber' => 't2',
        ];
        $tenant2 = tenant::create($data);

        $t1 = tenant::fetch_by_idnumber($tenant1->idnumber);
        $this->assertInstanceOf(\stdClass::class, $t1);
        $this->assertFalse($tenant1 === $t1);
        $this->assertSame((array)$tenant1, (array)$t1);

        $this->assertNull(tenant::fetch_by_idnumber('vvvvv'));

        $DB->delete_records('tool_mutenancy_tenant', ['id' => $tenant1->id]);
        $t1 = tenant::fetch_by_idnumber($tenant1->idnumber);
        $this->assertSame((array)$tenant1, (array)$t1);

        \cache_helper::purge_by_event('tool_mutenancy_invalidatecaches');
        $this->assertNull(tenant::fetch_by_idnumber($tenant1->idnumber));
    }

    /**
     * @covers ::get_login_url
     */
    public function test_get_login_url(): void {
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant(['archived' => 1]);

        $result = tenant::get_login_url($tenant1->id);
        $this->assertInstanceOf(\moodle_url::class, $result);
        $this->assertSame('/login/?tenant=ten1', $result->out_as_local_url(false));

        $result = tenant::get_login_url($tenant2->id);
        $this->assertNull($result);
    }

    /**
     * @covers ::cohort_deleted
     */
    public function test_cohort_deleted(): void {
        global $DB;

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $tenant1 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant2 = $generator->create_tenant(['assoccohortid' => $cohort2->id]);
        $tenant3 = $generator->create_tenant(['assoccohortid' => $cohort1->id]);
        $tenant4 = $generator->create_tenant([]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort2->id, $user2->id);
        cohort_add_member($cohort3->id, $user3->id);

        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant2->cohortid, 'userid' => $user2->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant3->cohortid, 'userid' => $user1->id]));

        cohort_delete_cohort($cohort1);
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant1->cohortid, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('cohort_members', ['cohortid' => $tenant2->cohortid, 'userid' => $user2->id]));
        $this->assertFalse($DB->record_exists('cohort_members', ['cohortid' => $tenant3->cohortid, 'userid' => $user1->id]));
        $tenant1 = tenant::fetch($tenant1->id);
        $tenant2 = tenant::fetch($tenant2->id);
        $tenant3 = tenant::fetch($tenant3->id);
        $this->assertNull($tenant1->assoccohortid);
        $this->assertSame($cohort2->id, $tenant2->assoccohortid);
        $this->assertNull($tenant3->assoccohortid);
    }
}
