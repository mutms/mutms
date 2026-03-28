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

namespace tool_muprog\local\content;

use stdClass;
use tool_muprog\local\allocation;

/**
 * A offline attendance item.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attendance extends item {
    /** @var int no show */
    public const STATUS_NOSHOW = 3;
    /** @var int attended but failed */
    public const STATUS_FAILED = 2;
    /** @var int passed during attendance */
    public const STATUS_COMPLETED = 1;
    /** @var int not taken yet - NULL value is used in database */
    public const STATUS_NOTSET = 0;

    #[\Override]
    public static function get_type(): string {
        return 'attendance';
    }

    #[\Override]
    public static function get_type_name(): string {
        return get_string('attendance', 'tool_muprog');
    }

    #[\Override]
    protected static function init_from_record(\stdClass $record, ?item $previous, array &$unusedrecords, array &$prerequisites): item {
        if ($record->type !== 'attendance' || $record->topitem || $record->courseid !== null || $record->creditframeworkid !== null) {
            throw new \core\exception\coding_exception('Invalid attendance item');
        }
        return parent::init_from_record($record, $previous, $unusedrecords, $prerequisites);
    }

    /**
     * Returns all possible attendance statuses.
     *
     * @return array
     */
    public static function get_statuses(): array {
        return [
            self::STATUS_NOTSET => get_string('notset', 'tool_muprog'), // NULL used in database.
            self::STATUS_NOSHOW => get_string('attendance_status_noshow', 'tool_muprog'),
            self::STATUS_FAILED => get_string('attendance_status_failed', 'tool_muprog'),
            self::STATUS_COMPLETED => get_string('attendance_status_completed', 'tool_muprog'),
        ];
    }

    /**
     * Take user attendance.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function take_attendance(stdClass $data): stdClass {
        global $DB, $USER;

        $item = $DB->get_record('tool_muprog_item', ['id' => $data->itemid, 'type' => 'attendance'], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $item->programid], '*', MUST_EXIST);
        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $data->userid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $allocation->userid], '*', MUST_EXIST);

        $attendance = $DB->get_record('tool_muprog_attendance', ['itemid' => $item->id, 'userid' => $user->id]);
        if (!$attendance) {
            $id = $DB->insert_record('tool_muprog_attendance', ['itemid' => $item->id, 'userid' => $user->id]);
            $attendance = $DB->get_record('tool_muprog_attendance', ['id' => $id], '*', MUST_EXIST);
        }
        $oldattendance = clone($attendance);

        if ($data->status == self::STATUS_NOTSET) {
            $attendance->status = null;
            $attendance->timeeffective = null;
            $attendance->takenby = null;
        } else {
            $statuses = self::get_statuses();
            if (!isset($statuses[$data->status])) {
                throw new \core\exception\invalid_parameter_exception('Invalid attendance status');
            }
            $attendance->status = (int)$data->status;

            if (!empty($data->timeeffective)) {
                $attendance->timeeffective = (int)$data->timeeffective;
            } else if (!$attendance->timeeffective) {
                $attendance->timeeffective = time();
            }

            $attendance->takenby = $USER->id;
        }

        $DB->update_record('tool_muprog_attendance', $attendance);
        $attendance = $DB->get_record('tool_muprog_attendance', ['id' => $oldattendance->id], '*', MUST_EXIST);

        // Evidence completion takes precedence.
        $evidence = $DB->get_record('tool_muprog_evidence', ['userid' => $allocation->userid, 'itemid' => $item->id]);
        if (!$evidence) {
            $completion = $DB->get_record('tool_muprog_completion', ['allocationid' => $allocation->id, 'itemid' => $item->id]);
            if ($attendance->status == self::STATUS_COMPLETED) {
                $timecompleted = $attendance->timeeffective + $item->completiondelay;
                if (!$completion || $completion->timecompleted != $timecompleted) {
                    allocation::update_item_completion((object)['allocationid' => $allocation->id, 'itemid' => $item->id, 'timecompleted' => $timecompleted]);
                }
            } else {
                if ($completion && $completion->timecompleted) {
                    allocation::update_item_completion((object)['allocationid' => $allocation->id, 'itemid' => $item->id, 'timecompleted' => null]);
                }
            }
        }

        return $attendance;
    }
}
