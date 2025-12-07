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
// phpcs:disable moodle.Commenting.ValidTags.Invalid
// phpcs:disable moodle.Commenting.InlineComment.DocBlock

namespace tool_mulib\local;

use context, context_system, context_tenant, context_user, context_coursecat, context_course;
use core\exception\coding_exception;

/**
 * Helper class for maintaining contents of {tool_mulib_context_parent} and {tool_mulib_context_map}
 * database tables.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class context_map_builder {
    /** @var int max number of subcontexts to be updated directly when moving categories */
    public const MAX_CATEGORY_UPDATE_COUNT = 100;

    /**
     * Build context relations map.
     */
    public static function build(): void {
        global $DB;

        // NOTE: code is kept in smaller public methods to simplify testing.

        self::parent_purge_deleted();
        if (!mulib::is_mutenancy_active()) {
            self::parent_user_fix();
        } else {
            self::parent_tenant_fix();
            self::parent_tenant_user_fix();
        }
        self::parent_category_fix();
        self::parent_course_fix();

        self::map_purge_deleted();
        self::map_distance_0();
        self::map_distance_1();
        // Start with at least 4 levels in case the context depths and paths were not updated correctly.
        $max1 = (int)$DB->get_field('context', 'MAX(depth)', []);
        $max2 = (int)$DB->get_field('tool_mulib_context_map', 'MAX(distance)', []);
        $maxn = max(4, $max1, $max2);
        for ($n = 2; $n <= $maxn; $n++) {
            self::map_distance_n($n);
        }
        self::map_purge_above_system();
    }

    /**
     * Update database table statistics.
     */
    public static function analyze(): void {
        global $DB;
        $dbfamily = $DB->get_dbfamily();
        if ($dbfamily === 'postgres') {
            $DB->execute("ANALYZE {tool_mulib_context_parent}, {tool_mulib_context_map}, {role_assignments}, {role_capabilities}");
        } else if ($dbfamily === 'mysql') {
            $DB->execute("ANALYZE TABLE {tool_mulib_context_parent}, {tool_mulib_context_map}, {role_assignments}, {role_capabilities}");
        }
    }

    /**
     * Remove references to deleted contexts.
     */
    public static function parent_purge_deleted(): void {
        global $DB;
        $syscontextid = context_system::instance()->id;

        // Remove all references to deleted contexts.
        $sql = "DELETE
                  FROM {tool_mulib_context_parent}
                 WHERE contextid = $syscontextid OR NOT EXISTS (

                         SELECT 'x'
                           FROM {context} ctx
                          WHERE ctx.id = {tool_mulib_context_parent}.contextid

                 )";
        $DB->execute($sql);
    }

    /**
     * Fix user context parents.
     */
    public static function parent_user_fix(): void {
        global $DB;

        $syscontextid = context_system::instance()->id;
        $userlevel = context_user::LEVEL;

        // Remove parents for deleted users.
        $sql = "SELECT p.contextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$userlevel}
                  JOIN {user} u ON u.id = ctx.instanceid
                 WHERE u.deleted = 1";
        $deleteids = $DB->get_fieldset_sql($sql);
        if ($deleteids) {
            // This should not happen often, so only the select needs to be fast.
            foreach (array_chunk($deleteids, 100) as $cids) {
                $cids = implode(',', $cids);
                $sql = "DELETE
                          FROM {tool_mulib_context_parent}
                         WHERE contextid in ({$cids})";
                $DB->execute($sql);
            }
        }

        // Fix parents.
        $sql = "SELECT p.contextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$userlevel}
                 WHERE p.parentcontextid <> {$syscontextid}";
        $fixids = $DB->get_fieldset_sql($sql);
        if ($fixids) {
            foreach (array_chunk($fixids, 100) as $cids) {
                // This should not happen often, so only the select needs to be fast.
                $cids = implode(',', $cids);
                $sql = "UPDATE {tool_mulib_context_parent}
                           SET parentcontextid = {$syscontextid}
                         WHERE contextid in ({$cids})";
                $DB->execute($sql);
            }
        }

        // Add missing parent entries.
        $sql = "INSERT INTO {tool_mulib_context_parent} (contextid, parentcontextid)

                SELECT ctx.id, {$syscontextid}
                  FROM {context} ctx
                  JOIN {user} u ON u.id = ctx.instanceid AND ctx.contextlevel = {$userlevel} AND u.deleted = 0
             LEFT JOIN {tool_mulib_context_parent} p ON p.contextid = ctx.id
                 WHERE p.contextid IS NULL";
        $DB->execute($sql);
    }

    /**
     * Fix tenant context parents.
     */
    public static function parent_tenant_fix(): void {
        global $DB;

        $syscontextid = context_system::instance()->id;
        $tenantlevel = context_tenant::LEVEL;

        $sql = "SELECT p.contextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$tenantlevel}
                 WHERE p.parentcontextid <> {$syscontextid}";
        $fixids = $DB->get_fieldset_sql($sql);
        if ($fixids) {
            foreach (array_chunk($fixids, 100) as $cids) {
                $cids = implode(',', $cids);
                $sql = "UPDATE {tool_mulib_context_parent}
                           SET parentcontextid = {$syscontextid}
                         WHERE contextid IN ({$cids})";
                $DB->execute($sql);
            }
        }

        $sql = "INSERT INTO {tool_mulib_context_parent} (contextid, parentcontextid)

                SELECT ctx.id, {$syscontextid}
                  FROM {context} ctx
                  JOIN {tool_mutenancy_tenant} t ON t.id = ctx.instanceid AND ctx.contextlevel = {$tenantlevel}
             LEFT JOIN {tool_mulib_context_parent} p ON p.contextid = ctx.id
                 WHERE p.contextid IS NULL";
        $DB->execute($sql);
    }

    /**
     * Fix user context parents with multi-tenancy on.
     */
    public static function parent_tenant_user_fix(): void {
        global $DB;

        $syscontextid = context_system::instance()->id;
        $userlevel = context_user::LEVEL;
        $tenantlevel = context_tenant::LEVEL;

        // Remove parents for deleted users.
        $sql = "SELECT p.contextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$userlevel}
                  JOIN {user} u ON u.id = ctx.instanceid
                 WHERE u.deleted = 1";
        $deleteids = $DB->get_fieldset_sql($sql);
        if ($deleteids) {
            foreach (array_chunk($deleteids, 100) as $cids) {
                $cids = implode(',', $cids);
                $sql = "DELETE
                          FROM {tool_mulib_context_parent}
                         WHERE contextid in ({$cids})";
                $DB->execute($sql);
            }
        }

        // Fix parents of global users.
        $sql = "SELECT p.contextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$userlevel}
                  JOIN {user} u ON u.id = ctx.instanceid AND u.tenantid IS NULL
                 WHERE p.parentcontextid <> {$syscontextid}";
        $fixids = $DB->get_fieldset_sql($sql);
        if ($fixids) {
            // This should not happen, so only the select needs to be fast.
            foreach (array_chunk($fixids, 100) as $cids) {
                $cids = implode(',', $cids);
                $sql = "UPDATE {tool_mulib_context_parent}
                           SET parentcontextid = {$syscontextid}
                         WHERE contextid IN ({$cids})";
                $DB->execute($sql);
            }
        }

        // Fix parents of tenant members.
        $sql = "SELECT p.contextid, pctx.id AS parentcontextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$userlevel}
                  JOIN {user} u ON u.id = ctx.instanceid
                  JOIN {tool_mutenancy_tenant} t ON t.id = u.tenantid
                  JOIN {context} pctx ON pctx.instanceid = t.id AND pctx.contextlevel = {$tenantlevel}
                 WHERE p.parentcontextid <> pctx.id";
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $p) {
            // This should not happen, so only the select needs to be fast.
            $DB->set_field('tool_mulib_context_parent', 'parentcontextid', $p->parentcontextid, ['contextid' => $p->contextid]);
        }
        $rs->close();

        $sql = "INSERT INTO {tool_mulib_context_parent} (contextid, parentcontextid)

                SELECT ctx.id, COALESCE(pctx.id, {$syscontextid})
                  FROM {context} ctx
                  JOIN {user} u ON u.id = ctx.instanceid AND ctx.contextlevel = {$userlevel} AND u.deleted = 0
             LEFT JOIN {tool_mutenancy_tenant} t ON t.id = u.tenantid
             LEFT JOIN {context} pctx ON pctx.instanceid = t.id AND pctx.contextlevel = {$tenantlevel}
             LEFT JOIN {tool_mulib_context_parent} p ON p.contextid = ctx.id
                 WHERE p.contextid IS NULL";
        $DB->execute($sql);
    }

    /**
     * Fix category parents.
     */
    public static function parent_category_fix(): void {
        global $DB;

        $syscontextid = context_system::instance()->id;
        $categorylevel = context_coursecat::LEVEL;

        $sql = "SELECT p.contextid, COALESCE(pctx.id, {$syscontextid}) AS parentcontextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$categorylevel}
                  JOIN {course_categories} cc ON cc.id = ctx.instanceid
             LEFT JOIN {course_categories} ccp ON ccp.id = cc.parent
             LEFT JOIN {context} pctx ON pctx.instanceid = ccp.id AND pctx.contextlevel = {$categorylevel}
                 WHERE p.parentcontextid <> COALESCE(pctx.id, {$syscontextid})";
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $p) {
            // This should not happen often, so only the select needs to be fast.
            $DB->set_field('tool_mulib_context_parent', 'parentcontextid', $p->parentcontextid, ['contextid' => $p->contextid]);
        }
        $rs->close();

        $sql = "INSERT INTO {tool_mulib_context_parent} (contextid, parentcontextid)

                SELECT ctx.id, COALESCE(pctx.id, {$syscontextid})
                  FROM {context} ctx
                  JOIN {course_categories} cc ON cc.id = ctx.instanceid AND ctx.contextlevel = {$categorylevel}
             LEFT JOIN {course_categories} ccp ON ccp.id = cc.parent
             LEFT JOIN {context} pctx ON pctx.instanceid = ccp.id AND pctx.contextlevel = {$categorylevel}
             LEFT JOIN {tool_mulib_context_parent} p ON p.contextid = ctx.id
                 WHERE p.contextid IS NULL";
        $DB->execute($sql);
    }

    /**
     * Fix course context parents.
     */
    public static function parent_course_fix(): void {
        global $DB;

        $syscontextid = context_system::instance()->id;
        $categorylevel = context_coursecat::LEVEL;
        $courselevel = context_course::LEVEL;

        $sql = "SELECT p.contextid, COALESCE(pctx.id, {$syscontextid}) AS parentcontextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {context} ctx ON ctx.id = p.contextid AND ctx.contextlevel = {$courselevel}
                  JOIN {course} cs ON cs.id = ctx.instanceid
             LEFT JOIN {course_categories} ccp ON ccp.id = cs.category
             LEFT JOIN {context} pctx ON pctx.instanceid = ccp.id AND pctx.contextlevel = {$categorylevel}
                 WHERE p.parentcontextid <> COALESCE(pctx.id, {$syscontextid})";
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $p) {
            // This should not happen often, so only the select needs to be fast.
            $DB->set_field('tool_mulib_context_parent', 'parentcontextid', $p->parentcontextid, ['contextid' => $p->contextid]);
        }
        $rs->close();

        $sql = "INSERT INTO {tool_mulib_context_parent} (contextid, parentcontextid)

                SELECT ctx.id, COALESCE(pctx.id, {$syscontextid})
                  FROM {context} ctx
                  JOIN {course} cs ON cs.id = ctx.instanceid AND ctx.contextlevel = {$courselevel}
             LEFT JOIN {course_categories} ccp ON ccp.id = cs.category
             LEFT JOIN {context} pctx ON pctx.instanceid = ccp.id AND pctx.contextlevel = {$categorylevel}
             LEFT JOIN {tool_mulib_context_parent} p ON p.contextid = ctx.id
                 WHERE p.contextid IS NULL";
        $DB->execute($sql);
    }

    /**
     * Remove all entries not present in context parents cache.
     */
    public static function map_purge_deleted(): void {
        global $DB;

        $syscontextid = context_system::instance()->id;

        $sql = "SELECT DISTINCT map.contextid
                  FROM {tool_mulib_context_map} map
             LEFT JOIN {tool_mulib_context_parent} p ON p.contextid = map.contextid
                 WHERE map.contextid <> $syscontextid AND p.contextid IS NULL";
        $deleteids = $DB->get_fieldset_sql($sql);
        foreach (array_chunk($deleteids, 100) as $cids) {
            // This should not happen often.
            $cids = implode(',', $cids);
            $sql = "DELETE
                      FROM {tool_mulib_context_map}
                     WHERE contextid in ({$cids})";
            $DB->execute($sql);
        }
    }

    /**
     * Add missing references to self.
     */
    public static function map_distance_0(): void {
        global $DB;

        $sql = "UPDATE {tool_mulib_context_map}
                   SET relatedcontextid = contextid
                 WHERE distance = 0 AND relatedcontextid <> contextid";
        $DB->execute($sql);

        $sql = "INSERT INTO {tool_mulib_context_map} (contextid, distance, relatedcontextid)

                SELECT p.contextid, 0, p.contextid
                  FROM {tool_mulib_context_parent} p
             LEFT JOIN {tool_mulib_context_map} map ON map.contextid = p.contextid AND map.distance = 0
                 WHERE map.contextid IS NULL";
        $DB->execute($sql);

        $syscontext = context_system::instance();
        if (!$DB->record_exists('tool_mulib_context_map', ['contextid' => $syscontext->id, 'distance' => 0])) {
            self::upsert_context_map($syscontext->id, [$syscontext->id]);
        }
    }

    /**
     * Fix existing and add missing references to direct parents.
     */
    public static function map_distance_1(): void {
        global $DB;

        if ($DB->get_dbfamily() === 'mysql') {
            $sql = /** @lang=MySQL */
                "UPDATE {tool_mulib_context_map} map, {tool_mulib_context_parent} p
                       SET map.relatedcontextid = p.parentcontextid
                     WHERE map.contextid = p.contextid AND map.distance = 1
                           AND map.relatedcontextid <> p.parentcontextid";
        } else {
            $sql =
                "UPDATE {tool_mulib_context_map}
                    SET relatedcontextid = p.parentcontextid
                   FROM {tool_mulib_context_parent} p
                  WHERE {tool_mulib_context_map}.contextid = p.contextid AND {tool_mulib_context_map}.distance = 1
                        AND {tool_mulib_context_map}.relatedcontextid <> p.parentcontextid";
        }
        $DB->execute($sql);

        $sql = "INSERT INTO {tool_mulib_context_map} (contextid, distance, relatedcontextid)

                SELECT p.contextid, 1, p.parentcontextid
                  FROM {tool_mulib_context_parent} p
             LEFT JOIN {tool_mulib_context_map} map ON map.contextid = p.contextid AND map.distance = 1
                 WHERE map.contextid IS NULL";
        $DB->execute($sql);
    }

    /**
     * Fix parent of a parents.
     * @param int $n
     */
    public static function map_distance_n(int $n): void {
        global $DB;
        if ($n < 2) {
            throw new coding_exception('Distance of at least 2 is expected');
        }
        $prev = $n - 1;

        if ($DB->get_dbfamily() === 'mysql') {
            $sql = /** @lang=MySQL */
                "UPDATE {tool_mulib_context_map} map, (SELECT map.contextid, p.parentcontextid
                                                         FROM {tool_mulib_context_map} map
                                                         JOIN {tool_mulib_context_parent} p ON p.contextid = map.relatedcontextid
                                                              AND map.distance = {$prev}) AS prevmap
                   SET map.relatedcontextid = prevmap.parentcontextid
                 WHERE map.contextid = prevmap.contextid AND map.distance = {$n}
                       AND map.relatedcontextid <> prevmap.parentcontextid";
        } else {
            $sql =
                "WITH prevmap AS (
                        SELECT map.contextid, p.parentcontextid
                          FROM {tool_mulib_context_map} map
                          JOIN {tool_mulib_context_parent} p ON p.contextid = map.relatedcontextid
                               AND map.distance = {$prev}
                     )
                UPDATE {tool_mulib_context_map}
                   SET relatedcontextid = prevmap.parentcontextid
                  FROM prevmap
                 WHERE {tool_mulib_context_map}.contextid = prevmap.contextid AND {tool_mulib_context_map}.distance = {$n}
                       AND {tool_mulib_context_map}.relatedcontextid <> prevmap.parentcontextid";
        }
        $DB->execute($sql);

        $sql = "INSERT INTO {tool_mulib_context_map} (contextid, distance, relatedcontextid)

                SELECT map.contextid, {$n}, p.parentcontextid
                  FROM {tool_mulib_context_parent} p
                  JOIN {tool_mulib_context_map} map ON map.relatedcontextid = p.contextid AND map.distance = {$prev}
             LEFT JOIN {tool_mulib_context_map} nextmap ON nextmap.contextid = map.contextid AND nextmap.distance = {$n}
                 WHERE nextmap.contextid IS NULL";
        $DB->execute($sql);
    }

    /**
     * Delete unsued distances higher than distance to the top.
     */
    public static function map_purge_above_system(): void {
        global $DB;
        $syscontextid = context_system::instance()->id;
        $sql = "SELECT om.contextid, om.distance
                  FROM {tool_mulib_context_map} om
                  JOIN {tool_mulib_context_map} map ON map.contextid = om.contextid AND map.relatedcontextid = $syscontextid
                 WHERE (om.distance > map.distance OR om.distance < 0)";
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $map) {
            // This should not happen, so only the select needs to be fast.
            $DB->delete_records('tool_mulib_context_map', ['contextid' => $map->contextid, 'distance' => $map->distance]);
        }
        $rs->close();
    }

    /**
     * Check if all map entries look ok.
     * @return null|string null is ok, string is error
     */
    public static function map_check(): ?string {
        global $DB;

        $syscontext = context_system::instance();
        $sql = "SELECT map.contextid
                  FROM {tool_mulib_context_map} map
                  JOIN (SELECT contextid, MAX(distance) AS maxdistance
                          FROM {tool_mulib_context_map}
                      GROUP BY contextid
                        ) sysrel ON sysrel.contextid = map.contextid AND sysrel.maxdistance = map.distance
                 WHERE map.relatedcontextid <> :syscontextid
              ORDER BY map.contextid ASC";
        $cids = $DB->get_fieldset_sql($sql, ['syscontextid' => $syscontext->id]);
        if ($cids) {
            return 'Context map error - following contexts do not have system as top parent: ' . implode(', ', $cids);
        }

        return null;
    }

    /**
     * Insert or update entries in context map table for one context.
     *
     * @param int $contextid
     * @param int $parentcontextid
     * @return void does nothing if context parameters are invalid
     */
    public static function upsert_context_parent(int $contextid, int $parentcontextid): void {
        global $DB;

        if ($contextid < 1 || $parentcontextid < 1 || $contextid == $parentcontextid) {
            debugging('invalid context parameters', DEBUG_DEVELOPER);
            return;
        }

        $dbfamily = $DB->get_dbfamily();
        if ($dbfamily === 'postgres') {
            $sql = "INSERT INTO {tool_mulib_context_parent} (contextid, parentcontextid) VALUES ($contextid,$parentcontextid)
                    ON CONFLICT (contextid) DO UPDATE SET parentcontextid = excluded.parentcontextid";
            $DB->execute($sql);
        } else if ($dbfamily === 'mysql') {
            $sql = "INSERT INTO {tool_mulib_context_parent} (contextid, parentcontextid) VALUES ($contextid,$parentcontextid)
                    ON DUPLICATE KEY UPDATE parentcontextid = VALUES(parentcontextid)";
            $DB->execute($sql);
        } else {
            $data = ['contextid' => $contextid, 'parentcontextid' => $parentcontextid];
            if ($DB->record_exists('tool_mulib_context_parent', $data)) {
                $DB->set_field('tool_mulib_context_parent', 'parentcontextid', $parentcontextid, ['contextid' => $contextid]);
            } else {
                try {
                    $DB->insert_record('tool_mulib_context_parent', $data, false);
                } catch (\Exception $ex) {
                    // Could be a concurrent insert.
                    $DB->set_field('tool_mulib_context_parent', 'parentcontextid', $parentcontextid, ['contextid' => $contextid]);
                }
            }
        }
    }

    /**
     * Delete relevant record from context parent table.
     * @param int $contextid
     * @return void
     */
    public static function delete_context_parent(int $contextid): void {
        global $DB;
        $DB->delete_records('tool_mulib_context_parent', ['contextid' => $contextid]);
    }

    /**
     * Insert or update entries in context map table for one context.
     *
     * @param int $contextid
     * @param array $relations array of all distance=>relatedcontextid context relations, 0 distance is added automatically
     * @return void does nothing if $relations parameter is invalid
     */
    public static function upsert_context_map(int $contextid, array $relations): void {
        global $DB;

        if (!isset($relations[0])) {
            $relations[0] = $contextid;
        }
        $maxdistance = max(array_keys($relations));
        for ($i = 0; $i <= $maxdistance; $i++) {
            if (($relations[$i] ?? 0) < 1) {
                debugging("relatedcontextid with distance $i is missing for context $contextid", DEBUG_DEVELOPER);
                return;
            }
        }
        if (count($relations) != $maxdistance + 1) {
            debugging("Unexpected number of relations detected for context $contextid", DEBUG_DEVELOPER);
            return;
        }
        if ($relations[0] != $contextid) {
            debugging("relatedcontextid with distance 0 must match own contextid $contextid", DEBUG_DEVELOPER);
            return;
        }
        if ($relations[$maxdistance] != context_system::instance()->id) {
            debugging("Top parent of context $contextid must be a system context", DEBUG_DEVELOPER);
            return;
        }

        $dbfamily = $DB->get_dbfamily();
        if ($dbfamily === 'postgres') {
            $values = [];
            foreach ($relations as $distance => $relatedcontextid) {
                $values[] = '(' . $contextid . ',' . (int)$distance . ',' . (int)$relatedcontextid . ')';
            }
            $values = implode(', ', $values);
            $sql = "INSERT INTO {tool_mulib_context_map} (contextid, distance, relatedcontextid) VALUES $values
                    ON CONFLICT (contextid, distance) DO UPDATE SET relatedcontextid = excluded.relatedcontextid";
            $DB->execute($sql);
        } else if ($dbfamily === 'mysql') {
            $values = [];
            foreach ($relations as $distance => $relatedcontextid) {
                $values[] = '(' . $contextid . ',' . (int)$distance . ',' . (int)$relatedcontextid . ')';
            }
            $values = implode(', ', $values);
            $sql = "INSERT INTO {tool_mulib_context_map} (contextid, distance, relatedcontextid) VALUES $values
                    ON DUPLICATE KEY UPDATE relatedcontextid = VALUES(relatedcontextid)";
            $DB->execute($sql);
        } else {
            foreach ($relations as $distance => $relatedcontextid) {
                $relation = ['contextid' => $contextid, 'distance' => $distance, 'relatedcontextid' => $relatedcontextid];
                if ($DB->record_exists('tool_mulib_context_map', ['contextid' => $relation['contextid'], 'distance' => $relation['distance']])) {
                    $DB->set_field('tool_mulib_context_map', 'relatedcontextid', $relatedcontextid, ['contextid' => $contextid, 'distance' => $distance]);
                } else {
                    try {
                        $DB->insert_record('tool_mulib_context_map', $relation, false);
                    } catch (\Exception $ex) {
                        // Could be a concurrent insert.
                        $DB->set_field('tool_mulib_context_map', 'relatedcontextid', $relatedcontextid, ['contextid' => $contextid, 'distance' => $distance]);
                    }
                }
            }
        }

        $DB->delete_records_select('tool_mulib_context_map', "contextid = ? AND distance > ?", [$contextid, $maxdistance]);
    }

    /**
     * Delete relevant records from context map table.
     * @param int $contextid
     * @return void
     */
    public static function delete_context_map(int $contextid): void {
        global $DB;
        $DB->delete_records('tool_mulib_context_map', ['contextid' => $contextid]);
    }

    /**
     * Observer.
     * @param \core\event\user_created $event
     */
    public static function callback_user_created(\core\event\user_created $event): void {
        $context = $event->get_context();
        self::upsert_context_parent($context->id, $context->get_parent_context()->id);
        self::upsert_context_map($context->id, $context->get_parent_context_ids(true));
    }

    /**
     * Observer.
     * @param \tool_mutenancy\event\user_allocated $event
     */
    public static function callback_user_allocated(\tool_mutenancy\event\user_allocated $event): void {
        $context = $event->get_context();
        self::upsert_context_parent($context->id, $context->get_parent_context()->id);
        self::upsert_context_map($context->id, $context->get_parent_context_ids(true));
    }

    /**
     * Observer.
     * @param \core\event\user_deleted $event
     */
    public static function callback_user_deleted(\core\event\user_deleted $event): void {
        $context = $event->get_context();
        self::delete_context_parent($context->id);
        self::delete_context_map($context->id);
    }

    /**
     * Observer.
     * @param \tool_mutenancy\event\tenant_created $event
     */
    public static function callback_tenant_created(\tool_mutenancy\event\tenant_created $event): void {
        $context = $event->get_context();
        self::upsert_context_parent($context->id, $context->get_parent_context()->id);
        self::upsert_context_map($context->id, $context->get_parent_context_ids(true));
    }

    /**
     * Observer.
     * @param \tool_mutenancy\event\tenant_deleted $event
     */
    public static function callback_tenant_deleted(\tool_mutenancy\event\tenant_deleted $event): void {
        $context = $event->get_context();
        self::delete_context_parent($context->id);
        self::delete_context_map($context->id);
    }

    /**
     * Observer.
     * @param \core\event\course_category_created $event
     */
    public static function callback_course_category_created(\core\event\course_category_created $event): void {
        $context = $event->get_context();
        self::upsert_context_parent($context->id, $context->get_parent_context()->id);
        self::upsert_context_map($context->id, $context->get_parent_context_ids(true));
    }

    /**
     * Observer.
     * @param \core\event\course_category_updated $event
     */
    public static function callback_course_category_updated(\core\event\course_category_updated $event): void {
        global $DB;
        $context = $event->get_context();

        $oldparentcontextid = $DB->get_field('tool_mulib_context_parent', 'parentcontextid', ['contextid' => $context->id]);
        if (!$oldparentcontextid || $oldparentcontextid == $context->get_parent_context()->id) {
            return;
        }

        self::upsert_context_parent($context->id, $context->get_parent_context()->id);
        self::upsert_context_map($context->id, $context->get_parent_context_ids(true));

        // Guess if a direct update is more expensive than full rebuild.
        $sql = "SELECT COUNT('x')
                  FROM {context} ctx
                  JOIN {tool_mulib_context_map} map ON map.contextid = ctx.id
                 WHERE map.relatedcontextid = :contextid";
        $updatecount = $DB->count_records_sql($sql, ['contextid' => $context->id]);
        if ($updatecount > self::MAX_CATEGORY_UPDATE_COUNT) {
            self::build();
            return;
        }

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                  JOIN {tool_mulib_context_map} map ON map.contextid = ctx.id
                 WHERE map.relatedcontextid = :contextid
              ORDER BY ctx.depth ASC, ctx.id ASC";
        $rs = $DB->get_recordset_sql($sql, ['contextid' => $context->id]);
        foreach ($rs as $contextrecord) {
            \context_helper::preload_from_record($contextrecord);
            $subcontext = context::instance_by_id($contextrecord->id);
            self::upsert_context_map($subcontext->id, $subcontext->get_parent_context_ids(true));
        }
        $rs->close();
    }

    /**
     * Observer.
     * @param \core\event\course_category_deleted $event
     */
    public static function callback_course_category_deleted(\core\event\course_category_deleted $event): void {
        $context = $event->get_context();
        self::delete_context_parent($context->id);
        self::delete_context_map($context->id);
    }

    /**
     * Observer.
     * @param \core\event\course_created $event
     */
    public static function callback_course_created(\core\event\course_created $event): void {
        $context = $event->get_context();
        self::upsert_context_parent($context->id, $context->get_parent_context()->id);
        self::upsert_context_map($context->id, $context->get_parent_context_ids(true));
    }

    /**
     * Observer.
     * @param \core\event\course_updated $event
     */
    public static function callback_course_updated(\core\event\course_updated $event): void {
        global $DB;
        $context = $event->get_context();

        $oldparentcontextid = $DB->get_field('tool_mulib_context_parent', 'parentcontextid', ['contextid' => $context->id]);
        if (!$oldparentcontextid || $oldparentcontextid == $context->get_parent_context()->id) {
            return;
        }

        self::upsert_context_parent($context->id, $context->get_parent_context()->id);
        self::upsert_context_map($context->id, $context->get_parent_context_ids(true));
    }

    /**
     * Observer.
     * @param \core\event\course_deleted $event
     */
    public static function callback_course_deleted(\core\event\course_deleted $event): void {
        $context = $event->get_context();
        self::delete_context_parent($context->id);
        self::delete_context_map($context->id);
    }
}
