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

namespace tool_muprog\local;

use stdClass;

/**
 * Program calendar events helper.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class calendar {
    /** @var null|string */
    private static $oldforceflang = null;

    /** @var string start type */
    public const EVENTTYPE_START = 'programstart';
    /** @var string due type */
    public const EVENTTYPE_DUE = 'programdue';
    /** @var string end type */
    public const EVENTTYPE_END = 'programend';

    /**
     * Creates calendar events for allocation.
     *
     * @param stdClass $allocation
     * @param stdClass $program
     * @return void
     */
    public static function fix_allocation_events(stdClass $allocation, stdClass $program): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        if ($allocation->programid != $program->id) {
            throw new \coding_exception('Invalid parameter mix');
        }

        $types = [self::EVENTTYPE_START, self::EVENTTYPE_DUE, self::EVENTTYPE_END];

        $user = $DB->get_record('user', ['id' => $allocation->userid, 'deleted' => 0, 'confirmed' => 1]);
        $records = $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocation->id]);

        if (!$user || $program->archived || $allocation->archived || $allocation->timecompleted) {
            // Delete all events.
            foreach ($records as $event) {
                $calendarevent = \calendar_event::load($event);
                $calendarevent->delete();
            }
            if ($allocation->calendarupdated) {
                $DB->set_field('tool_muprog_allocation', 'calendarupdated', 0, ['id' => $allocation->id]);
                $allocation->calendarupdated = '0';
            }
            return;
        }

        $events = [];
        foreach ($records as $event) {
            if (!in_array($event->eventtype, $types)) {
                $calendarevent = \calendar_event::load($event);
                $calendarevent->delete();
                continue;
            }
            if (isset($events[$event->eventtype])) {
                debugging('Duplicate program calendar event detected for allocationid: ' . $allocation->id, DEBUG_DEVELOPER);
                $calendarevent = \calendar_event::load($event);
                $calendarevent->delete();
                continue;
            }
            $events[$event->eventtype] = $event;
        }
        unset($records);

        self::force_language($user->lang);
        try {
            foreach ($types as $type) {
                if ($type === self::EVENTTYPE_START) {
                    $time = $allocation->timestart;
                } else if ($type === self::EVENTTYPE_DUE) {
                    $time = $allocation->timedue;
                } else if ($type === self::EVENTTYPE_END) {
                    $time = $allocation->timeend;
                } else {
                    throw new \coding_exception('invalid event type');
                }

                $event = $events[$type] ?? null;

                if (!$time) {
                    if ($event) {
                        $calendarevent = \calendar_event::load($event);
                        $calendarevent->delete();
                    }
                    continue;
                }

                $name = get_string('calendar' . $type, 'tool_muprog', $program->fullname);
                $description = $program->description;
                $format = $program->descriptionformat;
                if ($format == FORMAT_HTML || $format == FORMAT_MOODLE) {
                    // Embedded files are not supported.
                    $description = strip_pluginfile_content($description);
                }

                if (!$event) {
                    $data = [
                        'name' => $name,
                        'description' => $description,
                        'format' => $format,
                        'courseid' => 0,
                        'groupid' => 0,
                        'userid' => $allocation->userid,
                        'component' => 'tool_muprog',
                        'eventtype' => $type,
                        'modulename' => '',
                        'instance' => $allocation->id,
                        'type' => CALENDAR_EVENT_TYPE_ACTION,
                        'timestart' => $time,
                        'timeduration' => 0,
                        'visible' => 1,
                    ];
                    \calendar_event::create($data, false);
                } else {
                    $data = [];
                    if ($event->name !== $name) {
                        $data['name'] = $name;
                    }
                    if ($event->description !== $description) {
                        $data['description'] = $description;
                    }
                    if ($event->format != $format) {
                        $data['format'] = $format;
                    }
                    if ($event->timestart != $time) {
                        $data['timestart'] = $time;
                    }

                    if ($data) {
                        $calendarevent = \calendar_event::load($event);
                        $calendarevent->update($data, false);
                    }
                }
            }
            if (!$allocation->calendarupdated) {
                $DB->set_field('tool_muprog_allocation', 'calendarupdated', 1, ['id' => $allocation->id]);
                $allocation->calendarupdated = '1';
            }
        } finally {
            self::revert_language();
        }
    }

    /**
     * Check and fix all program/allocation calendar events.
     *
     * @param stdClass|null $program
     * @param \progress_trace|null $trace
     * @return void
     */
    public static function fix_program_events(?stdClass $program, ?\progress_trace $trace = null): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        // Delete calendar events with no matching allocations, archived or when program completed;
        // skip missing check if looking at one program only.
        if ($trace) {
            $trace->output('deleting events', 1);
        }
        $params = [];
        $programselect = "";
        if ($program) {
            $params['programid'] = $program->id;
            $programselect = " AND pa.programid = :programid";
        }
        $sql = "SELECT e.*
                  FROM {event} e
                  JOIN {user} u ON u.id = e.userid AND u.deleted = 0
             LEFT JOIN {tool_muprog_allocation} pa ON pa.id = e.instance
             LEFT JOIN {tool_muprog_program} p ON p.id = pa.programid
                 WHERE e.component = 'tool_muprog'
                       AND (pa.id IS NULL OR pa.archived = 1 OR p.archived = 1 OR pa.timecompleted IS NOT NULL)
                       $programselect
             ORDER BY e.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $event) {
            $calendarevent = \calendar_event::load($event);
            $calendarevent->delete();
        }
        $rs->close();

        if ($program && $program->archived) {
            // Nothing to do.
            return;
        }

        // Add and update calendar events.
        if ($trace) {
            $trace->output('adding and updating events', 1);
        }
        $params = [
            'pstart' => self::EVENTTYPE_START,
            'pdue' => self::EVENTTYPE_DUE,
            'pend' => self::EVENTTYPE_END,
        ];
        $programselect = "";
        if ($program) {
            $params['programid'] = $program->id;
            $programselect = " AND pa.programid = :programid";
        }
        $sql = "SELECT pa.*
                  FROM {tool_muprog_program} p
                  JOIN {tool_muprog_allocation} pa ON pa.programid = p.id
                  JOIN {user} u ON u.id = pa.userid AND u.deleted = 0
             LEFT JOIN {event} es ON es.instance = pa.id AND es.component = 'tool_muprog' AND es.eventtype = :pstart
             LEFT JOIN {event} ed ON ed.instance = pa.id AND ed.component = 'tool_muprog' AND ed.eventtype = :pdue
             LEFT JOIN {event} ee ON ee.instance = pa.id AND ee.component = 'tool_muprog' AND ee.eventtype = :pend
                 WHERE p.archived = 0 AND pa.archived = 0 AND pa.timecompleted IS NULL
                       AND (
                           pa.calendarupdated = 0
                           OR (es.timestart <> pa.timestart OR es.id IS NULL)
                           OR ((ed.timestart <> pa.timedue) OR (ed.id IS NULL AND pa.timedue IS NOT NULL) OR (ed.id IS NOT NULL AND pa.timedue IS NULL))
                           OR ((ee.timestart <> pa.timeend) OR (ee.id IS NULL AND pa.timeend IS NOT NULL) OR (ee.id IS NOT NULL AND pa.timeend IS NULL))
                       )
                       $programselect
              ORDER BY p.id ASC, pa.id ASC";

        $rs = $DB->get_recordset_sql($sql, $params);
        $allocationprogram = $program;
        foreach ($rs as $allocation) {
            if (!$allocationprogram || $allocationprogram->id != $allocation->programid) {
                $allocationprogram = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);
            }
            self::fix_allocation_events($allocation, $allocationprogram);
        }
        $rs->close();
    }

    /**
     * Delete calendar events associated with allocation.
     *
     * @param int $allocationid
     * @return void
     */
    public static function delete_allocation_events(int $allocationid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $events = $DB->get_records('event', ['component' => 'tool_muprog', 'instance' => $allocationid], 'id ASC');
        foreach ($events as $event) {
            $calendarevent = \calendar_event::load($event);
            $calendarevent->delete();
        }
    }

    /**
     * Delete program calendar events.
     *
     * @param int $programid
     * @return void
     */
    public static function delete_program_events(int $programid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $sql = "SELECT e.*
                  FROM {event} e
                  JOIN {tool_muprog_allocation} pa ON pa.id = e.instance AND e.component = 'tool_muprog'
                 WHERE pa.programid = :programid
              ORDER BY e.id ASC";
        $params = ['programid' => $programid];
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $event) {
            $calendarevent = \calendar_event::load($event);
            $calendarevent->delete();
        }
        $rs->close();
    }

    /**
     * Invalidate program calendar events.
     *
     * @param int $programid
     * @return void
     */
    public static function invalidate_program_events(int $programid): void {
        global $DB;
        $DB->set_field('tool_muprog_allocation', 'calendarupdated', 0, ['programid' => $programid, 'calendarupdated' => 1]);
    }

    /**
     * Temporarily force a different language for calendar events.
     *
     * NOTE: better not make this hack public to prevent abuse, it would not be testable anyway.
     *
     * @param string $lang
     * @return void
     */
    protected static function force_language(string $lang): void {
        global $SESSION, $CFG;

        if (isset(self::$oldforceflang)) {
            debugging('Calendar language was already forced', DEBUG_DEVELOPER);
        }

        if (!$lang || !get_string_manager()->translation_exists($lang, false)) {
            $lang = $CFG->lang;
        }

        if (current_language() === $lang) {
            return;
        }

        self::$oldforceflang = $SESSION->forcelang ?? null;
        $SESSION->forcelang = $lang;
        moodle_setlocale();
    }

    /**
     * Revert forcing of different language.
     *
     * @return void
     */
    protected static function revert_language(): void {
        global $SESSION;

        if (!isset(self::$oldforceflang) && !isset($SESSION->forcelang)) {
            return;
        }

        if (isset(self::$oldforceflang) && self::$oldforceflang !== '') {
            $SESSION->forcelang = self::$oldforceflang;
        } else {
            unset($SESSION->forcelang);
        }
        self::$oldforceflang = null;
        moodle_setlocale();
    }
}
