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

namespace tool_muprog\local\source;

use stdClass;

/**
 * External database query allocation.
 *
 * This source is adding new allocation only, existing allocation dates are not updated automatically during sync!
 * Optionally allocation can be archived if user allocation row disappears from the external query.
 *
 * Meaning of aux fields:
 * - auxint1: id from tool_mulib_extdb_query, null means sync is disabled
 * - auxint2: 1 means to archive users that are not present in extdb query anymore, 0 means allocate only
 * - auxint3: is timestamp when adhoc sync task was scheduled, null after sync start
 * - auxint4: is timestamp when last allocation sync started, null after sync completion
 * - auxint5: is timestamp when last allocation sync completed
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class extdb extends base {
    /** @var int */
    public const ADHOC_SYNC_TIMEOUT = 60 * 60 * 1; // It should not take longer than 1 hour to complete the sync.
    /** @var int */
    public const MIN_DATE = 946684800; // Parsed dates older than 2000 are considered invalid.

    #[\Override]
    public static function get_type(): string {
        return 'extdb';
    }

    #[\Override]
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        global $DB, $OUTPUT;

        $result = parent::render_status_details($program, $source);

        if (!$source) {
            return $result;
        }
        if (!$source->auxint1) {
            return get_string('disabled', 'core_admin');
        }

        $query = $DB->get_record('tool_mulib_extdb_query', ['id' => $source->auxint1]);
        if ($query) {
            $result .= ' (' . s($query->name) . ')';
            $context = \context::instance_by_id($program->contextid);
            if (!$program->archived && has_capability('tool/muprog:allocate', $context)) {
                $label = get_string('source_extdb_sync', 'tool_muprog');
                $editurl = new \moodle_url('/admin/tool/muprog/management/source_extdb_sync.php', ['sourceid' => $source->id]);
                $editbutton = new \tool_mulib\output\ajax_form\icon($editurl, $label, 'i/reload');
                $editbutton->set_modal_title(static::get_name());
                $result .= $OUTPUT->render($editbutton);
            }
        } else {
            $result .= ' - ' . get_string('error');
        }

        return $result;
    }

    #[\Override]
    public static function is_update_allowed(stdClass $program): bool {
        // Dates can be changed manually.
        return true;
    }

    #[\Override]
    public static function is_allocation_archive_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        if ($source->auxint2 == 1 && $source->auxint1 > 0) {
            // Prevent archiving if query selected and automatic archiving is enabled.
            return false;
        }
        return parent::is_allocation_archive_possible($program, $source, $allocation);
    }

    #[\Override]
    public static function is_allocation_restore_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        if ($source->auxint2 == 1 && $source->auxint1 > 0) {
            // Prevent restoring if query selected and automatic archiving is enabled.
            return false;
        }
        return parent::is_allocation_restore_possible($program, $source, $allocation);
    }

    #[\Override]
    public static function is_allocation_delete_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        if (!$allocation->archived) {
            // Prevent deleting if allocation not archived.
            return false;
        }
        return parent::is_allocation_restore_possible($program, $source, $allocation);
    }

    #[\Override]
    public static function is_import_allowed(stdClass $fromprogram, stdClass $targetprogram): bool {
        // Importing of selected query is not allowed.
        return false;
    }

    #[\Override]
    public static function fix_allocations(?int $programid, ?int $userid): bool {
        // The allocations are only changed from adhoc task,
        // nothing to do here, users can trigger sync manually or can wait for cron.
        return false;
    }

    /**
     * Trigger ad-hoc tasks for each program with external allocation.
     */
    public static function cron(): void {
        global $DB;

        $sql = "SELECT s.*
                  FROM {tool_muprog_source} s
                  JOIN {tool_muprog_program} p ON p.id = s.programid AND p.archived = 0
                  JOIN {tool_mulib_extdb_query} q ON q.id = s.auxint1
                 WHERE s.type = 'extdb' AND (s.auxint4 IS NULL OR s.auxint4 < :timedout)
              ORDER BY s.id ASC";
        $params = ['timedout' => time() - self::ADHOC_SYNC_TIMEOUT];
        $sources = $DB->get_records_sql($sql, $params);
        foreach ($sources as $source) {
            self::sync_asap($source);
        }
    }

    /**
     * Allocate users using external database query via ad-hoc task.
     *
     * @param stdClass $source
     * @return bool true if task syn created, false if not
     */
    public static function sync_asap(stdClass $source): bool {
        if (!$source->auxint1 || $source->type !== self::get_type()) {
            return false;
        }

        $task = \tool_muprog\task\extdb_sync::create_from_source($source);
        \core\task\manager::queue_adhoc_task($task, false);

        return true;
    }

    /**
     * Sync allocations now.
     *
     * NOTE: this must be called from the ad-hoc task only!
     *
     * @param stdClass $source
     * @return bool success
     */
    public static function sync(stdClass $source): bool {
        global $DB;

        raise_memory_limit(MEMORY_EXTRA);

        $program = $DB->get_record('tool_muprog_program', ['id' => $source->programid]);
        if (!$program || $program->archived) {
            return false;
        }
        $context = \context::instance_by_id($program->contextid);
        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = $context->tenantid;
        } else {
            $tenantid = null;
        }

        $query = new \tool_muprog\local\extdb\query\allocation($source->auxint1, $program);
        $extrecordset = $query->query();

        $first = $extrecordset->current();
        if ($first === null) {
            // No rows with allocations returned. we would already get an exception on error.
            if ($source->auxint2 == 1) {
                // Archive all allocations.
                $sql = "SELECT a.id
                          FROM {tool_muprog_allocation} a
                          JOIN {tool_muprog_source} s ON s.id = a.sourceid AND s.type = 'extdb'
                          JOIN {tool_muprog_program} p ON p.id = a.programid
                         WHERE a.archived = 0 AND s.id = :sourceid
                      ORDER BY a.id ASC";
                $aids = $DB->get_fieldset_sql($sql, ['sourceid' => $source->id]);
                foreach ($aids as $aid) {
                    self::allocation_archive($aid);
                }
            }
            return true;
        }

        $identifiers = ['userid' => 'id', 'username' => 'username', 'useremail' => 'email', 'useridnumber' => 'idnumber'];
        $uident = array_intersect(array_keys($first), array_keys($identifiers));
        if (count($uident) !== 1) {
            debugging('External query does not contain exactly one valid user identifier column', DEBUG_NORMAL);
            return false;
        }
        $uident = reset($uident);

        $processed = []; // List of processed users.
        foreach ($extrecordset as $row) {
            if (trim($row[$uident] ?? '') === '') {
                // Missing user identifier.
                continue;
            }
            $select = $identifiers[$uident] . " = :uident AND deleted = 0";
            $params = ['uident' => $row[$uident]];
            if ($tenantid) {
                // Ignore or archive users from other tenants.
                $select .= ' AND tenantid IS NULL OR tenantid = :tenantid';
            }
            $users = $DB->get_records_select('user', $select, $params, 'id ASC', 'id, confirmed');
            if (!$users) {
                // Ignore unknown and deleted users.
                continue;
            }
            if (count($users) > 1) {
                // Ignore records that match multiple users.
                foreach ($users as $user) {
                    $processed[$user->id] = $user->id;
                }
                continue;
            }
            $user = reset($users);
            $processed[$user->id] = $user->id;

            if (!$user->confirmed) {
                // Ignore unconfirmed users.
                continue;
            }

            $sql = "SELECT a.*, s.type
                     FROM {tool_muprog_allocation} a
                LEFT JOIN {tool_muprog_source} s ON s.id = a.sourceid
                    WHERE a.programid = :programid AND a.userid = :userid";
            $allocation = $DB->get_record_sql($sql, ['programid' => $program->id, 'userid' => $user->id]);
            if ($allocation) {
                if ($allocation->type !== 'extdb') {
                    // Ignore all other sources.
                    continue;
                }
                // Check if archived.
                if ($source->auxint2 == 1 && $allocation->archived) {
                    self::allocation_restore($allocation->id);
                }
                continue;
            }

            // Allocate user.
            $dateoverrides = [];
            foreach (['timestart', 'timedue', 'timeend'] as $override) {
                if (empty($row[$override])) {
                    continue;
                }
                $date = $row[$override];
                if (is_number($date)) {
                    $dateoverrides[$override] = $date;
                    continue;
                }
                $date = strtotime($date);
                if ($date === false || $date < self::MIN_DATE) {
                    continue;
                }
                $dateoverrides[$override] = $date;
            }
            self::allocation_create($program, $source, $user->id, [], $dateoverrides);
        }
        $extrecordset->close();

        if ($source->auxint2 == 1) {
            // Archived missing users.
            $rs = $DB->get_recordset(
                'tool_muprog_allocation',
                ['sourceid' => $source->id, 'archived' => 0],
                'id ASC',
                'id, userid'
            );
            foreach ($rs as $allocation) {
                if (!isset($processed[$allocation->userid])) {
                    self::allocation_archive($allocation->id);
                }
            }
            $rs->close();
        }

        return true;
    }
}
