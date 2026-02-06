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

namespace tool_muhome\local;

use stdClass;
use core\exception\invalid_parameter_exception;
use tool_mulib\local\sql;
use core\url;

/**
 * Custom home pages helper.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page {
    /** @var int default for first page */
    public const INITIAL_PRIORITY = 1000;

    /** @var string page type used in page blocks */
    public const PAGE_TYPE = 'tool-muhome-index';

    /** @var int draft pages are not visible yet */
    public const STATUS_DRAFT = 0;
    /** @var int active pages have standard visibility */
    public const STATUS_ACTIVE = 1;
    /** @var int archived pages should be hidden in most places, they are not expected to be used in the future */
    public const STATUS_ARCHIVED = 2;

    /**
     * Returns defaults for new page.
     *
     * @param int|null $contextid
     * @return stdClass
     */
    public static function get_defaults(?int $contextid): stdClass {
        global $DB;

        $page = new stdClass();
        if (isset($contextid)) {
            $page->contextid = (string)$contextid;
        }
        $page->status = (string)self::STATUS_DRAFT;
        $page->guestvisible = '0';
        $page->uservisible = '1';
        $page->priority = (int)$DB->get_field('tool_muhome_page', 'MIN(priority)', []);
        if (!$page->priority) {
            $page->priority = (string)self::INITIAL_PRIORITY;
        } else {
            $page->priority = (string)($page->priority - 10);
        }

        return $page;
    }

    /**
     * Create new page.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $record = new stdClass();

        if (empty($data->contextid)) {
            throw new invalid_parameter_exception('page contextid is required');
        }
        $context = \context::instance_by_id($data->contextid);
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            throw new invalid_parameter_exception('System or category context expected');
        }
        $record->contextid = $context->id;

        if (trim($data->name ?? '') === '') {
            throw new invalid_parameter_exception('page name is required');
        }
        $record->name = $data->name;

        if (trim($data->title ?? '') === '') {
            $record->title = null;
        } else {
            $record->title = $data->title;
        }

        $record->priority = (int)($data->priority ?? 0);

        $record->status = (int)($data->status ?? self::STATUS_DRAFT);
        $options = self::get_statuses_menu();
        if (!isset($options[$record->status])) {
            throw new invalid_parameter_exception('invalid page status');
        }

        $record->hiddenbefore = $data->hiddenbefore ?? 0;
        if (!$record->hiddenbefore) {
            $record->hiddenbefore = null;
        }

        $record->hiddenafter = $data->hiddenafter ?? 0;
        if (!$record->hiddenafter) {
            $record->hiddenafter = null;
        }

        $record->guestvisible = (int)(bool)($data->guestvisible ?? 0);
        $record->uservisible = (int)(bool)($data->uservisible ?? 0);
        $record->hiddenfromtenants = (int)(bool)($data->hiddenfromtenants ?? 0);

        $record->timecreated = time();
        $record->timemodified = $record->timecreated;

        $trans = $DB->start_delegated_transaction();

        $record->id = $DB->insert_record('tool_muhome_page', $record);

        if (!$record->uservisible && !empty($data->cohortvisible)) {
            foreach ($data->cohortvisible as $cohortid) {
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                $DB->insert_record('tool_muhome_page_cohortvisible', ['pageid' => $record->id, 'cohortid' => $cohort->id]);
            }
        }

        $trans->allow_commit();
        self::fix_muhome_active();

        \cache_helper::purge_by_event('tool_muhome_invalidatecaches');

        return $DB->get_record('tool_muhome_page', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Update existing page.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $data = (object)(array)$data;
        $record = new stdClass();
        $record->id = $data->id;

        $oldpage = $DB->get_record('tool_muhome_page', ['id' => $record->id], '*', MUST_EXIST);

        if (property_exists($data, 'contextid')) {
            if ($data->contextid != $oldpage->contextid) {
                throw new \core\exception\coding_exception('page::update() cannot change contextid, use page::move() instead');
            }
        }

        if (property_exists($data, 'name')) {
            if (trim($data->name) === '') {
                throw new invalid_parameter_exception('page name is required');
            }
            $record->name = $data->name;
        }

        if (property_exists($data, 'title')) {
            if (trim($data->title ?? '') === '') {
                $record->title = null;
            } else {
                $record->title = $data->title;
            }
        }

        if (property_exists($data, 'priority')) {
            $record->priority = (int)$data->priority;
        }

        if (property_exists($data, 'status')) {
            $options = self::get_statuses_menu();
            if (!isset($options[$data->status])) {
                throw new invalid_parameter_exception('invalid page status');
            }
            $record->status = $data->status;
        }

        if (property_exists($data, 'hiddenbefore')) {
            $record->hiddenbefore = $data->hiddenbefore ?? 0;
            if (!$record->hiddenbefore) {
                $record->hiddenbefore = null;
            }
        }

        if (property_exists($data, 'hiddenafter')) {
            $record->hiddenafter = $data->hiddenafter ?? 0;
            if (!$record->hiddenafter) {
                $record->hiddenafter = null;
            }
        }

        if (property_exists($data, 'guestvisible')) {
            $record->guestvisible = (int)(bool)($data->guestvisible ?? 0);
        }
        if (property_exists($data, 'uservisible')) {
            $record->uservisible = (int)(bool)($data->uservisible ?? 0);
        }
        if (property_exists($data, 'hiddenfromtenants')) {
            $record->hiddenfromtenants = (int)(bool)($data->hiddenfromtenants ?? 0);
        }

        $record->timemodified = time();

        $trans = $DB->start_delegated_transaction();

        if (property_exists($data, 'contextid')) {
            $context = \context::instance_by_id($data->contextid);
            if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
                throw new invalid_parameter_exception('System or category context expected');
            }
            if ($oldpage->contextid != $context->id) {
                $oldcontext = \context::instance_by_id($oldpage->contextid, IGNORE_MISSING);
                // Move all page blocks to new context.
                $bis = $DB->get_records('block_instances', ['pagetypepattern' => self::PAGE_TYPE, 'subpagepattern' => $oldpage->id]);
                foreach ($bis as $bi) {
                    $blockcontext = \context_block::instance($bi->id);
                    $DB->set_field('block_instances', 'parentcontextid', $context->id, ['id' => $bi->id]);
                    $blockcontext->update_moved($context);
                }
                $record->contextid = $context->id;
            }
        }

        $DB->update_record('tool_muhome_page', $record);
        $record = $DB->get_record('tool_muhome_page', ['id' => $record->id], '*', MUST_EXIST);

        if ($record->uservisible) {
            $DB->delete_records('tool_muhome_page_cohortvisible', ['pageid' => $record->id]);
        } else if (property_exists($data, 'cohortvisible')) {
            $currentcohorts = self::get_cohortvisible_menu($oldpage->id);
            foreach ($data->cohortvisible as $cohortid) {
                if (isset($currentcohorts[$cohortid])) {
                    unset($currentcohorts[$cohortid]);
                    continue;
                }
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);
                $DB->insert_record('tool_muhome_page_cohortvisible', ['pageid' => $record->id, 'cohortid' => $cohort->id]);
            }
            foreach ($currentcohorts as $cohortid => $unused) {
                $DB->delete_records('tool_muhome_page_cohortvisible', ['pageid' => $record->id, 'cohortid' => $cohortid]);
            }
        }

        $trans->allow_commit();
        self::fix_muhome_active();

        \cache_helper::purge_by_event('tool_muhome_invalidatecaches');

        return $record;
    }

    /**
     * Move existing page to a different context.
     *
     * @param int $id section id
     * @param int $contextid new context id
     * @return stdClass page record
     */
    public static function move(int $id, int $contextid): stdClass {
        global $DB;

        $page = $DB->get_record('tool_muhome_page', ['id' => $id], '*', MUST_EXIST);

        $context = \context::instance_by_id($contextid);
        if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
            throw new invalid_parameter_exception('System or category context expected');
        }

        if ($page->contextid == $context->id) {
            return $page;
        }

        $trans = $DB->start_delegated_transaction();

        // Move all page blocks to new context.
        $bis = $DB->get_records('block_instances', ['pagetypepattern' => self::PAGE_TYPE, 'subpagepattern' => $page->id]);
        foreach ($bis as $bi) {
            $blockcontext = \context_block::instance($bi->id);
            $DB->set_field('block_instances', 'parentcontextid', $context->id, ['id' => $bi->id]);
            $blockcontext->update_moved($context);
        }

        $record = (object)[
            'id' => $page->id,
            'contextid' => $context->id,
            'timemodified' => time(),
        ];

        $DB->update_record('tool_muhome_page', $record);

        $trans->allow_commit();
        \cache_helper::purge_by_event('tool_muhome_invalidatecaches');

        $record = $DB->get_record('tool_muhome_page', ['id' => $record->id], '*', MUST_EXIST);
        return $record;
    }

    /**
     * Delete page.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/blocklib.php');

        $page = $DB->get_record('tool_muhome_page', ['id' => $id]);
        if (!$page) {
            return;
        }
        $context = \context::instance_by_id($page->contextid, IGNORE_MISSING);

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('tool_muhome_page_cohortvisible', ['pageid' => $page->id]);

        if ($context) {
            // Delete all blocks.
            $bis = $DB->get_records('block_instances', ['pagetypepattern' => self::PAGE_TYPE, 'subpagepattern' => $page->id]);
            foreach ($bis as $bi) {
                blocks_delete_instance($bi);
            }
            // Delete all fils.
            get_file_storage()->delete_area_files(
                $page->contextid,
                'tool_muhome',
                'content',
                $page->id
            );
        }

        $DB->delete_records('tool_muhome_page', ['id' => $page->id]);

        $trans->allow_commit();
        self::fix_muhome_active();

        \cache_helper::purge_by_event('tool_muhome_invalidatecaches');
    }

    /**
     * Cache existence of programs.
     */
    public static function fix_muhome_active(): void {
        global $DB;

        $active = (int)$DB->record_exists('tool_muhome_page', ['status' => self::STATUS_ACTIVE]);
        set_config('active', $active, 'tool_muhome');
    }

    /**
     * Returns menu of page statuses.
     *
     * @return array
     */
    public static function get_statuses_menu(): array {
        return [
            self::STATUS_DRAFT => get_string('page_status_draft', 'tool_muhome'),
            self::STATUS_ACTIVE => get_string('page_status_active', 'tool_muhome'),
            self::STATUS_ARCHIVED => get_string('page_status_archived', 'tool_muhome'),
        ];
    }

    /**
     * Fetches cohorts page is visible to.
     *
     * @param int $pageid
     * @return array non-formated menu of visible cohorts
     */
    public static function get_cohortvisible_menu(int $pageid): array {
        global $DB;

        $sql = new sql(
            "SELECT c.id, c.name
               FROM {cohort} c
               JOIN {tool_muhome_page_cohortvisible} vc ON c.id = vc.cohortid
              WHERE vc.pageid = ?
           ORDER BY c.name ASC, c.id ASC",
            [$pageid]
        );

        return $DB->get_records_sql_menu($sql->sql, $sql->params);
    }

    /**
     * Returns URL for given page.
     *
     * @param int|null $pageid
     * @return url
     */
    public static function get_url(?int $pageid): url {
        if ($pageid) {
            return new url('/admin/tool/muhome/', ['pageid' => $pageid]);
        } else {
            return new url('/admin/tool/muhome/');
        }
    }

    /**
     * Returns visible pages for current user.
     *
     * @param bool $usecache
     * @return array
     */
    public static function get_my_pages(bool $usecache = true): array {
        global $DB, $USER;

        $cache = \cache::make('tool_muhome', 'mypages');
        $cachekey = (int)$USER->id;
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $cachekey .= '_' . (int)\tool_mutenancy\local\tenancy::get_current_tenantid();
        }

        if ($usecache) {
            $pages = $cache->get($cachekey);
            if (is_array($pages)) {
                return $pages;
            }
        }

        $now = time();

        $sql = new sql(
            "SELECT p.id, p.name
               FROM {tool_muhome_page} p
               JOIN {context} ctx ON ctx.id = p.contextid
              WHERE p.status = :active
                    AND (p.hiddenbefore IS NULL OR p.hiddenbefore <= $now)
                    AND (p.hiddenafter IS NULL OR p.hiddenafter > $now)
                    /* userwhere */ /* tenantwhere */
           ORDER BY p.priority DESC, p.id ASC",
            ['active' => self::STATUS_ACTIVE]
        );

        if (!isloggedin() || isguestuser()) {
            $sql = $sql->replace_comment(
                'userwhere',
                "AND p.guestvisible = 1"
            );
        } else {
            $sql = $sql->replace_comment(
                'userwhere',
                "AND (p.uservisible = 1 OR EXISTS(SELECT 'x'
                                                     FROM {tool_muhome_page_cohortvisible} cv
                                                     JOIN {cohort_members} cm ON cm.cohortid = cv.cohortid AND cm.userid = :userid
                                                   WHERE cv.pageid = p.id))",
                ['userid' => $USER->id]
            );
        }

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $sql = $sql->replace_comment(
                    'tenantwhere',
                    "AND p.hiddenfromtenants = 0 AND (ctx.tenantid IS NULL OR ctx.tenantid = :tenantid)",
                    ['tenantid' => $tenantid]
                );
            } else {
                // Pages defined inside tenant categories are visible only when switched to the tenant.
                $sql = $sql->replace_comment(
                    'tenantwhere',
                    "AND ctx.tenantid IS NULL"
                );
            }
        }

        $pages = $DB->get_records_sql_menu($sql->sql, $sql->params);
        $cache->set($cachekey, $pages);

        return $pages;
    }

    /**
     * When deleting course category delete all pages attached to that category.
     *
     * @param int $categoryid
     * @return void
     */
    public static function pre_course_category_delete(int $categoryid): void {
        global $DB;

        $catcontext = \context_coursecat::instance($categoryid);
        $pages = $DB->get_records('tool_muhome_page', ['contextid' => $catcontext->id]);
        foreach ($pages as $page) {
            self::delete($page->id);
        }
    }

    /**
     * Archive tenant pages right before tenant deletion.
     *
     * @param int $tenantid
     * @return void
     */
    public static function archive_tenant_pages(int $tenantid): void {
        global $DB;

        $sql = new sql(
            "SELECT p.id
               FROM {tool_muhome_page} p
               JOIN {context} ctx ON ctx.id = p.contextid
              WHERE ctx.tenantid = :tenantid AND p.status <> :archived",
            ['tenantid' => $tenantid, 'archived' => self::STATUS_ARCHIVED]
        );
        $pageids = $DB->get_fieldset_sql($sql->sql, $sql->params);

        foreach ($pageids as $pageid) {
            $data = (object)[
                'id' => $pageid,
                'status' => self::STATUS_ARCHIVED,
            ];
            self::update($data);
        }
    }
}
