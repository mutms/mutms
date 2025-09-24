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

namespace tool_mucertify\local;

use stdClass;

/**
 * Certification period helper.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class period {
    /**
     * For first and canrecerfity flags for user certification periods.
     *
     * Also updates assignment caching flags and temporary certification end.
     *
     * @param int $certificationid
     * @param int $userid
     * @return stdClass|null assignment record
     */
    public static function fix_flags(int $certificationid, int $userid): ?stdClass {
        global $DB;

        $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certificationid, 'userid' => $userid]);

        $hascertifiedperiod = $DB->record_exists_select(
            'tool_mucertify_period',
            "certificationid = :certificationid AND userid = :userid AND timecertified IS NOT NULL AND timerevoked IS NULL",
            ['certificationid' => $certificationid, 'userid' => $userid]
        );

        if (!$hascertifiedperiod) {
            if ($assignment) {
                $until = null;
                if ($assignment->timecertifiedtemp) {
                    if ($assignment->timecertifiedtemp >= $assignment->timecreated + DAYSECS) {
                        $from = (string)$assignment->timecreated;
                    } else {
                        $from = (string)($assignment->timecertifiedtemp - DAYSECS);
                    }
                } else {
                    $from = null;
                }
                if ($assignment->timecertifiedfrom !== $from || $assignment->timecertifieduntil !== $until) {
                    $DB->update_record('tool_mucertify_assignment', [
                        'id' => $assignment->id,
                        'timecertifiedfrom' => $from,
                        'timecertifieduntil' => $until,
                    ]);
                    $assignment = $DB->get_record(
                        'tool_mucertify_assignment',
                        ['certificationid' => $certificationid, 'userid' => $userid],
                        '*',
                        MUST_EXIST
                    );
                }
            }
        }

        $periods = $DB->get_records(
            'tool_mucertify_period',
            ['certificationid' => $certificationid, 'userid' => $userid],
            'timewindowstart ASC'
        );

        if (!$periods) {
            if ($assignment) {
                return $assignment;
            } else {
                return null;
            }
        }

        $from = null;
        $until = null;
        $first = false;
        $hasrecertify = false;
        foreach ($periods as $period) {
            if ($period->timecertified && !$period->timerevoked) {
                if (!$from || $period->timefrom < $from) {
                    $from = $period->timefrom;
                }
                if ($period->timeuntil) {
                    if ($period->timeuntil > $until) {
                        $until = $period->timeuntil;
                    }
                } else {
                    $until = (string)\tool_mulib\local\date_util::TIMESTAMP_FOREVER;
                }
            }
            if ($period->recertifiable) {
                $hasrecertify = true;
            }
            if ($first || $period->timerevoked) {
                if ($period->first) {
                    $DB->set_field('tool_mucertify_period', 'first', 0, ['id' => $period->id]);
                }
            } else {
                $first = true;
                if (!$period->first) {
                    $DB->set_field('tool_mucertify_period', 'first', 1, ['id' => $period->id]);
                }
            }
        }

        if ($hasrecertify) {
            // Only move to the flag to the end, do not add it if not present in at least one period.
            $last = false;
            $periods = array_reverse($periods);
            foreach ($periods as $period) {
                if ($last || $period->timerevoked) {
                    if ($period->recertifiable) {
                        $DB->set_field('tool_mucertify_period', 'recertifiable', 0, ['id' => $period->id]);
                    }
                } else {
                    $last = true;
                    if (!$period->recertifiable) {
                        $DB->set_field('tool_mucertify_period', 'recertifiable', 1, ['id' => $period->id]);
                    }
                }
            }
        }

        if (!$assignment) {
            return null;
        }

        $update = [];
        if ($assignment->timecertifiedtemp) {
            if ($until && $assignment->timecertifiedtemp < $until) {
                // Remove temporary certification if it is before normal expiration.
                $update['timecertifiedtemp'] = null;
            } else if ($assignment->timecertifiedtemp && !$from) {
                if ($assignment->timecertifiedtemp >= $assignment->timecreated + DAYSECS) {
                    $from = (string)$assignment->timecreated;
                } else {
                    $from = (string)($assignment->timecertifiedtemp - DAYSECS);
                }
            }
        }
        if ($assignment->timecertifiedfrom !== $from || $assignment->timecertifieduntil !== $until) {
            $update['timecertifiedfrom'] = $from;
            $update['timecertifieduntil'] = $until;
        }

        if ($update) {
            $update['id'] = $assignment->id;
            $DB->update_record('tool_mucertify_assignment', $update);
            $assignment = $DB->get_record(
                'tool_mucertify_assignment',
                ['certificationid' => $certificationid, 'userid' => $userid],
                '*',
                MUST_EXIST
            );
        }
        return $assignment;
    }

    /**
     * Returns default dates for new period.
     *
     * @param stdClass $certification
     * @param int $userid
     * @param array $dateoverrides
     * @return array
     */
    public static function get_default_dates(stdClass $certification, int $userid, array $dateoverrides): array {
        global $DB;

        $now = time();

        $firstperiod = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $userid, 'first' => 1]
        );
        $recertperiod = $DB->get_record(
            'tool_mucertify_period',
            ['certificationid' => $certification->id, 'userid' => $userid, 'recertifiable' => 1]
        );
        $continuation = false;

        if (empty($dateoverrides['timewindowstart'])) {
            $dateoverrides['timewindowstart'] = $now;
            if ($firstperiod && $recertperiod) {
                if ($certification->recertify && $recertperiod->timeuntil) {
                    $dateoverrides['timewindowstart'] = $recertperiod->timeuntil - $certification->recertify;
                    $continuation = true;
                }
            }
        }

        $result = [
            'timewindowstart' => $dateoverrides['timewindowstart'],
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => null,
            'timeuntil' => null,
        ];

        $settings = certification::get_periods_settings($certification);

        if (array_key_exists('timewindowdue', $dateoverrides)) {
            $result['timewindowdue'] = $dateoverrides['timewindowdue'];
        } else {
            if ($continuation) {
                $result['timewindowdue'] = $recertperiod->timeuntil;
            } else {
                if ($settings->due1 !== null) {
                    $result['timewindowdue'] = $result['timewindowstart'] + $settings->due1;
                }
            }
        }

        if (array_key_exists('timewindowend', $dateoverrides)) {
            $result['timewindowend'] = $dateoverrides['timewindowend'];
        } else {
            if ($firstperiod) {
                $windowend = $settings->windowend2;
            } else {
                $windowend = $settings->windowend1;
            }
            if ($windowend['since'] === certification::SINCE_NEVER) {
                $result['timewindowend'] = null;
            } else if ($windowend['since'] === certification::SINCE_WINDOWSTART) {
                $iterval = util::normalise_delay($windowend['delay']);
                $d = new \DateTime('@' . $result['timewindowstart']);
                $d->add(new \DateInterval($iterval));
                $result['timewindowend'] = $d->getTimestamp();
            } else if ($windowend['since'] === certification::SINCE_WINDOWDUE && $result['timewindowdue']) {
                $iterval = util::normalise_delay($windowend['delay']);
                $d = new \DateTime('@' . $result['timewindowdue']);
                $d->add(new \DateInterval($iterval));
                $result['timewindowend'] = $d->getTimestamp();
            }
        }

        if (array_key_exists('timefrom', $dateoverrides)) {
            $result['timefrom'] = $dateoverrides['timefrom'];
        } else {
            if ($firstperiod) {
                $valid = $settings->valid2;
            } else {
                $valid = $settings->valid1;
            }
            if ($valid === certification::SINCE_WINDOWSTART) {
                $result['timefrom'] = $result['timewindowstart'];
            } else if ($valid === certification::SINCE_WINDOWDUE && $result['timewindowdue']) {
                $result['timefrom'] = $result['timewindowdue'];
            } else if ($valid === certification::SINCE_WINDOWEND && $result['timewindowend']) {
                $result['timefrom'] = $result['timewindowend'];
            }
        }

        if (array_key_exists('timeuntil', $dateoverrides)) {
            $result['timeuntil'] = $dateoverrides['timeuntil'];
        } else {
            if ($firstperiod) {
                $expiration = $settings->expiration2;
            } else {
                $expiration = $settings->expiration1;
            }
            if ($expiration['since'] === certification::SINCE_NEVER || $expiration['since'] === certification::SINCE_CERTIFIED) {
                $result['timeuntil'] = null;
            } else if ($expiration['since'] === certification::SINCE_WINDOWSTART) {
                $iterval = util::normalise_delay($expiration['delay']);
                $d = new \DateTime('@' . $result['timewindowstart']);
                $d->add(new \DateInterval($iterval));
                $result['timeuntil'] = $d->getTimestamp();
            } else if ($expiration['since'] === certification::SINCE_WINDOWDUE && $result['timewindowdue']) {
                $iterval = util::normalise_delay($expiration['delay']);
                $d = new \DateTime('@' . $result['timewindowdue']);
                $d->add(new \DateInterval($iterval));
                $result['timeuntil'] = $d->getTimestamp();
            } else if ($expiration['since'] === certification::SINCE_WINDOWEND && $result['timewindowend']) {
                $iterval = util::normalise_delay($expiration['delay']);
                $d = new \DateTime('@' . $result['timewindowend']);
                $d->add(new \DateInterval($iterval));
                $result['timeuntil'] = $d->getTimestamp();
            }
        }

        foreach ($result as $k => $v) {
            if ($v === null) {
                continue;
            }
            $result[$k] = (int)$v;
        }

        return $result;
    }

    /**
     * Add period.
     *
     * @param stdClass $data
     * @return stdClass period record
     */
    public static function add(stdClass $data): stdClass {
        global $DB;

        if (isset($data->assignmentid)) {
            $assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $data->assignmentid], '*', MUST_EXIST);
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);
            $user = $DB->get_record('user', ['id' => $assignment->userid, 'deleted' => 0], '*', MUST_EXIST);
            $userid = $assignment->userid;
        } else {
            if (!property_exists($data, 'certificationid')) {
                throw new \invalid_parameter_exception('either assignmentid or certificationid value is required');
            }
            if (!property_exists($data, 'userid')) {
                throw new \invalid_parameter_exception('either userid and assignmentid values are required');
            }
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $data->certificationid], '*', MUST_EXIST);
            $user = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0], '*', MUST_EXIST);
            $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id]);
            if (!$assignment) {
                // This should not happen with current UI.
                $assignment = null;
            }
            $userid = $user->id;
        }
        if (!property_exists($data, 'programid')) {
            throw new \invalid_parameter_exception('programid value is required');
        }
        if ($data->programid) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $data->programid], '*', MUST_EXIST);
            $programid = $program->id;
            unset($program);
        } else {
            // Special case - might be some historic data with non-existing program.
            $programid = null;
        }

        $record = new stdClass();
        $record->certificationid = $certification->id;
        $record->userid = $userid;
        $record->programid = $programid;

        $datefields = ['timewindowstart', 'timewindowdue', 'timewindowend', 'timecertified', 'timefrom', 'timeuntil', 'timerevoked'];

        foreach ($datefields as $field) {
            if (!property_exists($data, $field)) {
                $record->$field = null;
                continue;
            }
            if ($data->$field > 0) {
                $record->$field = $data->$field;
            } else {
                $record->$field = null;
            }
        }

        $record->first = 0;
        $record->recertifiable = 0;
        if (!isset($record->timerevoked)) {
            if (
                !$DB->record_exists(
                    'tool_mucertify_period',
                    ['certificationid' => $certification->id, 'userid' => $user->id, 'timerevoked' => null]
                )
            ) {
                $record->first = 1;
                $record->recertifiable = 1; // Ignored if recertification disabled in settings, they might enable it later.
            } else if (
                $DB->record_exists(
                    'tool_mucertify_period',
                    ['certificationid' => $certification->id, 'userid' => $user->id, 'recertifiable' => 1]
                )
            ) {
                // Duplicates will be removed when fixing flags.
                $record->recertifiable = 1;
            }
        }

        // Check dates are valid.
        if ($record->timewindowstart <= 0) {
            throw new \invalid_parameter_exception('timewindowstart invalid');
        }
        if ($record->timewindowdue && $record->timewindowdue <= $record->timewindowstart) {
            throw new \invalid_parameter_exception('timewindowdue invalid');
        }
        if ($record->timewindowend && $record->timewindowend <= $record->timewindowstart) {
            throw new \invalid_parameter_exception('timewindowend invalid');
        }
        if ($record->timewindowdue && $record->timewindowend && $record->timewindowend < $record->timewindowdue) {
            throw new \invalid_parameter_exception('timewindowend invalid');
        }
        if ($record->timefrom && $record->timeuntil && $record->timefrom >= $record->timeuntil) {
            throw new \invalid_parameter_exception('timeuntil invalid');
        }
        if ($record->timecertified && !$record->timefrom) {
            throw new \invalid_parameter_exception('timefrom required');
        }

        $evidence = [];
        if (isset($record->timecertified) || isset($record->timerevoked)) {
            if (isset($data->evidencedetails) && trim($data->evidencedetails) !== '') {
                $evidence['details'] = $data->evidencedetails;
            }
        }
        $record->evidencejson = \tool_mucertify\local\util::json_encode((object)$evidence);

        $trans = $DB->start_delegated_transaction();

        $id = $DB->insert_record('tool_mucertify_period', $record);

        $assignment = self::fix_flags($record->certificationid, $record->userid);

        $period = $DB->get_record('tool_mucertify_period', ['id' => $id], '*', MUST_EXIST);

        $trans->allow_commit();

        \tool_mucertify\event\period_created::create_from_period($certification, $assignment, $period)->trigger();

        \tool_muprog\local\source\mucertify::sync_certifications($record->certificationid, $record->userid);

        return $period;
    }

    /**
     * Add first certification period if there is a programid1.
     *
     * NOTE: this is called when new assignment is created
     *
     * @param stdClass $assignment
     * @param array $dateoverrides
     * @return null|stdClass period record
     */
    public static function add_first(stdClass $assignment, array $dateoverrides): ?stdClass {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);

        if ($DB->record_exists('tool_mucertify_period', ['certificationid' => $certification->id, 'userid' => $assignment->userid])) {
            // This should not happen for now, we delete periods when unassigning.
            return null;
        }

        if (!$certification->programid1) {
            // This should not happen.
            return null;
        }

        $now = time();

        $data = new stdClass();
        $data->assignmentid = $assignment->id;
        $data->programid = $certification->programid1;

        if (empty($dateoverrides['timewindowstart'])) {
            $dateoverrides['timewindowstart'] = $now;
        }
        $defaultdates = self::get_default_dates($certification, $assignment->userid, $dateoverrides);
        foreach ($defaultdates as $k => $v) {
            $data->$k = $v;
        }

        // Fix dates if necessary - we cannot ask users now to fix dates.
        if ($data->timewindowdue && $data->timewindowdue <= $data->timewindowstart) {
            $data->timewindowdue = $data->timewindowstart + 1;
        }
        if ($data->timewindowend && $data->timewindowend <= $data->timewindowstart) {
            $data->timewindowend = $data->timewindowstart + 1;
        }
        if ($data->timewindowdue && $data->timewindowend && $data->timewindowend < $data->timewindowdue) {
            $data->timewindowdue = $data->timewindowend;
        }
        if ($data->timefrom && $data->timeuntil && $data->timefrom > $data->timeuntil) {
            $data->timeuntil = $data->timefrom + 1;
        }

        if (isset($dateoverrides['timecertified'])) {
            $data->timecertified = $dateoverrides['timecertified'];
            if (isset($dateoverrides['evidencedetails'])) {
                $data->evidencedetails = $dateoverrides['evidencedetails'];
            }
        }

        return self::add($data);
    }

    /**
     * Override period dates.
     *
     * @param stdClass $data
     * @return stdClass
     */
    public static function override_dates(stdClass $data): stdClass {
        global $DB;

        $record = $DB->get_record('tool_mucertify_period', ['id' => $data->id], '*', MUST_EXIST);
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $record->certificationid], '*', MUST_EXIST);

        $oldrecord = clone($record);
        $datefields = ['timewindowstart', 'timewindowdue', 'timewindowend', 'timecertified', 'timefrom', 'timeuntil', 'timerevoked'];

        foreach ($datefields as $field) {
            if (!property_exists($data, $field)) {
                continue;
            }
            if ($data->$field > 0) {
                $record->$field = $data->$field;
            } else {
                $record->$field = null;
            }
        }

        // Check dates are valid.
        if ($record->timewindowstart <= 0) {
            throw new \invalid_parameter_exception('timewindowstart invalid');
        }
        if ($record->timewindowdue && $record->timewindowdue <= $record->timewindowstart) {
            throw new \invalid_parameter_exception('timewindowdue invalid');
        }
        if ($record->timewindowend && $record->timewindowend <= $record->timewindowstart) {
            throw new \invalid_parameter_exception('timewindowend invalid');
        }
        if ($record->timewindowdue && $record->timewindowend && $record->timewindowend < $record->timewindowdue) {
            throw new \invalid_parameter_exception('timewindowend invalid');
        }
        if ($record->timefrom && $record->timeuntil && $record->timefrom > $record->timeuntil) {
            throw new \invalid_parameter_exception('timeuntil invalid');
        }
        if ($record->timecertified && !$record->timefrom) {
            throw new \invalid_parameter_exception('timefrom required');
        }

        if (!isset($record->timecertified) && !isset($record->timerevoked)) {
            $evidence = [];
        } else {
            $evidence = (array)json_decode($record->evidencejson);
            if (property_exists($data, 'evidencedetails')) {
                if ($data->evidencedetails === null || trim($data->evidencedetails) === '') {
                    unset($evidence['details']);
                } else {
                    $evidence['details'] = $data->evidencedetails;
                }
            }
        }
        $record->evidencejson = \tool_mucertify\local\util::json_encode((object)$evidence);

        $trans = $DB->start_delegated_transaction();

        $DB->update_record('tool_mucertify_period', $record);
        $assignment = self::fix_flags($record->certificationid, $record->userid);

        if (!$oldrecord->timerevoked && $record->timerevoked && $record->certificateissueid) {
            certificate::revoke($record->id);
        }

        $period = $DB->get_record('tool_mucertify_period', ['id' => $record->id], '*', MUST_EXIST);

        $trans->allow_commit();

        \tool_mucertify\event\period_updated::create_from_period($certification, $assignment, $period)->trigger();

        \tool_muprog\local\source\mucertify::sync_certifications($record->certificationid, $record->userid);

        return $period;
    }

    /**
     * Update recertification flag.
     *
     * NOTE: this does not trigger assignment updated event.
     *
     * @param stdClass $assignment
     * @param bool $stoprecertify
     * @return stdClass assignment record
     */
    public static function update_recertifiable(stdClass $assignment, bool $stoprecertify): stdClass {
        global $DB;

        if ($stoprecertify) {
            $DB->set_field(
                'tool_mucertify_period',
                'recertifiable',
                0,
                ['certificationid' => $assignment->certificationid, 'userid' => $assignment->userid]
            );
        } else {
            $DB->set_field(
                'tool_mucertify_period',
                'recertifiable',
                1,
                ['certificationid' => $assignment->certificationid, 'userid' => $assignment->userid, 'timerevoked' => null]
            );
        }
        return self::fix_flags($assignment->certificationid, $assignment->userid);
    }

    /**
     * Delete period.
     *
     * @param int $periodid
     * @return void
     */
    public static function delete(int $periodid): void {
        global $DB;

        $record = $DB->get_record('tool_mucertify_period', ['id' => $periodid]);
        if (!$record) {
            return;
        }
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $record->certificationid], '*', MUST_EXIST);

        $trans = $DB->start_delegated_transaction();

        if ($record->certificateissueid) {
            certificate::revoke($periodid);
        }

        \tool_mucertify\local\notification_manager::delete_period_notifications($record);

        $DB->delete_records('tool_mucertify_period', ['id' => $record->id]);
        $assignment = self::fix_flags($record->certificationid, $record->userid);

        $trans->allow_commit();

        \tool_mucertify\event\period_deleted::create_from_period($certification, $assignment, $record)->trigger();

        \tool_muprog\local\source\mucertify::sync_certifications($record->certificationid, $record->userid);
    }

    /**
     * Called from event observer and right after new period creation.
     *
     * NOTE: make sure completion is not in the future when calling this
     *
     * @param stdClass $program
     * @param stdClass $allocation
     * @return void
     */
    public static function allocation_completed(stdClass $program, stdClass $allocation): void {
        global $DB;

        if ($program->id != $allocation->programid) {
            throw new \coding_exception('program mismatch');
        }

        $now = time();
        $sql = "SELECT p.*
                  FROM {tool_mucertify_period} p
                  JOIN {tool_mucertify_assignment} a ON a.userid = p.userid AND a.certificationid = p.certificationid
                  JOIN {tool_mucertify_certification} c ON c.id = p.certificationid
                  JOIN {user} u ON u.id = p.userid
                 WHERE a.archived = 0 AND c.archived = 0 AND u.deleted = 0
                       AND p.timecertified IS NULL AND p.timerevoked IS NULL
                       AND p.timewindowstart <= $now AND (p.timewindowend IS NULL OR p.timewindowend > $now)
                       AND p.userid = :userid AND p.programid = :programid";
        $params = [
            'userid' => $allocation->userid,
            'programid' => $program->id,
        ];
        $period = $DB->get_record_sql($sql, $params);

        if (!$period) {
            return;
        }

        $certification = $DB->get_record(
            'tool_mucertify_certification',
            ['id' => $period->certificationid],
            '*',
            MUST_EXIST
        );
        $assignment = $DB->get_record(
            'tool_mucertify_assignment',
            ['certificationid' => $period->certificationid, 'userid' => $period->userid],
            '*',
            MUST_EXIST
        );

        $settings = certification::get_periods_settings($certification);

        $period->timecertified = $now;
        if ($period->first) {
            $valid = $settings->valid1;
            $expiration = $settings->expiration1;
        } else {
            $valid = $settings->valid2;
            $expiration = $settings->expiration2;
        }
        if ($period->timefrom === null) {
            if ($valid === certification::SINCE_CERTIFIED) {
                $period->timefrom = $period->timecertified;
            } else if ($valid === certification::SINCE_WINDOWSTART) {
                $period->timefrom = $period->timewindowstart;
            } else if ($valid === certification::SINCE_WINDOWDUE && $period->timewindowdue) {
                $period->timefrom = $period->timewindowdue;
            } else if ($valid === certification::SINCE_WINDOWEND && $period->timewindowend) {
                $period->timefrom = $period->timewindowend;
            }
        }
        if (!$period->timefrom) {
            // Value is required.
            $period->timefrom = $period->timecertified;
        }
        if ($period->timeuntil === null) {
            if ($expiration['since'] === certification::SINCE_NEVER) {
                $period->timeuntil = null;
            } else if ($expiration['since'] === certification::SINCE_CERTIFIED) {
                $iterval = util::normalise_delay($expiration['delay']);
                $d = new \DateTime('@' . $period->timecertified);
                $d->add(new \DateInterval($iterval));
                $period->timeuntil = $d->getTimestamp();
            } else if ($expiration['since'] === certification::SINCE_WINDOWSTART) {
                $iterval = util::normalise_delay($expiration['delay']);
                $d = new \DateTime('@' . $period->timewindowstart);
                $d->add(new \DateInterval($iterval));
                $period->timeuntil = $d->getTimestamp();
            } else if ($expiration['since'] === certification::SINCE_WINDOWDUE && $period->timewindowdue) {
                $iterval = util::normalise_delay($expiration['delay']);
                $d = new \DateTime('@' . $period->timewindowdue);
                $d->add(new \DateInterval($iterval));
                $period->timeuntil = $d->getTimestamp();
            } else if ($expiration['since'] === certification::SINCE_WINDOWEND && $period->timewindowend) {
                $iterval = util::normalise_delay($expiration['delay']);
                $d = new \DateTime('@' . $period->timewindowend);
                $d->add(new \DateInterval($iterval));
                $period->timeuntil = $d->getTimestamp();
            }
        }
        if ($period->timeuntil && $period->timeuntil <= $period->timefrom) {
            // Until value cannot be invalid.
            $period->timeuntil = $period->timefrom + 1;
        }

        $DB->update_record('tool_mucertify_period', $period);

        $assignment = self::fix_flags($assignment->certificationid, $assignment->userid);
        $period = $DB->get_record('tool_mucertify_period', ['id' => $period->id], '*', MUST_EXIST);

        \tool_mucertify\event\period_certified::create_from_period($certification, $assignment, $period)->trigger();

        if (certificate::is_available() && $certification->templateid) {
            // Make sure the certificate is generated asap.
            $asynctask = new \tool_mucertify\task\trigger_certificate();
            $asynctask->set_custom_data('');
            $asynctask->set_userid(get_admin()->id);
            \core\task\manager::queue_adhoc_task($asynctask, true);
        }
    }

    /**
     * Returns period status as fancy HTML.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @return string
     */
    public static function get_status_html(stdClass $certification, ?stdClass $assignment, stdClass $period): string {
        $now = time();

        if (!$assignment || $certification->archived || $assignment->archived) {
            return '<span class="badge bg-dark">' . get_string('periodstatus_archived', 'tool_mucertify') . '</span>';
        }

        if ($period->timerevoked) {
            return '<span class="badge bg-danger">' . get_string('periodstatus_revoked', 'tool_mucertify') . '</span>';
        }

        if ($period->timecertified) {
            if (!$period->timeuntil || $period->timeuntil > $now) {
                return '<span class="badge bg-success">' . get_string('periodstatus_certified', 'tool_mucertify') . '</span>';
            } else {
                return '<span class="badge bg-light text-dark">' . get_string('periodstatus_expired', 'tool_mucertify') . '</span>';
            }
        }

        if ($period->timewindowend && $period->timewindowend < $now) {
            return '<span class="badge bg-danger">' . get_string('periodstatus_failed', 'tool_mucertify') . '</span>';
        }

        if ($period->timewindowdue && $period->timewindowdue < $now) {
            return '<span class="badge bg-danger">' . get_string('periodstatus_overdue', 'tool_mucertify') . '</span>';
        }

        if ($period->timewindowstart > $now) {
            return '<span class="badge bg-light text-dark">' . get_string('periodstatus_future', 'tool_mucertify') . '</span>';
        }

        return '<span class="badge bg-warning text-dark">' . get_string('periodstatus_pending', 'tool_mucertify') . '</span>';
    }

    /**
     * Window start html.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @param bool $short
     * @return string
     */
    public static function get_windowstart_html(stdClass $certification, ?stdClass $assignment, stdClass $period, bool $short = false): string {
        if ($short) {
            $format = get_string('strftimedatetimeshort');
        } else {
            $format = '';
        }
        // Must be always set!
        return userdate($period->timewindowstart, $format);
    }

    /**
     * Window due html.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @param bool $short
     * @return string
     */
    public static function get_windowdue_html(stdClass $certification, ?stdClass $assignment, stdClass $period, bool $short = false): string {
        if (!$period->timewindowdue) {
            return get_string('notset', 'tool_mucertify');
        }
        if ($short) {
            $format = get_string('strftimedatetimeshort');
        } else {
            $format = '';
        }
        return userdate($period->timewindowdue, $format);
    }

    /**
     * Window end html.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @param bool $short
     * @return string
     */
    public static function get_windowend_html(stdClass $certification, ?stdClass $assignment, stdClass $period, bool $short = false): string {
        if (!$period->timewindowend) {
            return get_string('notset', 'tool_mucertify');
        }
        if ($short) {
            $format = get_string('strftimedatetimeshort');
        } else {
            $format = '';
        }
        return userdate($period->timewindowend, $format);
    }

    /**
     * From html.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @param bool $short
     * @return string
     */
    public static function get_from_html(stdClass $certification, ?stdClass $assignment, stdClass $period, bool $short = false): string {
        if ($short) {
            $format = get_string('strftimedatetimeshort');
        } else {
            $format = '';
        }
        if ($period->timefrom) {
            return userdate($period->timefrom, $format);
        }
        if ($period->timecertified) {
            // This should not happen.
            return get_string('notset', 'tool_mucertify');
        }

        $settings = certification::get_periods_settings($certification);
        if ($period->first) {
            $valid = $settings->valid1;
        } else {
            $valid = $settings->valid2;
        }
        $options = certification::get_valid_options();
        return $options[$valid];
    }

    /**
     * Until html.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @param bool $short
     * @return string
     */
    public static function get_until_html(stdClass $certification, ?stdClass $assignment, stdClass $period, bool $short = false): string {
        if ($short) {
            $format = get_string('strftimedatetimeshort');
        } else {
            $format = '';
        }
        if ($period->timeuntil) {
            return userdate($period->timeuntil, $format);
        }
        if ($period->timecertified) {
            return get_string('noexpiration', 'tool_mucertify');
        }

        $settings = certification::get_periods_settings($certification);
        if ($period->first) {
            $since = $settings->expiration1['since'];
            $delay = $settings->expiration1['delay'];
        } else {
            $since = $settings->expiration2['since'];
            $delay = $settings->expiration2['delay'];
        }

        if ($since === certification::SINCE_NEVER) {
            return get_string('never', 'tool_mucertify');
        }
        $options = certification::get_expiration_options();
        $a = new stdClass();
        $a->delay = util::format_interval($delay);
        $a->after = $options[$since];
        return get_string('delayafter', 'tool_mucertify', $a);
    }

    /**
     * Returns period status as fancy HTML.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @param bool $short
     * @return string
     */
    public static function get_recertify_html(stdClass $certification, ?stdClass $assignment, stdClass $period, bool $short = false): string {
        if (!$certification->recertify || !$period->recertifiable || $certification->archived || !$assignment || $assignment->archived) {
            return get_string('no');
        }
        if ($period->timeuntil) {
            if ($short) {
                $format = get_string('strftimedatetimeshort');
            } else {
                $format = '';
            }
            return userdate($period->timeuntil - $certification->recertify, $format);
        } else {
            return get_string('recertifyifexpired', 'tool_mucertify');
        }
    }

    /**
     * Check if new recertification periods should be created.
     *
     * NOTE: this is called from cron and user certification assignment page.
     *
     * @param int|null $certificationid
     * @param int|null $userid
     * @return void
     */
    public static function process_recertifications(?int $certificationid, ?int $userid): void {
        global $DB;

        $params = [];
        $params['now'] = time();
        $params['cutoff'] = time() - DAYSECS * 90; // Do not create recertifications for old periods.

        if ($certificationid) {
            $params['certificationid'] = $certificationid;
            $certificationselect = "AND cp.certificationid = :certificationid";
        } else {
            $certificationselect = "";
        }
        if ($userid) {
            $params['userid'] = $userid;
            $userselect = "AND cp.userid = :userid";
        } else {
            $userselect = "";
        }

        $sql = "SELECT cp.id
                  FROM {tool_mucertify_period} cp
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid AND c.archived = 0
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = c.id AND ca.userid = cp.userid AND ca.archived = 0
                  JOIN {user} u ON u.id = cp.userid AND u.deleted = 0
                  JOIN {tool_muprog_program} p ON p.id = c.programid2 AND p.archived = 0
                 WHERE cp.timerevoked IS NULL AND cp.recertifiable = 1 AND cp.timecertified IS NOT NULL
                       AND cp.timeuntil - c.recertify < :now
                       AND cp.timeuntil > :cutoff
                       $certificationselect $userselect
              ORDER BY cp.id ASC";
        $periods = $DB->get_records_sql($sql, $params);
        foreach ($periods as $period) {
            $period = $DB->get_record('tool_mucertify_period', ['id' => $period->id]);
            if (!$period || isset($period->timerevoked) || !$period->recertifiable || !$period->timecertified || !$period->timeuntil) {
                continue;
            }
            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $period->certificationid]);
            if (!$certification || !isset($certification->recertify)) {
                continue;
            }
            $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $period->userid]);
            if (!$assignment || $assignment->archived) {
                continue;
            }
            $periodsettings = certification::get_periods_settings($certification);
            $dates = [
                'timewindowstart' => $period->timeuntil - $certification->recertify,
                'timewindowdue' => $period->timeuntil,
            ]; // Do not rely on guessing in get_default_dates()...
            if ($period->timewindowstart >= $dates['timewindowstart']) {
                // Wrong settings for recertification!!!
                $DB->set_field('tool_mucertify_period', 'recertifiable', 0, ['id' => $period->id]);
                continue;
            }
            $dates = self::get_default_dates($certification, $period->userid, $dates);
            $dates['certificationid'] = $certification->id;
            $dates['userid'] = $period->userid;
            $dates['programid'] = $certification->programid2;

            $trans = $DB->start_delegated_transaction();
            self::add((object)$dates);
            if (isset($periodsettings->grace2) && $periodsettings->grace2 > 0) {
                $graceuntil = $period->timeuntil + $periodsettings->grace2;
                if ($graceuntil > time() && $graceuntil > (int)$assignment->timecertifiedtemp) {
                    $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $period->userid]);
                    if ($assignment && $graceuntil > $assignment->timecertifieduntil) {
                        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $graceuntil, ['id' => $assignment->id]);
                    }
                }
            }
            $trans->allow_commit();
        }
    }

    /**
     * Process certification history upload.
     *
     * @param stdClass $data
     * @param array $filedata
     * @return array processing stats
     */
    public static function process_history_upload(stdClass $data, array $filedata): array {
        global $DB;

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $data->certificationid, 'archived' => 0], '*', MUST_EXIST);
        $source = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification->id, 'type' => 'manual']);

        if (!in_array($data->usermapping, ['username', 'idnumber', 'email'], true)) {
            throw new \invalid_parameter_exception('invalid usermapping');
        }
        $userfield = $data->usermapping;
        $evidencedefault = $data->evidencedefault ?? '';

        $assign = $data->assign || 0;
        $skipassigned = $data->skipassigned || 0;

        if ($data->hasheaders) {
            unset($filedata[0]);
        }

        $result = [
            'assigned' => 0,
            'periods' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
        foreach ($filedata as $row) {
            $users = $DB->get_records('user', [$userfield => $row[$data->usercolumn], 'deleted' => 0, 'confirmed' => 1]);
            if (count($users) !== 1) {
                $result['errors']++;
                continue;
            }
            $user = reset($users);
            if (isguestuser($user)) {
                $result['errors']++;
                continue;
            }
            $timefrom = $row[$data->timefromcolumn];
            $timeuntil = $row[$data->timeuntilcolumn];
            $timecertified = $row[$data->timecertifiedcolumn];
            if (!$timefrom || !$timeuntil || !$timecertified) {
                $result['errors']++;
                continue;
            }
            $timefrom = strtotime($timefrom);
            $timeuntil = strtotime($timeuntil);
            $timecertified = strtotime($timecertified);
            if ($timefrom > time() || $timecertified > time()) {
                $result['errors']++;
                continue;
            }
            if (!$timefrom || !$timeuntil || !$timecertified) {
                $result['errors']++;
                continue;
            }
            if ($timefrom >= $timeuntil || $timeuntil <= $timecertified) {
                $result['errors']++;
                continue;
            }

            if (
                $DB->record_exists('tool_mucertify_period', [
                'certificationid' => $certification->id, 'userid' => $user->id, 'timerevoked' => null,
                'timefrom' => $timefrom, 'timeuntil' => $timeuntil,
                ])
            ) {
                // Ignore duplicates.
                $result['skipped']++;
                continue;
            }

            $preventrecertification = false;
            $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id]);
            if ($assignment) {
                if ($assign && $skipassigned) {
                    $result['skipped']++;
                    continue;
                }
            } else {
                if (!$assign) {
                    $result['skipped']++;
                    continue;
                }
                if (!$source) {
                    $result['skipped']++;
                    continue;
                }
                \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user->id], ['noperiod' => 1]);
                $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id]);
                if (!$assignment) {
                    $result['skipped']++;
                    continue;
                }
                $result['assigned']++;
                // New periods are created as non-recertifiable.
                $preventrecertification = true;
            }
            $evidence = '';
            if ($data->evidencecolumn !== '' && isset($row[$data->evidencecolumn])) {
                $evidence = $row[$data->evidencecolumn];
            }
            if (trim($evidence) === '') {
                $evidence = $evidencedefault;
            }

            $period = self::add((object)[
                'certificationid' => $certification->id,
                'userid' => $user->id,
                'programid' => null,
                'timecertified' => $timecertified,
                'timewindowstart' => $timefrom,
                'timefrom' => $timefrom,
                'timeuntil' => $timeuntil,
                'evidencedetails' => $evidence,
            ]);
            $result['periods']++;
            if ($preventrecertification) {
                self::update_recertifiable($assignment, true);
            }
        }

        return $result;
    }
}
