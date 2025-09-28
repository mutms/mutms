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

namespace core\context;

use core\context;
use stdClass;
use coding_exception, moodle_url;

/**
 * Multi-tenancy tenant context level.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant extends context {
    public const LEVEL = 12;

    /**
     * Please use \core\context\tenant::instance($tenantid) if you need the instance of context.
     * Alternatively if you know only the context id use \core\context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != self::LEVEL) {
            throw new coding_exception('Invalid $record->contextlevel in core\context\tenant constructor.');
        }
    }

    /**
     * Returns short context name.
     *
     * @return string
     */
    public static function get_short_name(): string {
        return 'tenant';
    }

    /**
     * Returns human readable context level name.
     *
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('tenant', 'tool_mutenancy');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Category
     * @param boolean $short does not apply to course categories
     * @param boolean $escape Whether the returned name of the context is to be HTML escaped or not.
     * @return string the human-readable context name.
     */
    public function get_context_name($withprefix = true, $short = false, $escape = true) {
        $tenant = \tool_mutenancy\local\tenant::fetch($this->_instanceid);
        if (!$tenant) {
            return '';
        }

        if ($short) {
            $tenantname = $tenant->idnumber;
        } else {
            $tenantname = $tenant->name;
        }
        $tenantname = format_string($tenantname, true, ['context' => $this, 'escape' => $escape]);

        if ($withprefix) {
            $tenantname = get_string('tenant', 'tool_mutenancy') . ': ' . $tenantname;
        }

        return $tenantname;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/admin/tool/mutenancy/tenant.php', ['id' => $this->_instanceid]);
    }

    /**
     * Returns context instance database name.
     *
     * @return string|null table name for all levels except system.
     */
    protected static function get_instance_table(): ?string {
        return 'tool_mutenancy_tenant';
    }

    /**
     * Returns list of columns that can be used from behat
     * to look up context by reference.
     *
     * @return array list of column names from instance table
     */
    protected static function get_behat_reference_columns(): array {
        return ['idnumber'];
    }

    /**
     * Returns list of all role archetypes that are compatible
     * with role assignments in context level.
     *
     * @return int[]
     */
    protected static function get_compatible_role_archetypes(): array {
        return ['manager'];
    }

    /**
     * Returns list of all possible parent context levels.
     *
     * @return int[]
     */
    public static function get_possible_parent_levels(): array {
        return [system::LEVEL];
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @param string $sort
     * @return array
     */
    public function get_capabilities(string $sort = self::DEFAULT_CAPABILITY_SORT) {
        global $DB;

        $levels[] = self::LEVEL;
        $levels[] = user::LEVEL;
        $levels[] = system::LEVEL;

        return $DB->get_records_list('capabilities', 'contextlevel', $levels, $sort);
    }

    /**
     * Returns tenant context instance.
     *
     * @param int $tenantid tenant id
     * @param int $strictness
     * @return tenant|false context instance
     */
    public static function instance($tenantid, $strictness = MUST_EXIST) {
        global $DB;

        if (!mutenancy_is_active()) {
            return false;
        }

        if ($context = context::cache_get(self::LEVEL, $tenantid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', ['contextlevel' => self::LEVEL, 'instanceid' => $tenantid])) {
            // Do not use tenant::fetch() here, we want real DB data, not cached stuff.
            $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], 'id', $strictness);
            if (!$tenant) {
                return false;
            }
            $record = context::insert_context_record(self::LEVEL, $tenant->id, '/' . SYSCONTEXTID);
        }

        if ($record) {
            $context = new tenant($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Create missing context instances at tenant context level
     */
    protected static function create_level_instances() {
        global $DB;

        if (!mutenancy_is_active()) {
            return;
        }

        $sql = "SELECT ".self::LEVEL.", t.id
                  FROM {tool_mutenancy_tenant} t
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE t.id = cx.instanceid AND cx.contextlevel=".self::LEVEL.")";
        $contextdata = $DB->get_recordset_sql($sql);
        foreach ($contextdata as $context) {
            context::insert_context_record(self::LEVEL, $context->id, null);
        }
        $contextdata->close();
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {

        if (!mutenancy_is_active()) {
            return "SELECT c.*
                     FROM {context} c
                    WHERE 1=2";
        }

        $sql = "
                  SELECT c.*
                    FROM {context} c
               LEFT JOIN {tool_mutenancy_tenant} t ON c.instanceid = t.id
                   WHERE t.id IS NULL AND c.contextlevel = ".self::LEVEL."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths.
     *
     * @param bool $force
     */
    protected static function build_paths($force) {
        global $DB;

        if (!mutenancy_is_active()) {
            return;
        }

        $level = self::LEVEL;

        if ($force || $DB->record_exists_select('context', "contextlevel = {$level} AND (depth <> 2 OR path IS NULL)")) {
            $base = '/' . SYSCONTEXTID . '/';
            $path = $DB->sql_concat("'$base'", 'id');

            $sql = "UPDATE {context}
                       SET depth=2, path={$path}
                     WHERE contextlevel = {$level}
                           AND EXISTS (SELECT 'x'
                                         FROM {tool_mutenancy_tenant} t
                                        WHERE t.id = {context}.instanceid)";
            if ($force) {
                $sql .= " AND ({context}.path IS NULL OR {context}.depth <> 2)";
            }
            $DB->execute($sql);
        }

        // Fix tenant members one by one, there should not be many of those.
        $tenantlevel = tenant::LEVEL;
        $userlevel = user::LEVEL;
        $path = $DB->sql_concat('tc.path', "'/'", 'c.id');

        $sql = "SELECT c.*, tc.path AS parentpath, tc.tenantid AS parenttenantid
                  FROM {context} c
                  JOIN {user} u ON u.id = c.instanceid AND c.contextlevel = $userlevel ANd u.tenantid IS NOT NULL
                  JOIN {context} tc ON tc.instanceid = u.tenantid AND tc.contextlevel = $tenantlevel AND tc.path IS NOT NULL AND tc.depth = 2
                 WHERE c.depth <> 3 OR c.tenantid IS NULL OR c.tenantid <> u.tenantid
                       OR c.path IS NULL OR c.path <> $path
              ORDER BY c.id ASC";
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $c) {
            $c->tenantid = $c->parenttenantid;
            $c->path = $c->parentpath . '/' . $c->id;
            $c->depth = 3;
            unset($c->parenttenantid);
            unset($c->parentpath);
            $DB->update_record('context', $c);
        }
        $rs->close();
    }

    /**
     * Fix tenantid in all contexts.
     *
     * @return void
     */
    public static function fix_all_tenantids(): void {
        global $DB;

        $sql = "UPDATE {context}
                   SET tenantid=NULL
                 WHERE tenantid IS NOT NULL
                       AND depth = 1";
        $DB->execute($sql);

        $sql = "UPDATE {context}
                   SET tenantid=NULL
                 WHERE tenantid IS NOT NULL
                       AND depth = 2 AND contextlevel <> :tenantlevel AND contextlevel <> :coursecatlevel";
        $DB->execute($sql, ['tenantlevel' => self::LEVEL, 'coursecatlevel' => coursecat::LEVEL]);

        $sql = "UPDATE {context}
                   SET tenantid=instanceid
                 WHERE contextlevel = :tenantlevel
                       AND (tenantid IS NULL OR tenantid <> instanceid)";
        $DB->execute($sql, ['tenantlevel' => self::LEVEL]);

        $sql = "UPDATE {context}
                   SET tenantid=(
                           SELECT t.id
                             FROM {tool_mutenancy_tenant} t
                            WHERE t.categoryid = {context}.instanceid)
                 WHERE contextlevel = :coursecatlevel AND depth = 2";
        $DB->execute($sql, ['coursecatlevel' => coursecat::LEVEL]);

        $path = $DB->sql_concat('tc.path', "'/%'");
        $sql = "UPDATE {context}
                   SET tenantid=NULL
                 WHERE depth > 2 AND tenantid IS NOT NULL
                       AND NOT EXISTS (
                           SELECT 'x'
                             FROM (SELECT *
                                     FROM {context}
                                    WHERE depth = 2 AND tenantid IS NOT NULL) tc
                            WHERE tc.tenantid = {context}.tenantid
                                  AND {context}.path LIKE $path)";
        $DB->execute($sql);

        $sql = "UPDATE {context}
                   SET tenantid=(
                           SELECT tc.tenantid
                             FROM (SELECT *
                                     FROM {context}
                                    WHERE depth = 2 AND tenantid IS NOT NULL) tc
                            WHERE {context}.path LIKE $path)
                 WHERE depth > 2 AND tenantid IS NULL
                       AND EXISTS (
                           SELECT 'x'
                             FROM (SELECT *
                                     FROM {context}
                                    WHERE depth = 2 AND tenantid IS NOT NULL) tc
                            WHERE {context}.path LIKE $path)";
        $DB->execute($sql);
    }

    /**
     * Calculate tenantid using existing context data,
     * hopefully it is accurate and nobody messed with tenant categories.
     *
     * @param int $contextlevel
     * @param int $instanceid
     * @param string $path
     * @return int|null
     */
    public static function guess_tenantid(int $contextlevel, int $instanceid, string $path): ?int {
        global $DB;
        if ($contextlevel === system::LEVEL) {
            return null;
        }
        if ($contextlevel === self::LEVEL) {
            return $instanceid;
        }
        if (!$path) {
            debugging('missing context path, cannot find tenantid', DEBUG_DEVELOPER);
            return null;
        }

        $parts = explode('/', $path);

        if (count($parts) === 3) {
            if ($contextlevel == coursecat::LEVEL) {
                $tenant = $DB->get_record('tool_mutenancy_tenant', ['categoryid' => $instanceid]);
                if ($tenant) {
                    return $tenant->id;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        if (isset($parts[2])) {
            $tcontext = context::instance_by_id($parts[2], IGNORE_MISSING);
            if ($tcontext) {
                return $tcontext->tenantid;
            } else {
                return null;
            }
        }

        return null;
    }
}
