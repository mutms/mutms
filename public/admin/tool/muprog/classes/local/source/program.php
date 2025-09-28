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
 * Program allocation based on completion of other program.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'program';
    }

    /**
     * Can settings of this source be imported to other program?
     *
    /**
     * Can settings of this source be imported to other program?
     *
     * @param stdClass $fromprogram
     * @param stdClass $targetprogram
     * @return bool
     */
    public static function is_import_allowed(stdClass $fromprogram, stdClass $targetprogram): bool {
        return false;
    }

    /**
     * Render details about this enabled source in a programs management ui.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        global $DB;

        $result = parent::render_status_details($program, $source);

        if ($source && $source->auxint1) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $source->auxint1]);
            if ($program) {
                $name = format_string($program->fullname);
                $context = \context::instance_by_id($program->contextid);
                if (has_capability('tool/muprog:view', $context)) {
                    $url = new \moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
                    $name = \html_writer::link($url, $name);
                }
            } else {
                $name = get_string('error');
            }
            $result .= " - $name";
        }

        return $result;
    }

    #[\Override]
    public static function is_allocation_archive_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    #[\Override]
    public static function is_allocation_restore_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    /**
     * Is it possible to manually delete user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function is_allocation_delete_possible(stdClass $program, stdClass $source, stdClass $allocation): bool {
        global $DB;

        if (
            $program->id != $source->programid
            || $program->id != $allocation->programid
            || $source->id != $allocation->sourceid
        ) {
            throw new \coding_exception('invalid parameters');
        }
        if ($program->archived) {
            return false;
        }
        if (!$allocation->archived) {
            return false;
        }
        if (!$source->auxint1) {
            return true;
        }

        return !$DB->record_exists('tool_muprog_allocation', ['programid' => $source->auxint1, 'userid' => $allocation->userid]);
    }

    /**
     * Make sure users are allocated properly.
     *
     * This is expected to be called from cron and when
     * program allocation settings are updated.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @return bool true if anything updated
     */
    public static function fix_allocations(?int $programid, ?int $userid): bool {
        global $DB;

        $updated = false;

        // Allocate all users that completed the linked program,
        // do not archive anything because programs can be reset later.

        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = 'AND p.id = :programid';
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND opa.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $params['now3'] = $now;
        $sql = "SELECT p.id, s.id AS sourceid, opa.userid
                  FROM {tool_muprog_allocation} opa
                  JOIN {tool_muprog_source} s ON s.type = 'program' AND s.auxint1 = opa.programid
                  JOIN {tool_muprog_program} p ON p.id = s.programid
             LEFT JOIN {tool_muprog_allocation} pa ON pa.programid = p.id AND pa.userid = opa.userid
                 WHERE opa.timecompleted <= :now3
                       AND pa.id IS NULL
                       AND p.archived = 0
                       AND (p.timeallocationstart IS NULL OR p.timeallocationstart <= :now1)
                       AND (p.timeallocationend IS NULL OR p.timeallocationend > :now2)
                       $programselect $userselect
              ORDER BY p.id ASC, s.id ASC, opa.userid ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        $lastprogram = null;
        $lastsource = null;
        foreach ($rs as $record) {
            if ($lastprogram && $lastprogram->id == $record->id) {
                $program = $lastprogram;
            } else {
                $program = $DB->get_record('tool_muprog_program', ['id' => $record->id], '*', MUST_EXIST);
                $lastprogram = $program;
            }
            if ($lastsource && $lastsource->id == $record->sourceid) {
                $source = $lastsource;
            } else {
                $source = $DB->get_record('tool_muprog_source', ['id' => $record->sourceid], '*', MUST_EXIST);
                $lastsource = $source;
            }
            self::allocation_create($program, $source, $record->userid, []);
            $updated = true;
        }
        $rs->close();

        return $updated;
    }

    /**
     * Called when a program is completed.
     *
     * @param \tool_muprog\event\allocation_completed $event
     * @return void
     */
    public static function observe_allocation_completed(\tool_muprog\event\allocation_completed $event): void {
        global $DB;

        $allocation = $event->get_record_snapshot('tool_muprog_allocation', $event->objectid);
        if ($allocation->timecompleted === null || $allocation->timecompleted > time()) {
            return;
        }

        $sql = "SELECT s.*
                  FROM {tool_muprog_source} s
                  JOIN {tool_muprog_program} p ON p.id = s.programid
             LEFT JOIN {tool_muprog_allocation} pa ON pa.programid = p.id AND pa.userid = :userid
                 WHERE s.type = 'program' AND s.auxint1 = :programid AND p.archived = 0
                       AND pa.id IS NULL
        ";
        $params = ['userid' => $allocation->userid, 'programid' => $allocation->programid];

        $sources = $DB->get_records_sql($sql, $params);
        if (!$sources) {
            return;
        }

        foreach ($sources as $source) {
            $program = $event->get_record_snapshot('tool_muprog_program', $source->programid);
            self::allocation_create($program, $source, $allocation->userid, []);
        }
    }
}
