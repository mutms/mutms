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

namespace tool_mutenancy\local;

use stdClass;
use tool_mutenancy\local\tenancy;

/**
 * Multi-tenancy tenant helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant {
    /** @var string we need an accurate way to distinguish id from idnumber  */
    public const IDNUMBER_REGEX = '/^[a-zA-Z][0-9a-zA-Z]+$/D';

    /**
     * Create new tenant.
     *
     * @param stdClass $data
     * @return stdClass tool_mutenancy_tenant record
     */
    public static function create(stdClass $data): stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        if (!tenancy::is_active()) {
            throw new \core\exception\coding_exception('Tenancy was not activated');
        }

        $syscontext = \context_system::instance();

        $record = new stdClass();

        if (trim($data->name ?? '') === '') {
            throw new \core\exception\invalid_parameter_exception('missing tenant name');
        }
        if (\core_text::strlen($data->name) > 255) {
            throw new \core\exception\invalid_parameter_exception('tenant name too long');
        }

        $record->name = $data->name;

        if (trim($data->idnumber ?? '') === '') {
            throw new \core\exception\invalid_parameter_exception('missing tenant idnumber');
        }
        if (!preg_match(self::IDNUMBER_REGEX, $data->idnumber)) {
            throw new \core\exception\invalid_parameter_exception('invalid tenant idnumber format');
        }
        if ($DB->record_exists_select('tool_mutenancy_tenant', 'LOWER(idnumber) = LOWER(?)', [$data->idnumber])) {
            throw new \core\exception\invalid_parameter_exception('duplicate tenant idnumber');
        }
        if (\core_text::strlen($data->idnumber) > 50) {
            throw new \core\exception\invalid_parameter_exception('tenant idnumber too long');
        }
        $record->idnumber = $data->idnumber;

        $record->loginshow = (int)(bool)($data->loginshow ?? 0);

        $record->memberlimit = (int)($data->memberlimit ?? 0);
        if ($record->memberlimit <= 0) {
            $record->memberlimit = null;
        }

        if (!empty($data->assoccohortcreate)) {
            $cohort = (object)[
                'name' => get_string('tenant_associates', 'tool_mutenancy') . ': ' . $record->name,
                'contextid' => $syscontext->id,
                'visible' => 0,
                'idnumber' => '',
            ];
            $record->assoccohortid = cohort_add_cohort($cohort);
        } else if (!empty($data->assoccohortid)) {
            $cohort = $DB->get_record('cohort', ['id' => $data->assoccohortid], '*', MUST_EXIST);
            if (
                $cohort->component === 'tool_mutenancy'
                || $DB->record_exists('tool_mutenancy_tenant', ['cohortid' => $data->assoccohortid])
            ) {
                throw new \core\exception\invalid_parameter_exception('tenant cohort cannot be used in assoccohortid');
            }
            $record->assoccohortid = $cohort->id;
        } else {
            $record->assoccohortid = null;
        }

        if (trim($data->sitefullname ?? '') === '') {
            $data->sitefullname = null;
        } else if (\core_text::strlen($data->sitefullname) > 255) {
            throw new \core\exception\invalid_parameter_exception('tenant sitefullname too long');
        }
        $record->sitefullname = $data->sitefullname;

        if (trim($data->siteshortname ?? '') === '') {
            $data->siteshortname = null;
        } else if (\core_text::strlen($data->siteshortname) > 255) {
            throw new \core\exception\invalid_parameter_exception('tenant siteshortname too long');
        }
        $record->siteshortname = $data->siteshortname;

        $record->archived = (int)(bool)($data->archived ?? 0);

        $now = time();
        $trans = $DB->start_delegated_transaction();

        if (!empty($data->categoryid)) {
            // Use existing category.
            $category = $DB->get_record('course_categories', ['id' => $data->categoryid], '*', MUST_EXIST);
            if ($DB->record_exists('tool_mutenancy_tenant', ['categoryid' => $category->id])) {
                throw new \core\exception\invalid_parameter_exception('Cannot use other tenant category');
            }
            if ($category->parent) {
                throw new \core\exception\invalid_parameter_exception('Only top level category can be tenant category');
            }
            $record->categoryid = $category->id;
        } else {
            // Create new category.
            $category = (object)[
                'name' => $record->name,
                'parent' => 0,
                'visible' => 1,
                'idnumber' => '',
            ];
            if (trim($data->categoryname ?? '') !== '') {
                $category->name = $data->categoryname;
            }
            if (trim($data->categoryidnumber ?? '') !== '') {
                $data->categoryidnumber = trim($data->categoryidnumber);
                if (!$DB->record_exists_select('course_categories', "LOWER(idnumber) = LOWER(?)", [$data->categoryidnumber])) {
                    $category->idnumber = $data->categoryidnumber;
                } else {
                    throw new \core\exception\invalid_parameter_exception('Category idnumber already exists');
                }
            }
            $category = \core_course_category::create($category);
            $record->categoryid = $category->id;
        }

        // Create new hidden cohort for members in system context,
        // it would not be useful inside the tenant category much.
        $cohort = (object)[
            'name' => tenancy::get_tenant_string('tenant_users') . ': ' . $data->name,
            'contextid' => $syscontext->id,
            'visible' => 0,
            'component' => 'tool_mutenancy',
            'idnumber' => '',
        ];
        if (trim($data->cohortname ?? '') !== '') {
            $cohort->name = $data->cohortname;
        }
        if (trim($data->cohortidnumber ?? '') !== '') {
            $data->cohortidnumber = trim($data->cohortidnumber);
            if (!$DB->record_exists_select('cohort', "LOWER(idnumber) = LOWER(?)", [$data->cohortidnumber])) {
                $cohort->idnumber = $data->cohortidnumber;
            } else {
                throw new \core\exception\invalid_parameter_exception('Cohort idnumber already exists');
            }
        }
        $cohort->id = cohort_add_cohort($cohort);
        $record->cohortid = $cohort->id;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $record->id = $DB->insert_record('tool_mutenancy_tenant', $record);
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $record->id], '*', MUST_EXIST);

        // Force context creation.
        $tenantcontext = \context_tenant::instance($tenant->id);

        // Fix tenantid in category contexts.
        $categorycontext = \context_coursecat::instance($category->id);
        $DB->set_field('context', 'tenantid', $tenant->id, ['id' => $categorycontext->id]);
        $sql = "UPDATE {context}
                   SET tenantid=:tenantid
                 WHERE path LIKE :path";
        $params = [
            'tenantid' => $tenant->id,
            'path' => $categorycontext->path . '/%',
        ];
        $DB->execute($sql, $params);
        $categorycontext->mark_dirty();

        \tool_mutenancy\event\tenant_created::create_from_tenant($tenant)->trigger();

        $trans->allow_commit();

        accesslib_clear_all_caches(true);
        \cache_helper::purge_by_event('changesincoursecat');

        if ($record->assoccohortid) {
            user::sync($tenant->id, null);
        }

        return $tenant;
    }

    /**
     * Update tenant.
     *
     * @param stdClass $data
     * @return stdClass tool_mutenancy_tenant record
     */
    public static function update(stdClass $data): stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $oldtenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $data->id], '*', MUST_EXIST);
        $syscontext = \context_system::instance();

        $record = clone($oldtenant);

        if (property_exists($data, 'name')) {
            if (trim($data->name ?? '') === '') {
                throw new \core\exception\invalid_parameter_exception('missing tenant name');
            }
            if (\core_text::strlen($data->name) > 255) {
                throw new \core\exception\invalid_parameter_exception('tenant name too long');
            }
            $record->name = $data->name;
        }

        if (property_exists($data, 'idnumber')) {
            if (trim($data->idnumber ?? '') === '') {
                throw new \core\exception\invalid_parameter_exception('missing tenant idnumber');
            }
            if (!preg_match(self::IDNUMBER_REGEX, $data->idnumber)) {
                throw new \core\exception\invalid_parameter_exception('invalid tenant idnumber format');
            }
            if ($DB->record_exists_select('tool_mutenancy_tenant', 'LOWER(idnumber) = LOWER(?) AND id <> ?', [$data->idnumber, $oldtenant->id])) {
                throw new \core\exception\invalid_parameter_exception('duplicate tenant idnumber');
            }
            if (\core_text::strlen($data->idnumber) > 50) {
                throw new \core\exception\invalid_parameter_exception('tenant idnumber too long');
            }
            $record->idnumber = $data->idnumber;
        }

        if (property_exists($data, 'loginshow')) {
            $record->loginshow = (int)(bool)($data->loginshow);
        }

        if (property_exists($data, 'memberlimit')) {
            $record->memberlimit = (int)($data->memberlimit);
            if ($record->memberlimit <= 0) {
                $record->memberlimit = null;
            }
        }

        if (!$oldtenant->assoccohortid && !empty($data->assoccohortcreate)) {
            $cohort = (object)[
                'name' => get_string('tenant_associates', 'tool_mutenancy') . ': ' . $record->name,
                'contextid' => $syscontext->id,
                'visible' => 0,
                'idnumber' => '',
            ];
            $record->assoccohortid = cohort_add_cohort($cohort);
        } else if (property_exists($data, 'assoccohortid')) {
            if (!empty($data->assoccohortid)) {
                $cohort = $DB->get_record('cohort', ['id' => $data->assoccohortid], '*', MUST_EXIST);
                if (
                    $cohort->component === 'tool_mutenancy'
                    || $DB->record_exists('tool_mutenancy_tenant', ['cohortid' => $data->assoccohortid])
                ) {
                    throw new \core\exception\invalid_parameter_exception('tenant cohort cannot be used in assoccohortid');
                }
                $record->assoccohortid = $cohort->id;
            } else {
                $record->assoccohortid = null;
            }
        }

        if (property_exists($data, 'sitefullname')) {
            if (trim($data->sitefullname ?? '') === '') {
                $data->sitefullname = null;
            } else if (\core_text::strlen($data->sitefullname) > 255) {
                throw new \core\exception\invalid_parameter_exception('tenant sitefullname too long');
            }
            $record->sitefullname = $data->sitefullname;
        }

        if (property_exists($data, 'siteshortname')) {
            if (trim($data->siteshortname ?? '') === '') {
                $data->siteshortname = null;
            } else if (\core_text::strlen($data->siteshortname) > 255) {
                throw new \core\exception\invalid_parameter_exception('tenant siteshortname too long');
            }
            $record->siteshortname = $data->siteshortname;
        }

        $now = time();
        $trans = $DB->start_delegated_transaction();

        // Update category if data provided.
        $category = $DB->get_record('course_categories', ['id' => $oldtenant->categoryid]);
        $categoryupdate = [];
        if ($category) {
            if (property_exists($data, 'categoryname') && trim($data->categoryname) !== '' && $data->categoryname !== $category->name) {
                $categoryupdate['name'] = $data->categoryname;
            }
            if (property_exists($data, 'categoryidnumber') && $data->categoryidnumber !== $category->idnumber) {
                $data->categoryidnumber = trim($data->categoryidnumber);
                if ($data->categoryidnumber === '') {
                    $categoryupdate['idnumber'] = '';
                } else if (!$DB->record_exists_select('course_categories', "LOWER(idnumber) = LOWER(?) AND id <> ?", [$data->categoryidnumber, $category->id])) {
                    $categoryupdate['idnumber'] = $data->categoryidnumber;
                } else {
                    throw new \core\exception\invalid_parameter_exception('Category idnumber already exists');
                }
            }
        }
        if ($categoryupdate) {
            $categoryupdate['id'] = $category->id;
            $categoryobj = \core_course_category::get($oldtenant->categoryid, MUST_EXIST, true);
            $categoryobj->update((object)$categoryupdate);
        }

        // Update cagtegory if data provided.
        $cohort = $DB->get_record('cohort', ['id' => $oldtenant->cohortid]);
        $cohortupdate = [];
        if ($cohort) {
            if (property_exists($data, 'cohortname') && trim($data->cohortname) !== '' && $data->cohortname !== $cohort->name) {
                $cohortupdate['name'] = $data->cohortname;
            }
            if (property_exists($data, 'cohortidnumber') && $data->cohortidnumber !== $cohort->idnumber) {
                $data->cohortidnumber = trim($data->cohortidnumber);
                if ($data->cohortidnumber === '') {
                    $cohortupdate['idnumber'] = '';
                } else if (!$DB->record_exists_select('cohort', "LOWER(idnumber) = LOWER(?) AND id <> ?", [$data->cohortidnumber, $cohort->id])) {
                    $cohortupdate['idnumber'] = $data->cohortidnumber;
                } else {
                    throw new \core\exception\invalid_parameter_exception('Cohort idnumber already exists');
                }
            }
        }
        if ($cohortupdate) {
            foreach ($cohortupdate as $k => $v) {
                $cohort->{$k} = $v;
            }
            cohort_update_cohort($cohort);
        }

        $record->timemodified = $now;

        $DB->update_record('tool_mutenancy_tenant', $record);
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $record->id], '*', MUST_EXIST);

        \tool_mutenancy\event\tenant_updated::create_from_tenant($tenant)->trigger();

        $trans->allow_commit();

        $cache = \cache::make('tool_mutenancy', 'tenant');
        $cache->delete($tenant->id);
        $cache->delete($tenant->idnumber);
        if ($oldtenant->idnumber !== $tenant->idnumber) {
            $cache->delete($oldtenant->idnumber);
        }

        if ($oldtenant->assoccohortid != $tenant->assoccohortid) {
            user::sync($tenant->id, null);
        }

        return $tenant;
    }

    /**
     * Archive tenant.
     *
     * NOTE: this includes hiding of tenant category and disabling new account signups.
     *
     * @param int $tenantid
     * @return stdClass tool_mutenancy_tenant record
     */
    public static function archive(int $tenantid): stdClass {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
        $update = (object)[
            'id' => $tenant->id,
            'archived' => 1,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mutenancy_tenant', $update);

        // Hide category.
        $visibleold = $DB->get_field('course_categories', 'visibleold', ['id' => $tenant->categoryid]);
        $category = \core_course_category::get($tenant->categoryid, MUST_EXIST, true);
        $category->update(['id' => $tenant->categoryid, 'visible' => 0]);
        $DB->set_field('course_categories', 'visibleold', $visibleold, ['id' => $tenant->categoryid]);

        // Disable signup!
        config::override($tenant->id, 'registerauth', '', 'core');

        \tool_mutenancy\event\tenant_archived::create_from_tenant($tenant)->trigger();

        $trans->allow_commit();

        $cache = \cache::make('tool_mutenancy', 'tenant');
        $cache->delete($tenant->id);
        $cache->delete($tenant->idnumber);

        return $DB->get_record('tool_mutenancy_tenant', ['id' => $tenant->id], '*', MUST_EXIST);
    }

    /**
     * Restore archived tenant.
     *
     * @param int $tenantid
     * @return stdClass tool_mutenancy_tenant record
     */
    public static function restore(int $tenantid): stdClass {
        global $DB;

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
        $category = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
        $cohort = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);

        $trans = $DB->start_delegated_transaction();

        $update = (object)[
            'id' => $tenant->id,
            'archived' => 0,
            'timemodified' => time(),
        ];
        $DB->update_record('tool_mutenancy_tenant', $update);

        // Restore category visibility.
        $visibleold = $DB->get_field('course_categories', 'visibleold', ['id' => $tenant->categoryid]);
        if ($visibleold) {
            $category = \core_course_category::get($tenant->categoryid, MUST_EXIST, true);
            $category->update(['id' => $tenant->categoryid, 'visible' => 1]);
        }

        \tool_mutenancy\event\tenant_restored::create_from_tenant($tenant)->trigger();

        $trans->allow_commit();

        $cache = \cache::make('tool_mutenancy', 'tenant');
        $cache->delete($tenant->id);
        $cache->delete($tenant->idnumber);

        return $DB->get_record('tool_mutenancy_tenant', ['id' => $tenant->id], '*', MUST_EXIST);
    }

    /**
     * Delete tenant.
     *
     * @param int $tenantid
     * @param bool $suspendmembers true means suspend all users
     */
    public static function delete(int $tenantid, bool $suspendmembers = true): void {
        global $DB;

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid]);
        if (!$tenant) {
            return;
        }

        if (!$tenant->archived) {
            throw new \core\exception\coding_exception('Only archived tenants can be deleted.');
        }

        $trans = $DB->start_delegated_transaction();

        $hook = new \tool_mutenancy\hook\pre_tenant_delete($tenant);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        $syscontext = \context_system::instance();
        /** @var \context_tenant $tenantcontext */
        $tenantcontext = \context_tenant::instance($tenant->id);
        role_unassign_all(['contextid' => $tenantcontext->id]);

        $managerrole = manager::get_role();
        $categorycontext = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);
        if ($categorycontext) {
            role_unassign_all(['contextid' => $categorycontext->id, 'roleid' => $managerrole->id]);
        }

        // Invalidate block contexts in user contexts.
        if ($DB instanceof \mysqli_native_moodle_database) {
            // MySQL from Oracle is the worst choice, let's hack around its limitations here...
            // phpcs:disable moodle.Commenting.ValidTags.Invalid
            // phpcs:ignore moodle.Commenting.InlineComment.DocBlock
            $sql = /** @lang MySQL */
                "UPDATE /*+ NO_MERGE(pbi) */ {context},
                        (SELECT bi.id
                           FROM {block_instances} bi
                           JOIN {context} uc ON uc.id = bi.parentcontextid
                          WHERE uc.contextlevel = :userlevel) AS pbi
                    SET depth=0,path=null,tenantid=null
                  WHERE {context}.contextlevel = :blocklevel AND {context}.tenantid = :tenantid
                        AND {context}.instanceid = pbi.id";
            // phpcs:enable moodle.Commenting.ValidTags.Invalid
        } else {
            $sql = "UPDATE {context}
                       SET depth=0,path=null,tenantid=null
                     WHERE {context}.contextlevel = :blocklevel AND {context}.tenantid = :tenantid
                           AND {context}.instanceid IN (
                               SELECT bi.id
                                 FROM {block_instances} bi
                                 JOIN {context} uc ON uc.id = bi.parentcontextid
                                WHERE uc.contextlevel = :userlevel)";
        }
        $params = [
            'tenantid' => $tenant->id,
            'blocklevel' => CONTEXT_BLOCK,
            'userlevel' => CONTEXT_USER,
        ];
        $DB->execute($sql, $params);

        // Fix user contexts.
        $path = $DB->sql_concat(":parent", "{context}.id");
        $sql = "UPDATE {context}
                   SET depth=2,path=$path,tenantid=null
                 WHERE {context}.contextlevel = :userlevel AND {context}.tenantid = :tenantid";
        $params = [
            'tenantid' => $tenant->id,
            'userlevel' => CONTEXT_USER,
            'parent' => $syscontext->path . '/',
        ];
        $DB->execute($sql, $params);

        // Remove tenantid from all other contexts.
        $sql = "UPDATE {context}
                   SET tenantid=null
                 WHERE {context}.tenantid = :tenantid";
        $params = [
            'tenantid' => $tenant->id,
        ];
        $DB->execute($sql, $params);

        if ($suspendmembers) {
            $sql = "SELECT u.id
                      FROM {user} u
                     WHERE u.deleted = 0 AND u.suspended = 0
                           AND u.tenantid = :tenantid
                  ORDER BY u.id ASC";
            $rs = $DB->get_recordset_sql($sql, ['tenantid' => $tenant->id]);
            foreach ($rs as $user) {
                member::suspend($user->id);
            }
            $rs->close();
        }

        $DB->set_field('user', 'tenantid', null, ['tenantid' => $tenant->id]);

        $DB->set_field('cohort', 'component', '', ['id' => $tenant->cohortid]);

        $DB->delete_records('tool_mutenancy_config', ['tenantid' => $tenant->id]);
        $DB->delete_records('tool_mutenancy_manager', ['tenantid' => $tenant->id]);
        $DB->delete_records('tool_mutenancy_tenant', ['id' => $tenant->id]);

        $tenantcontext->delete();

        \tool_mutenancy\event\tenant_deleted::create_from_tenant($tenant, $tenantcontext)->trigger();

        $trans->allow_commit();

        \cache_helper::purge_by_event('tool_mutenancy_invalidatecaches');

        \context_helper::build_all_paths(false);

        \cache_helper::purge_by_event('changesincoursecat');
    }

    /**
     * Fetch tenant record from cache or database.
     *
     * @param int $id
     * @return stdClass|null
     */
    public static function fetch(int $id): ?stdClass {
        global $DB;

        $cache = \cache::make('tool_mutenancy', 'tenant');

        $tenant = $cache->get($id);
        if ($tenant !== false) {
            return (object)$tenant;
        }

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $id]);
        if ($tenant) {
            $cache->set($tenant->id, (array)$tenant);
            $cache->set($tenant->idnumber, (array)$tenant);

            return $tenant;
        }
        return null;
    }

    /**
     * Fetch tenant record from cache or database.
     *
     * @param string $idnumber
     * @return stdClass|null
     */
    public static function fetch_by_idnumber(string $idnumber): ?stdClass {
        global $DB;

        $cache = \cache::make('tool_mutenancy', 'tenant');

        $tenant = $cache->get($idnumber);
        if ($tenant !== false) {
            return (object)$tenant;
        }

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['idnumber' => $idnumber]);
        if ($tenant) {
            $cache->set($tenant->id, (array)$tenant);
            $cache->set($tenant->idnumber, (array)$tenant);

            return $tenant;
        }
        return null;
    }

    /**
     * Construct tenant login URL.
     *
     * @param int $tenantid
     */
    public static function get_login_url(int $tenantid): ?\core\url {
        $tenant = self::fetch($tenantid);
        if (!$tenant || $tenant->archived) {
            return null;
        }
        return new \core\url('/login/', ['tenant' => $tenant->idnumber]);
    }

    /**
     * Cohort deleted observer.
     *
     * @param \core\event\cohort_deleted $event
     * @return void
     */
    public static function cohort_deleted(\core\event\cohort_deleted $event): void {
        global $DB;
        if (!tenancy::is_active()) {
            return;
        }

        $tenants = $DB->get_records('tool_mutenancy_tenant', ['assoccohortid' => $event->objectid]);
        if (!$tenants) {
            return;
        }

        $cache = \cache::make('tool_mutenancy', 'tenant');

        foreach ($tenants as $tenant) {
            $DB->set_field('tool_mutenancy_tenant', 'assoccohortid', null, ['id' => $tenant->id]);
            $cache->delete($tenant->id);
            $cache->delete($tenant->idnumber);
            user::sync($tenant->id, null);
        }
    }
}
