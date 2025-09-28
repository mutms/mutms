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

use tool_muprog\local\source\approval;
use tool_muprog\local\source\base;
use tool_muprog\local\source\cohort;
use tool_muprog\local\source\manual;
use tool_muprog\local\source\selfallocation;
use tool_muprog\local\source\mucertify;
use tool_muprog\local\source\program;
use stdClass;

/**
 * Program allocation abstraction.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation {
    /**
     * Returns list of all source classes present.
     *
     * @return string[] type => classname
     */
    public static function get_source_classes(): array {
        // Note: in theory this could be extended to load arbitrary classes.
        $types = [
            manual::get_type() => manual::class,
            selfallocation::get_type() => selfallocation::class,
            approval::get_type() => approval::class,
            cohort::get_type() => cohort::class,
            program::get_type() => program::class,
        ];

        if (file_exists(__DIR__ . '/../../../mucertify/version.php')) {
            $types[mucertify::get_type()] = mucertify::class;
        }

        return $types;
    }

    /**
     * Find allocation source class.
     *
     * @param string $type
     * @return null|class-string<\tool_muprog\local\source\base>
     */
    public static function get_source_classname(string $type): ?string {
        $types = self::get_source_classes();
        return $types[$type] ?? null;
    }

    /**
     * Returns list of all source names.
     *
     * @return string[] type => source name
     */
    public static function get_source_names(): array {
        /** @var base[] $classes */ // Type hack.
        $classes = self::get_source_classes();

        $result = [];
        foreach ($classes as $class) {
            $result[$class::get_type()] = $class::get_name();
        }

        return $result;
    }

    /**
     * Returns default allocated program start date.
     *
     * @param stdClass $program
     * @param int $timeallocated
     * @return int
     */
    public static function get_default_timestart(stdClass $program, int $timeallocated): int {
        $startdate = (object)json_decode($program->startdatejson);
        if ($startdate->type === 'allocation') {
            return $timeallocated;
        } else if ($startdate->type === 'date') {
            return (int)$startdate->date;
        } else if ($startdate->type === 'delay') {
            $d = new \DateTime('@' . $timeallocated);
            $d->add(new \DateInterval($startdate->delay));
            return $d->getTimestamp();
        } else {
            throw new \coding_exception('invalid program start');
        }
    }

    /**
     * Returns default allocated program due date.
     *
     * @param stdClass $program
     * @param int $timeallocated
     * @param int $timestart
     * @return int
     */
    public static function get_default_timedue(stdClass $program, int $timeallocated, int $timestart): ?int {
        $duedate = (object)json_decode($program->duedatejson);
        if ($duedate->type === 'notset') {
            return null;
        } else if ($duedate->type === 'date') {
            return (int)$duedate->date;
        } else if ($duedate->type === 'delay') {
            $d = new \DateTime('@' . $timeallocated);
            $d->add(new \DateInterval($duedate->delay));
            return $d->getTimestamp();
        } else {
            throw new \coding_exception('invalid program due');
        }
    }

    /**
     * Returns default allocated program end date.
     *
     * @param stdClass $program
     * @param int $timeallocated
     * @param int $timestart
     * @return int
     */
    public static function get_default_timeend(stdClass $program, int $timeallocated, int $timestart): ?int {
        $enddate = (object)json_decode($program->enddatejson);
        if ($enddate->type === 'notset') {
            return null;
        } else if ($enddate->type === 'date') {
            return (int)$enddate->date;
        } else if ($enddate->type === 'delay') {
            $d = new \DateTime('@' . $timeallocated);
            $d->add(new \DateInterval($enddate->delay));
            return $d->getTimestamp();
        } else {
            throw new \coding_exception('invalid program end');
        }
    }

    /**
     * Validate program allocation dates.
     *
     * @param int $timestart
     * @param int|null $timedue
     * @param int|null $timeend
     * @return array of errors
     */
    public static function validate_allocation_dates(int $timestart, ?int $timedue, ?int $timeend): array {
        $errors = [];

        if (!$timestart) {
            $errors['timestart'] = get_string('required');
        }

        if ($timedue && $timedue <= $timestart) {
            $errors['timedue'] = get_string('error');
        }

        if ($timeend && $timeend <= $timestart) {
            $errors['timeend'] = get_string('error');
        }

        if ($timeend && $timedue && $timedue > $timeend) {
            $errors['timedue'] = get_string('error');
        }

        return $errors;
    }

    /**
     * Add and delete course enrolment instances for programs.
     *
     * @param int|null $programid
     * @return void
     */
    public static function fix_enrol_instances(?int $programid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        if (!PHPUNIT_TEST && $DB->is_transaction_started()) {
            debugging('allocation::fix_enrol_instances() is not supposed to be used in transactions', DEBUG_DEVELOPER);
        }

        $plugin = enrol_get_plugin('muprog');

        // Add new instances.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $sql = "SELECT c.*, pi.programid, p.archived AS programarchived
                  FROM {course} c
                  JOIN {tool_muprog_item} pi ON pi.courseid = c.id
                  JOIN {tool_muprog_program} p ON p.id = pi.programid
             LEFT JOIN {enrol} e ON e.courseid = c.id AND e.enrol = 'muprog' AND e.customint1 = pi.programid
                 WHERE e.id IS NULL $programselect
              ORDER BY pi.programid ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $course) {
            $pid = $course->programid;
            unset($course->programid);
            $archived = $course->programarchived;
            unset($course->programarchived);
            $fields = ['customint1' => $pid];
            if ($archived) {
                $fields['status'] = ENROL_INSTANCE_DISABLED;
            } else {
                $fields['status'] = ENROL_INSTANCE_ENABLED;
            }
            $plugin->add_instance($course, $fields);
        }
        $rs->close();

        // Delete left-over instances.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND e.customint1 = :programid";
            $params['programid'] = $programid;
        }
        $sql = "SELECT e.*
                  FROM {enrol} e
             LEFT JOIN {tool_muprog_item} pi ON pi.courseid = e.courseid AND pi.programid = e.customint1
                 WHERE e.enrol = 'muprog' AND pi.id IS NULL $programselect
              ORDER BY e.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $enrol) {
            $plugin->delete_instance($enrol);
        }
        $rs->close();

        // Disable instances in archived courses.
        $params = ['enabled' => ENROL_INSTANCE_ENABLED];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $sql = "SELECT e.*
                  FROM {enrol} e
                  JOIN {tool_muprog_item} pi ON pi.courseid = e.courseid AND pi.programid = e.customint1
                  JOIN {tool_muprog_program} p ON p.id = pi.programid
                 WHERE e.enrol = 'muprog' AND p.archived = 1 AND e.status = :enabled $programselect
              ORDER BY e.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $enrol) {
            $plugin->update_status($enrol, ENROL_INSTANCE_DISABLED);
            $context = \context_course::instance($enrol->courseid);
            role_unassign_all([
                'contextid' => $context->id,
                'component' => 'enrol_muprog',
                'itemid' => $enrol->id,
            ]);
        }
        $rs->close();

        // Enable instances in non-archived courses.
        $params = ['disabled' => ENROL_INSTANCE_DISABLED];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $sql = "SELECT e.*
                  FROM {enrol} e
                  JOIN {tool_muprog_item} pi ON pi.courseid = e.courseid AND pi.programid = e.customint1
                  JOIN {tool_muprog_program} p ON p.id = pi.programid
                 WHERE e.enrol = 'muprog' AND p.archived = 0 AND e.status = :disabled $programselect
              ORDER BY e.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $enrol) {
            $plugin->update_status($enrol, ENROL_INSTANCE_ENABLED);
            // NOTE: roles will be re-added in user enrolment sync later.
        }
        $rs->close();

        // Create missing groups.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $sql = "SELECT pi.courseid, pi.programid, p.fullname, pg.id
                  FROM {tool_muprog_item} pi
                  JOIN {course} c ON c.id = pi.courseid
                  JOIN {tool_muprog_program} p ON p.id = pi.programid
             LEFT JOIN {tool_muprog_group} pg ON pg.programid = p.id AND pg.courseid = pi.courseid
             LEFT JOIN {groups} g ON g.id = pg.groupid
                 WHERE p.archived = 0 AND p.creategroups = 1 AND pi.courseid IS NOT NULL
                       AND (pg.id IS NULL OR g.id IS NULL)
                       $programselect
              ORDER BY pi.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $pg) {
            $trans = $DB->start_delegated_transaction();
            // NOTE: We should probably add some description pointing back to program.
            $data = (object)[
                'courseid' => $pg->courseid,
                'name' => $pg->fullname,
            ];
            $gid = groups_create_group($data);
            if ($pg->id) {
                $DB->set_field('tool_muprog_group', 'groupid', $gid, ['id' => $pg->id]);
            } else {
                $data = (object)[
                    'programid' => $pg->programid,
                    'courseid' => $pg->courseid,
                    'groupid' => $gid,
                ];
                $DB->insert_record('tool_muprog_group', $data);
            }
            $trans->allow_commit();
        }
        $rs->close();

        // Delete obsolete groups.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $sql = "SELECT pg.id, pg.groupid
                  FROM {tool_muprog_group} pg
             LEFT JOIN {tool_muprog_program} p ON p.id = pg.programid
             LEFT JOIN {tool_muprog_item} pi ON pi.programid = pg.programid AND pi.courseid = pg.courseid
                 WHERE (p.id IS NULL OR pi.id IS NULL OR p.creategroups = 0)
                       $programselect
              ORDER BY pg.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $pg) {
            $trans = $DB->start_delegated_transaction();
            groups_delete_group($pg->groupid);
            $DB->delete_records('tool_muprog_group', ['id' => $pg->id]);
            $trans->allow_commit();
        }
        $rs->close();
    }

    /**
     * Check and fix all user enrolments, this expects the course enrol
     * instances were already fixed.
     *
     * NOTE: this can be used as a quick way to make sure all users enrolments are up-to-date.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @param \progress_trace|null $trace
     * @return void
     */
    public static function fix_user_enrolments(?int $programid, ?int $userid, ?\progress_trace $trace = null): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        if (!PHPUNIT_TEST && !$userid && $DB->is_transaction_started()) {
            debugging('allocation::fix_user_enrolments() is not supposed to be used in transactions without userid', DEBUG_DEVELOPER);
        }

        $plugin = enrol_get_plugin('muprog');
        $roleid = get_config('tool_muprog', 'roleid');
        $enrol = null; // Cached last used enrol instance.
        $program = null;  // Cached last used program instance.

        // Delete enrolments if user is not allocated.
        if ($trace) {
            $trace->output('deleting enrolments for users not allocated', 1);
        }
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND ue.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql = "SELECT e.id, ue.userid, gm.groupid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'muprog'
                  JOIN {tool_muprog_item} pi ON pi.courseid = e.courseid AND pi.programid = e.customint1
             LEFT JOIN {tool_muprog_allocation} pa ON pa.programid = pi.programid AND pa.userid = ue.userid
             LEFT JOIN {tool_muprog_group} pg ON pg.programid = pi.programid AND pg.courseid = pi.courseid
             LEFT JOIN {groups_members} gm ON gm.groupid = pg.groupid AND gm.userid = ue.userid
                 WHERE pa.id IS NULL
                       $programselect $userselect
              ORDER BY e.id ASC, ue.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $e) {
            if (!$enrol || $enrol->id != $e->id) {
                $enrol = $DB->get_record('enrol', ['id' => $e->id], '*', MUST_EXIST);
            }
            if ($e->groupid) {
                groups_remove_member($e->groupid, $e->userid);
            }
            $plugin->unenrol_user($enrol, $e->userid);
            $context = \context_course::instance($enrol->courseid);
            role_unassign_all([
                'contextid' => $context->id,
                'component' => 'enrol_muprog',
                'itemid' => $enrol->id,
                'userid' => $e->userid,
            ]);
        }
        $rs->close();

        // Add enrolments for all users as soon as they are allocated,
        // we want teachers to see all future course users (ignore archived programs and allocations).
        if ($trace) {
            $trace->output('adding user enrolments', 1);
        }
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql = "SELECT e.id, pa.userid
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_muprog_item} pi ON pi.programid = pa.programid
                  JOIN {enrol} e ON e.enrol = 'muprog' AND e.customint1 = pi.programid AND e.courseid = pi.courseid
             LEFT JOIN {user_enrolments} ue ON ue.userid = pa.userid AND ue.enrolid = e.id
                 WHERE ue.id IS NULL
                       AND p.archived = 0 AND pa.archived = 0
                       $programselect $userselect
              ORDER BY e.id ASC, pa.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $e) {
            if (!$enrol || $enrol->id != $e->id) {
                $enrol = $DB->get_record('enrol', ['id' => $e->id], '*', MUST_EXIST);
            }
            // Do NOT restore grades, that would be a wrong thing to do here for programs
            // and especially certifications later.
            $plugin->enrol_user($enrol, $e->userid, null, 0, 0, ENROL_USER_SUSPENDED, false);
        }
        $rs->close();

        // Disable all enrolments (and remove roles) for archived, future and past allocations that are active.
        if ($trace) {
            $trace->output('suspending user enrolments', 1);
        }
        $params = ['active' => ENROL_USER_ACTIVE];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND ue.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $sql = "SELECT e.id, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'muprog'
                  JOIN {tool_muprog_item} pi ON pi.courseid = e.courseid AND pi.programid = e.customint1
                  JOIN {tool_muprog_allocation} pa ON pa.programid = pi.programid AND pa.userid = ue.userid
                 WHERE ue.status = :active
                       AND (pa.archived = 1 OR pa.timestart > :now1 OR (pa.timeend IS NOT NULL AND pa.timeend < :now2))
                       $programselect $userselect
              ORDER BY e.id ASC, ue.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $e) {
            if (!$enrol || $enrol->id != $e->id) {
                $enrol = $DB->get_record('enrol', ['id' => $e->id], '*', MUST_EXIST);
            }
            $plugin->update_user_enrol($enrol, $e->userid, ENROL_USER_SUSPENDED);
            $context = \context_course::instance($enrol->courseid);
            role_unassign_all([
                'contextid' => $context->id,
                'component' => 'enrol_muprog',
                'itemid' => $enrol->id,
                'userid' => $e->userid,
            ]);
        }
        $rs->close();

        // Copy completion date from other evidences if item not completed yet.
        if ($trace) {
            $trace->output('copying evidence completion dates', 1);
        }
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $params['now3'] = $now;
        $sql = "INSERT INTO {tool_muprog_completion} (itemid, allocationid, timecompleted)

                SELECT pi.id AS itemid, pa.id AS allocationid, pe.timecompleted
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_muprog_item} pi ON pi.programid = pa.programid
                  JOIN {tool_muprog_evidence} pe ON pe.userid = pa.userid AND pe.itemid = pi.id
             LEFT JOIN {tool_muprog_completion} pc ON pc.allocationid = pa.id AND pc.itemid = pi.id
                 WHERE pc.id IS NULL
                       AND p.archived = 0 AND pa.archived = 0
                       AND (pa.timestart <= :now1)
                       AND (pa.timeend IS NULL OR pa.timeend > :now2)
                       $programselect $userselect";
        $DB->execute($sql, $params);

        $select = "trainingid IS NOT NULL";
        $params = [];
        if ($programid) {
            $select .= " AND programid = :programid";
            $params['programid'] = $programid;
        }
        if ($DB->record_exists_select('tool_muprog_item', $select, $params)) {
            // Aggregate training progress unless the training framework is archived.
            if ($trace) {
                $trace->output('aggregating training progress', 1);
            }
            $params = [];
            $programselect = '';
            $userselect = '';
            if ($programid) {
                $programselect = "AND pi.programid = :programid";
                $params['programid'] = $programid;
            }
            if ($userid) {
                $userselect = "AND pa.userid = :userid";
                $params['userid'] = $userid;
            }
            $now = time();
            $params['now1'] = $now;
            $params['now2'] = $now;
            $params['now3'] = $now;
            $params['now4'] = $now;

            $sql = "INSERT INTO {tool_muprog_completion} (itemid, allocationid, timecompleted)

                    SELECT pi.id AS itemid, pa.id AS allocationid, (:now3 + pi.completiondelay) AS timecompleted
                      FROM {tool_muprog_allocation} pa
                      JOIN {tool_muprog_program} p ON p.id = pa.programid
                      JOIN {tool_muprog_item} pi ON pi.programid = pa.programid
                      JOIN {tool_mutrain_framework} tfr ON tfr.id = pi.trainingid
                 LEFT JOIN {tool_muprog_completion} pc ON pc.allocationid = pa.id AND pc.itemid = pi.id
                     WHERE pc.id IS NULL
                           AND EXISTS (

                               SELECT SUM(cd.intvalue)
                                 FROM {tool_mutrain_completion} ctc
                                 JOIN {customfield_field} cf ON cf.id = ctc.fieldid
                                 JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = ctc.instanceid
                                 JOIN {tool_mutrain_field} tf ON tf.fieldid = cf.id
                                WHERE tf.frameworkid = tfr.id AND ctc.userid = pa.userid AND cd.intvalue IS NOT NULL
                                      AND (tfr.restrictedcompletion = 0 OR ctc.timecompleted >= pa.timestart)
                               HAVING SUM(cd.intvalue) >= tfr.requiredtraining

                           )
                           AND p.archived = 0 AND pa.archived = 0 AND tfr.archived = 0
                           AND (pa.timestart <= :now1)
                           AND (pa.timeend IS NULL OR pa.timeend > :now2)
                           $programselect $userselect";
            $DB->execute($sql, $params);
        }

        // Copy completion info from course completion to program item completion,
        // do not remove program item completion if completion gets reset in course later.
        if ($trace) {
            $trace->output('copying course completion dates', 1);
        }
        $params = [];
        $programselect = '';
        $userselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $params['now3'] = $now;
        $sql = "INSERT INTO {tool_muprog_completion} (itemid, allocationid, timecompleted)

                SELECT pi.id AS itemid, pa.id AS allocationid, (:now3 + pi.completiondelay) AS timecompleted
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_muprog_item} pi ON pi.programid = pa.programid
                  JOIN {course_completions} cc ON cc.userid = pa.userid AND cc.course = pi.courseid
             LEFT JOIN {tool_muprog_completion} pc ON pc.allocationid = pa.id AND pc.itemid = pi.id
                 WHERE pc.id IS NULL AND cc.reaggregate = 0 AND cc.timecompleted > 0
                       AND p.archived = 0 AND pa.archived = 0
                       AND (pa.timestart <= :now1)
                       AND (pa.timeend IS NULL OR pa.timeend > :now2)
                       $programselect $userselect";
        $DB->execute($sql, $params);

        // Calculate set completions ignoring course items,
        // do max 100 dependencies to prevent infinite loop.
        if ($trace) {
            $trace->output('calculating item completions', 1);
        }
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql = "SELECT 1
                  FROM {tool_muprog_item} pi
                  JOIN {tool_muprog_program} p ON p.id = pi.programid
                  JOIN {tool_muprog_allocation} pa ON pa.programid = p.id
                 WHERE pi.minpoints IS NOT NULL
                       $programselect $userselect";
        $minpointsfound = $DB->record_exists_sql($sql, $params);

        for ($i = 0; $i < 100; $i++) {
            if ($trace) {
                $trace->output('level ' . $i, 2);
            }
            $count = 0;
            $now = time();
            $params['now1'] = $now;
            $params['now2'] = $now;
            $params['now3'] = $now;
            $sql = "SELECT psi.id AS itemid, pa.id AS allocationid, psi.minprerequisites, psi.completiondelay, COUNT(pric.id) AS precount
                      FROM {tool_muprog_item} psi
                      JOIN {tool_muprog_program} p ON p.id = psi.programid
                      JOIN {tool_muprog_allocation} pa ON pa.programid = p.id
                 LEFT JOIN {tool_muprog_completion} psic ON psic.itemid = psi.id AND psic.allocationid = pa.id
                      JOIN {tool_muprog_prerequisite} pr ON pr.itemid = psi.id
                      JOIN {tool_muprog_completion} pric ON pric.itemid = pr.prerequisiteitemid AND pric.allocationid = pa.id AND pric.timecompleted <= :now3
                     WHERE psi.minprerequisites IS NOT NULL AND psic.id IS NULL
                           AND p.archived = 0 AND pa.archived = 0
                           AND (pa.timestart <= :now1)
                           AND (pa.timeend IS NULL OR pa.timeend > :now2)
                           $programselect $userselect
                  GROUP BY psi.id, pa.id, psi.minprerequisites, psi.completiondelay
                    HAVING psi.minprerequisites <= COUNT(pric.id)
                  ORDER BY psi.id ASC, pa.id ASC";
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $completion) {
                // NOTE: this should not return many records because this
                // should be called with userid parameter from event observers.
                $record = new stdClass();
                $record->itemid = $completion->itemid;
                $record->allocationid = $completion->allocationid;
                $record->timecompleted = time() + $completion->completiondelay; // Use real time, we are not in a transaction here.
                $DB->insert_record('tool_muprog_completion', $record);
                $count++;
            }
            $rs->close();

            if ($minpointsfound) {
                $now = time();
                $params['now1'] = $now;
                $params['now2'] = $now;
                $params['now3'] = $now;
                $sql = "SELECT psi.id AS itemid, pa.id AS allocationid, psi.minpoints, psi.completiondelay, SUM(pri.points) AS pointcount
                          FROM {tool_muprog_item} psi
                          JOIN {tool_muprog_program} p ON p.id = psi.programid
                          JOIN {tool_muprog_allocation} pa ON pa.programid = p.id
                     LEFT JOIN {tool_muprog_completion} psic ON psic.itemid = psi.id AND psic.allocationid = pa.id
                          JOIN {tool_muprog_prerequisite} pr ON pr.itemid = psi.id
                          JOIN {tool_muprog_item} pri ON pri.id = pr.prerequisiteitemid
                          JOIN {tool_muprog_completion} pric ON pric.itemid = pr.prerequisiteitemid AND pric.allocationid = pa.id AND pric.timecompleted <= :now3
                         WHERE psi.minpoints IS NOT NULL AND psic.id IS NULL
                               AND p.archived = 0 AND pa.archived = 0
                               AND (pa.timestart <= :now1)
                               AND (pa.timeend IS NULL OR pa.timeend > :now2)
                               $programselect $userselect
                      GROUP BY psi.id, pa.id, psi.minpoints, psi.completiondelay
                        HAVING psi.minpoints <= SUM(pri.points)
                      ORDER BY psi.id ASC, pa.id ASC";
                $rs = $DB->get_recordset_sql($sql, $params);
                foreach ($rs as $completion) {
                    // NOTE: this should not return many records because this
                    // should be called with userid parameter from event observers.
                    $record = new stdClass();
                    $record->itemid = $completion->itemid;
                    $record->allocationid = $completion->allocationid;
                    $record->timecompleted = time() + $completion->completiondelay; // Use real time, we are not in a transaction here.
                    $DB->insert_record('tool_muprog_completion', $record);
                    $count++;
                }
                $rs->close();
            }

            if (!$count) {
                // Stop when nothing found.
                break;
            }
        }

        // Unsuspend enrolments where previous item was completed or there is no previous item specified.
        if ($trace) {
            $trace->output('activating user enrolments', 1);
        }
        $params = ['suspended' => ENROL_USER_SUSPENDED];
        $programselect = '';
        if ($programid) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND ue.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $params['now3'] = $now;
        $sql = "SELECT e.id, pa.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.enrol = 'muprog' AND e.id = ue.enrolid
                  JOIN {tool_muprog_item} pi ON pi.programid = e.customint1 AND pi.courseid = e.courseid
                  JOIN {tool_muprog_allocation} pa ON pa.userid = ue.userid AND pa.programid = pi.programid
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
             LEFT JOIN {tool_muprog_item} previ ON previ.programid = p.id AND previ.id = pi.previtemid
             LEFT JOIN {tool_muprog_completion} previc ON previc.itemid = previ.id AND previc.allocationid = pa.id
                 WHERE ue.status = :suspended
                       AND (pi.previtemid IS NULL OR previc.timecompleted <= :now3)
                       AND p.archived = 0 AND pa.archived = 0
                       AND (pa.timestart <= :now1)
                       AND (pa.timeend IS NULL OR pa.timeend > :now2)
                       $programselect $userselect
              ORDER BY e.id ASC, pa.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $e) {
            if (!$enrol || $enrol->id != $e->id) {
                $enrol = $DB->get_record('enrol', ['id' => $e->id], '*', MUST_EXIST);
            }
            $plugin->update_user_enrol($enrol, $e->userid, ENROL_USER_ACTIVE);
        }
        $rs->close();

        // Remove role for all non-active enrolments.
        if ($trace) {
            $trace->output('unassigning roles', 1);
        }
        $params = ['suspended' => ENROL_USER_SUSPENDED, 'disabled' => ENROL_INSTANCE_DISABLED, 'roleid' => $roleid];
        $programselect = '';
        if ($programid) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND ue.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql = "SELECT ra.roleid, ra.userid, ra.contextid, ra.itemid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.enrol = 'muprog' AND e.id = ue.enrolid
                  JOIN {tool_muprog_item} pi ON pi.programid = e.customint1 AND pi.courseid = e.courseid
             LEFT JOIN {tool_muprog_allocation} pa ON pa.userid = ue.userid AND pa.programid = pi.programid AND pa.archived = 0
             LEFT JOIN {tool_muprog_program} p ON p.id = pa.programid AND p.archived = 0
                  JOIN {context} c ON c.instanceid = e.courseid AND c.contextlevel = 50
                  JOIN {role_assignments} ra ON ra.contextid = c.id AND ra.component = 'enrol_muprog' AND ra.userid = ue.userid AND ra.itemid = e.id
                 WHERE (ue.status = :suspended OR e.status = :disabled OR p.id IS NULL OR pa.id IS NULL
                        OR ra.roleid <> :roleid)
                       $programselect $userselect
              ORDER BY ra.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ra) {
            role_unassign($ra->roleid, $ra->userid, $ra->contextid, 'enrol_muprog', $ra->itemid);
        }
        $rs->close();

        // Add all wanted roles.
        if ($trace) {
            $trace->output('assigning roles', 1);
        }
        $params = ['active' => ENROL_USER_ACTIVE, 'enabled' => ENROL_INSTANCE_ENABLED];
        $programselect = '';
        if ($programid) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND ue.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql = "SELECT ue.userid, c.id AS contextid, e.id AS itemid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.enrol = 'muprog' AND e.id = ue.enrolid
                  JOIN {tool_muprog_item} pi ON pi.programid = e.customint1 AND pi.courseid = e.courseid
                  JOIN {tool_muprog_allocation} pa ON pa.programid = pi.programid AND pa.userid = ue.userid AND pa.archived = 0
                  JOIN {tool_muprog_program} p ON p.id = pa.programid AND p.archived = 0
                  JOIN {context} c ON c.instanceid = e.courseid AND c.contextlevel = 50
             LEFT JOIN {role_assignments} ra ON ra.contextid = c.id AND ra.component = 'enrol_muprog' AND ra.userid = ue.userid AND ra.itemid = e.id
                 WHERE ra.id IS NULL
                       AND ue.status = :active AND e.status = :enabled
                       $programselect $userselect
              ORDER BY e.id ASC, pa.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ra) {
            role_assign($roleid, $ra->userid, $ra->contextid, 'enrol_muprog', $ra->itemid);
        }
        $rs->close();

        // Finally, if top program item is completed, copy the completion time to program allocation,
        // we do this in a loop one by one in order to trigger the allocation_completed event.
        if ($trace) {
            $trace->output('completing programs', 1);
        }
        $params = [];
        $params['now'] = time();
        $programselect = '';
        if ($programid) {
            $programselect = "AND pi.programid = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql = "SELECT p.id, pc.timecompleted, pa.id AS allocationid
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_muprog_item} pi ON pi.programid = pa.programid AND pi.topitem = 1
                  JOIN {tool_muprog_completion} pc ON pc.allocationid = pa.id AND pc.itemid = pi.id AND pc.timecompleted <= :now
                 WHERE pa.timecompleted IS NULL
                       AND p.archived = 0 AND pa.archived = 0
                       $programselect $userselect
              ORDER BY pa.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $p) {
            if (!$program || $program->id != $p->id) {
                $program = $DB->get_record('tool_muprog_program', ['id' => $p->id], '*', MUST_EXIST);
            }
            $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $p->allocationid]);
            $allocation->timecompleted = (string)$p->timecompleted;
            $DB->set_field('tool_muprog_allocation', 'timecompleted', $p->timecompleted, ['id' => $allocation->id]);
            $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid]);
            $user = $DB->get_record('user', ['id' => $allocation->userid]);

            \tool_muprog\event\allocation_completed::create_from_allocation($allocation, $program)->trigger();
            notification\completion::notify_now($user, $program, $source, $allocation);
            calendar::delete_allocation_events($allocation->id);
        }
        $rs->close();

        // Add program group members,
        // the membership is then kept until unenrolment or group disposal.
        if ($trace) {
            $trace->output('adding group members', 1);
        }
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND ue.userid = :userid";
            $params['userid'] = $userid;
        }
        $sql = "SELECT ue.userid, pg.groupid, p.id AS programid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.enrol = 'muprog' AND e.id = ue.enrolid
                  JOIN {tool_muprog_item} pi ON pi.programid = e.customint1 AND pi.courseid = e.courseid
                  JOIN {tool_muprog_program} p ON p.id = pi.programid
                  JOIN {tool_muprog_allocation} pa ON pa.userid = ue.userid AND pa.programid = pi.programid
                  JOIN {tool_muprog_group} pg ON pg.programid = pi.programid AND pg.courseid = pi.courseid
             LEFT JOIN {groups_members} gm ON gm.groupid = pg.groupid AND gm.userid = ue.userid
                 WHERE gm.id IS NULL AND p.archived = 0 AND pa.archived = 0
                       $programselect $userselect
              ORDER BY ue.userid ASC, pg.groupid ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $cm) {
            groups_add_member($cm->groupid, $cm->userid, 'tool_muprog', $cm->programid);
        }
        $rs->close();
    }

    /**
     * Ask sources to fix their allocations.
     *
     * This is expected to be called from cron and when
     * program allocation settings are updated.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @return void
     */
    public static function fix_allocation_sources(?int $programid, ?int $userid): void {
        $sources = self::get_source_classes();
        foreach ($sources as $source) {
            /** @var source\base $source */
            $source::fix_allocations($programid, $userid);
        }
    }

    /**
     * Reset user program progress.
     *
     * @param stdClass $data
     * @return stdClass allocation record
     */
    public static function reset(stdClass $data): stdClass {
        global $DB;

        $record = $DB->get_record('tool_muprog_allocation', ['id' => $data->id], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $record->programid], '*', MUST_EXIST);
        $source = $DB->get_record('tool_muprog_source', ['id' => $record->sourceid]);
        $user = $DB->get_record('user', ['id' => $record->userid, 'deleted' => 0], '*', MUST_EXIST);

        if ($data->resettype != course_reset::RESETTYPE_STANDARD && $data->resettype != course_reset::RESETTYPE_FULL) {
            throw new \coding_exception('invalid reset type');
        }

        if (!empty($data->updateallocation)) {
            $record->timestart = $data->timestart;
            $record->timedue = $data->timedue;
            if (!$record->timedue) {
                $record->timedue = null;
            } else if ($record->timedue <= $record->timestart) {
                throw new \coding_exception('invalid due date');
            }
            $record->timeend = $data->timeend;
            if (!$record->timeend) {
                $record->timeend = null;
            } else if ($record->timeend <= $record->timestart) {
                throw new \coding_exception('invalid end date');
            }
            if ($record->timedue && $record->timeend && $record->timedue > $record->timeend) {
                throw new \coding_exception('invalid due date');
            }
        }

        $trans = $DB->start_delegated_transaction();

        course_reset::purge_enrolments($user, $record->programid);
        if ($data->resettype == course_reset::RESETTYPE_STANDARD) {
            course_reset::purge_standard($user, $record->programid);
        } else {
            course_reset::purge_full($user, $record->programid);
        }
        course_reset::purge_completions($user, $record->programid);

        $select = "userid = :userid AND itemid IN (SELECT id FROM {tool_muprog_item} WHERE programid = :programid)";
        $params = ['programid' => $program->id, 'userid' => $user->id];
        $DB->delete_records_select('tool_muprog_evidence', $select, $params);
        $DB->delete_records('tool_muprog_completion', ['allocationid' => $record->id]);

        $record->timecompleted = null;
        $DB->update_record('tool_muprog_allocation', $record);
        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        self::fix_allocation_sources($allocation->programid, $allocation->userid);
        self::fix_user_enrolments($allocation->programid, $allocation->userid);
        calendar::fix_allocation_events($allocation, $program);

        notification\reset::notify_now($user, $program, $source, $allocation);

        return $allocation;
    }

    /**
     * Manually update item completion time.
     *
     * @param stdClass $data
     * @return void
     */
    public static function update_item_completion(stdClass $data): void {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $data->allocationid], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);
        $item = $DB->get_record('tool_muprog_item', ['id' => $data->itemid, 'programid' => $allocation->programid], '*', MUST_EXIST);
        $completion = $DB->get_record('tool_muprog_completion', ['allocationid' => $allocation->id, 'itemid' => $item->id]);

        if ($data->timecompleted) {
            if ($completion) {
                $DB->set_field('tool_muprog_completion', 'timecompleted', $data->timecompleted, ['id' => $completion->id]);
            } else {
                $record = new stdClass();
                $record->allocationid = $allocation->id;
                $record->itemid = $item->id;
                $record->timecompleted = $data->timecompleted;
                $DB->insert_record('tool_muprog_completion', $record);
            }
        } else {
            if ($completion) {
                $DB->delete_records('tool_muprog_completion', ['id' => $completion->id]);
            }
        }

        $trans->allow_commit();

        self::fix_user_enrolments($allocation->programid, $allocation->userid);
        calendar::fix_allocation_events($allocation, $program);
    }

    /**
     * Manually update item completion evidence.
     *
     * @param stdClass $data
     * @return void
     */
    public static function update_item_evidence(stdClass $data): void {
        global $DB, $USER;

        $trans = $DB->start_delegated_transaction();

        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $data->allocationid], '*', MUST_EXIST);
        $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);
        $item = $DB->get_record('tool_muprog_item', ['id' => $data->itemid, 'programid' => $allocation->programid], '*', MUST_EXIST);
        $completion = $DB->get_record('tool_muprog_completion', ['allocationid' => $allocation->id, 'itemid' => $item->id]);
        $evidence = $DB->get_record('tool_muprog_evidence', ['userid' => $allocation->userid, 'itemid' => $item->id]);

        if ($data->evidencetimecompleted) {
            $evidencejson = util::json_encode(['details' => $data->evidencedetails ?? '']);
            if ($evidence) {
                $evidence->timecompleted = $data->evidencetimecompleted;
                $evidence->evidencejson = $evidencejson;
                $DB->update_record('tool_muprog_evidence', $evidence);
                $evidence = $DB->get_record('tool_muprog_evidence', ['id' => $evidence->id], '*', MUST_EXIST);
            } else {
                $record = new stdClass();
                $record->userid = $allocation->userid;
                $record->itemid = $item->id;
                $record->timecompleted = $data->evidencetimecompleted;
                $record->evidencejson = $evidencejson;
                $record->timecreated = time();
                $record->createdby = $USER->id;
                $record->id = $DB->insert_record('tool_muprog_evidence', $record);
                $evidence = $DB->get_record('tool_muprog_evidence', ['id' => $record->id], '*', MUST_EXIST);
            }
        } else {
            if ($evidence) {
                $DB->delete_records('tool_muprog_evidence', ['id' => $evidence->id]);
                $evidence = false;
            }
        }

        if (!empty($data->itemrecalculate)) {
            if ($evidence) {
                if ($completion) {
                    if ($evidence->timecompleted != $completion->timecompleted) {
                        $DB->set_field(
                            'tool_muprog_completion',
                            'timecompleted',
                            $evidence->timecompleted,
                            ['id' => $completion->id]
                        );
                    }
                } else {
                    $record = new stdClass();
                    $record->allocationid = $allocation->id;
                    $record->itemid = $item->id;
                    $record->timecompleted = $evidence->timecompleted;
                    $record->id = $DB->insert_record('tool_muprog_completion', $record);
                }
            } else {
                if ($completion) {
                    $DB->delete_records('tool_muprog_completion', ['id' => $completion->id]);
                }
            }
        }

        $trans->allow_commit();

        self::fix_user_enrolments($allocation->programid, $allocation->userid);
        calendar::fix_allocation_events($allocation, $program);

        // Recalculate program completions if top item only,
        // note future program completions are ignored here because they are invalid.
        if ($item->topitem && !empty($data->itemrecalculate)) {
            $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $data->allocationid], '*', MUST_EXIST);
            $completion = $DB->get_record('tool_muprog_completion', ['allocationid' => $allocation->id, 'itemid' => $item->id]);
            if ($allocation->timecompleted && !$completion) {
                $allocation->timecompleted = null;
                base::allocation_update($allocation);
            } else if ($completion && $completion->timecompleted <= time()) {
                if ($allocation->timecompleted != $completion->timecompleted) {
                    $allocation->timecompleted = $completion->timecompleted;
                    base::allocation_update($allocation);
                }
            }
        }
    }

    /**
     * Returns completion status as plain text.
     *
     * @param stdClass $program
     * @param stdClass $allocation
     * @return string
     */
    public static function get_completion_status_plain(stdClass $program, stdClass $allocation): string {
        $now = time();

        if ($program->archived || $allocation->archived) {
            if ($allocation->timecompleted) {
                return get_string('programstatus_archivedcompleted', 'tool_muprog');
            } else {
                return get_string('programstatus_archived', 'tool_muprog');
            }
        }

        if ($allocation->timecompleted) {
            return get_string('programstatus_completed', 'tool_muprog');
        } else if ($allocation->timestart > $now) {
            return get_string('programstatus_future', 'tool_muprog');
        } else if ($allocation->timeend && $allocation->timeend < $now) {
            return get_string('programstatus_failed', 'tool_muprog');
        } else if ($allocation->timedue && $allocation->timedue < $now) {
            return get_string('programstatus_overdue', 'tool_muprog');
        } else {
            // We need something different from tags that use 'badge-info'.
            return get_string('programstatus_open', 'tool_muprog');
        }
    }

    /**
     * Returns completion status as fancy HTML.
     *
     * @param stdClass $program
     * @param stdClass $allocation
     * @return string
     */
    public static function get_completion_status_html(stdClass $program, stdClass $allocation): string {
        $result = [];

        $now = time();

        if ($program->archived || $allocation->archived) {
            if ($allocation->timecompleted) {
                $result[] = '<span class="badge bg-success">' . get_string('programstatus_archivedcompleted', 'tool_muprog') . '</span>';
            } else {
                $result[] = '<span class="badge bg-dark">' . get_string('programstatus_archived', 'tool_muprog') . '</span>';
            }
        } else if ($allocation->timecompleted) {
            $result[] = '<div class="badge bg-success">' . get_string('programstatus_completed', 'tool_muprog') . '</div>';
        } else if ($allocation->timestart > $now) {
            $result[] = '<div class="badge bg-light text-dark">' . get_string('programstatus_future', 'tool_muprog') . '</div>';
        } else if ($allocation->timeend && $allocation->timeend < $now) {
            $result[] = '<div class="badge bg-danger">' . get_string('programstatus_failed', 'tool_muprog') . '</div>';
        } else if ($allocation->timedue && $allocation->timedue < $now) {
            $result[] = '<div class="badge bg-warning text-dark">' . get_string('programstatus_overdue', 'tool_muprog') . '</div>';
        } else {
            // We need something different from tags that use 'badge-info'.
            $result[] = '<div class="badge bg-primary">' . get_string('programstatus_open', 'tool_muprog') . '</div>';
        }

        return implode(' ', $result);
    }

    /**
     * To be called after user deletion to make sure there are no user data leftovers.
     *
     * @param int $userid
     * @return void
     */
    public static function deleted_user_cleanup(int $userid): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid]);
        if ($user && !$user->deleted) {
            debugging('Invalid deleted user cleanup request!', DEBUG_DEVELOPER);
            return;
        }

        $allocations = $DB->get_records('tool_muprog_allocation', ['userid' => $userid]);
        foreach ($allocations as $allocation) {
            $DB->delete_records('tool_muprog_completion', ['allocationid' => $allocation->id]);
        }
        $DB->delete_records('tool_muprog_evidence', ['userid' => $userid]);
        $DB->delete_records('tool_muprog_allocation', ['userid' => $userid]);
        $DB->delete_records('tool_muprog_request', ['userid' => $userid]);
    }

    /**
     * Returns list of programs with allocation data that user can see.
     * @param int|null $userid
     * @return array
     */
    public static function get_my_allocations(?int $userid = null): array {
        global $USER, $DB;

        $params = ['userid' => $userid ?? $USER->id];

        $tenantjoin = "";
        if (\tool_muprog\local\util::is_mutenancy_active()) {
            // Having program allocations in different tenant is a BAD thing,
            // so let's just do the same as the catalogue for now.
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $tenantjoin = "JOIN {context} pc ON pc.id = p.contextid AND (pc.tenantid IS NULL OR pc.tenantid = :tenantid)";
                $params['tenantid'] = $tenantid;
            }
        }

        $sql = "SELECT pa.*
                  FROM {tool_muprog_allocation} pa
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  $tenantjoin
                 WHERE pa.userid = :userid AND p.archived = 0 AND pa.archived = 0
              ORDER BY p.fullname ASC";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Called from \tool_uploaduser\process::process_line()
     * right after manual::tool_uploaduser_process().
     *
     * @param stdClass $user
     * @param string $column
     * @param \uu_progress_tracker $upt
     * @return void
     */
    public static function tool_uploaduser_process(stdClass $user, string $column, \uu_progress_tracker $upt): void {
        global $DB;

        if (!preg_match('/^program\d+$/', $column)) {
            return;
        }
        // Offset is 7 to get the number after the word program.
        $number = substr($column, 7);
        if (empty($user->{$column})) {
            return;
        }

        $programid = $user->{$column};
        if (is_number($programid)) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $programid]);
        } else {
            $program = $DB->get_record('tool_muprog_program', ['idnumber' => $programid]);
        }
        if (!$program) {
            // No need to duplicate errors here,
            // the manual allocation should have already complained.
            return;
        }
        $programname = format_string($program->fullname);

        $context = \context::instance_by_id($program->contextid, IGNORE_MISSING);
        if (!$context) {
            $upt->track('enrolments', get_string('userupload_completion_error', 'tool_muprog', $programname), 'error');
            return;
        }
        if (!has_capability('tool/muprog:manageevidence', $context) && !has_capability('tool/muprog:admin', $context)) {
            $upt->track('enrolments', get_string('userupload_completion_error', 'tool_muprog', $programname), 'error');
            return;
        }

        $completionfield = 'pcompletiondate' . $number;
        if (empty($user->{$completionfield})) {
            return;
        }
        $timecompleted = strtotime($user->{$completionfield});
        if ($timecompleted === false) {
            $upt->track('enrolments', get_string('invalidcompletiondate', 'tool_muprog', $programname), 'error');
            return;
        }
        $completionevidence = 'pcompletionevidence' . $number;
        $evidence = $user->{$completionevidence} ?? '';

        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id]);
        if (!$allocation) {
            $upt->track('enrolments', get_string('userupload_completion_error', 'tool_muprog', $programname), 'error');
            return;
        }
        if ($program->archived || $allocation->archived) {
            $upt->track('enrolments', get_string('userupload_completion_error', 'tool_muprog', $programname), 'error');
            return;
        }
        $item = $DB->get_record('tool_muprog_item', ['topitem' => 1, 'programid' => $allocation->programid]);
        if (!$item) {
            $upt->track('enrolments', get_string('userupload_completion_error', 'tool_muprog', $programname), 'error');
            return;
        }

        $data = [
            'itemid' => $item->id,
            'allocationid' => $allocation->id,
            'evidencetimecompleted' => $timecompleted,
            'itemrecalculate' => 1,
        ];
        if (trim($evidence) !== '') {
            $data['evidencedetails'] = clean_text($evidence);
        } else {
            $data['evidencedetails'] = get_string('source_manual_uploadusers', 'tool_muprog');
        }
        self::update_item_evidence((object)$data);
        $upt->track('enrolments', get_string('userupload_completion_updated', 'tool_muprog', $programname), 'info');
    }

    /**
     * Returns preprocessed program evidence upload file contents.
     *
     * NOTE: data.json file is deleted.
     *
     * @param stdClass $data form submission data
     * @param array $filedata decoded data.json file
     * @return array with keys 'updated', 'skipped' and 'errors'
     */
    public static function process_evidence_uploaded_data(stdClass $data, array $filedata): array {
        global $DB, $USER;

        if (
            $data->usermapping !== 'username'
            && $data->usermapping !== 'email'
            && $data->usermapping !== 'idnumber'
        ) {
            // We need to prevent SQL injections in get_record later!
            throw new \coding_exception('Invalid usermapping value');
        }

        $result = [
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $program = $DB->get_record('tool_muprog_program', ['id' => $data->programid], '*', MUST_EXIST);

        if ($data->hasheaders) {
            unset($filedata[0]);
        }

        foreach ($filedata as $i => $row) {
            $userident = $row[$data->usercolumn];
            if (!$userident) {
                $result['errors']++;
                continue;
            }
            $users = $DB->get_records('user', [$data->usermapping => $userident, 'deleted' => 0, 'confirmed' => 1]);
            if (count($users) !== 1) {
                $result['errors']++;
                continue;
            }
            $user = reset($users);
            if (isguestuser($user->id)) {
                $result['errors']++;
                continue;
            }

            if (empty($row[$data->timecompletedcolumn])) {
                // If date is not set, we skip the row.
                $result['skipped']++;
                continue;
            }
            $timecompleted = strtotime($row[$data->timecompletedcolumn]);
            if (!$timecompleted) {
                // Invalid date is error.
                $result['errors']++;
                continue;
            }

            // Program evidence goes into the top item, program completion is updated via re-calculation option.
            $item = $DB->get_record('tool_muprog_item', ['topitem' => 1, 'programid' => $program->id]);
            $allocation = $DB->get_record('tool_muprog_allocation', ['userid' => $user->id, 'programid' => $program->id]);
            if (!$allocation) {
                $result['errors']++;
                continue;
            }
            if ($allocation->archived) {
                $result['skipped']++;
                continue;
            }

            $completiondata = [
                'itemid' => $item->id,
                'allocationid' => $allocation->id,
                'evidencetimecompleted' => $timecompleted,
                'itemrecalculate' => 1,
            ];

            if (isset($data->detailscolumn) && $data->detailscolumn != -1 && trim($row[$data->detailscolumn] ?? '') !== '') {
                $completiondata['evidencedetails'] = $row[$data->detailscolumn];
            } else if (trim($data->details ?? '') !== '') {
                $completiondata['evidencedetails'] = $data->details;
            } else {
                // They should have provided meaningful details, so fallback to whatever we call this.
                $completiondata['evidencedetails'] = get_string('evidenceupload', 'tool_muprog');
            }
            self::update_item_evidence((object)$completiondata);
            $result['updated']++;
        }

        if (!empty($data->csvfile)) {
            $fs = get_file_storage();
            $context = \context_user::instance($USER->id);
            $fs->delete_area_files($context->id, 'user', 'draft', $data->csvfile);
            $fs->delete_area_files($context->id, 'tool_muprog', 'upload', $data->csvfile);
        }

        return $result;
    }
}
