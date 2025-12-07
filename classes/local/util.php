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
 * Utility class for certifications.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class util {
    /**
     * Cache existence of programs.
     */
    public static function fix_mucertify_active(): void {
        global $DB;

        $active = (int)$DB->record_exists('tool_mucertify_certification', ['archived' => 0]);
        set_config('active', $active, 'tool_mucertify');
    }

    /**
     * Are any programs present?
     *
     * @return bool
     */
    public static function is_mucertify_active(): bool {
        return (bool)get_config('tool_mucertify', 'active');
    }

    /**
     * Encode JSON date in a consistent way.
     *
     * @param mixed $data
     * @return string
     */
    public static function json_encode($data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Parse form data for delay settings.
     *
     * @param string $name
     * @param stdClass $data
     */
    public static function get_submitted_delay(string $name, stdClass $data): string {
        $type = $data->{$name}['timeunit'];
        $value = (int)$data->{$name}['number'];

        if ($value < 0) {
            throw new \coding_exception('Invalid delay value');
        }
        if ($type === 'years') {
            return 'P' . $value . 'Y';
        } else if ($type === 'months') {
            return 'P' . $value . 'M';
        } else if ($type === 'days') {
            return 'P' . $value . 'D';
        } else if ($type === 'hours') {
            return 'PT' . $value . 'H';
        }
        throw new \coding_exception('Invalid delay type');
    }

    /**
     * Prepare current data for delay settings.
     *
     * @param array $value interval string
     * @param string $defaultunit
     * @return array
     */
    public static function get_delay_form_value(array $value, string $defaultunit): array {
        $since = $value['since'];
        $delay = $value['delay'];

        if ($delay === null || $delay === '') {
            return ['since' => $since, 'timeunit' => $defaultunit, 'number' => null];
        }
        if (preg_match('/^P(\d+)Y$/D', $delay, $matches)) {
            return ['since' => $since, 'timeunit' => 'years', 'number' => $matches[1]];
        }
        if (preg_match('/^P(\d+)M$/D', $delay, $matches)) {
            return ['since' => $since, 'timeunit' => 'months', 'number' => $matches[1]];
        }
        if (preg_match('/^P(\d+)D$/D', $delay, $matches)) {
            return ['since' => $since, 'timeunit' => 'days', 'number' => $matches[1]];
        }
        if (preg_match('/^PT(\d+)H$/D', $delay, $matches)) {
            return ['since' => $since, 'timeunit' => 'hours', 'number' => $matches[1]];
        }

        debugging('Unsupported delay format: ' . var_export($delay, true), DEBUG_DEVELOPER);
        return ['since' => $since, 'timeunit' => $defaultunit, 'number' => null];
    }

    /**
     * Normalise delays used in allocation settings.
     *
     * NOTE: for now only simple P1Y, P22M, P22D and PT22H formats are supported,
     *       support for more options may be added later.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function normalise_delay(?string $string): ?string {
        if (trim($string ?? '') === '') {
            return null;
        }

        if (preg_match('/^P\d+Y$/D', $string)) {
            if ($string === 'P0Y') {
                return null;
            }
            return $string;
        }
        if (preg_match('/^P\d+M$/D', $string)) {
            if ($string === 'P0M') {
                return null;
            }
            return $string;
        }
        if (preg_match('/^P\d+D$/D', $string)) {
            if ($string === 'P0D') {
                return null;
            }
            return $string;
        }
        if (preg_match('/^PT\d+H$/D', $string)) {
            if ($string === 'PT0H') {
                return null;
            }
            return $string;
        }

        debugging('Unsupported delay format: ' . $string, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Format delay that was stored in format of PHP DateInterval
     * to human-readable form.
     *
     * @param string|null $string
     * @return string
     */
    public static function format_interval(?string $string): string {
        if (!$string) {
            return get_string('notset', 'tool_mucertify');
        }

        $interval = new \DateInterval($string);

        $result = [];
        if ($interval->y) {
            if ($interval->y == 1) {
                $result[] = get_string('numyear', 'core', $interval->y);
            } else {
                $result[] = get_string('numyears', 'core', $interval->y);
            }
        }
        if ($interval->m) {
            if ($interval->m == 1) {
                $result[] = get_string('nummonth', 'core', $interval->m);
            } else {
                $result[] = get_string('nummonths', 'core', $interval->m);
            }
        }
        if ($interval->d) {
            if ($interval->d == 1) {
                $result[] = get_string('numday', 'core', $interval->d);
            } else {
                $result[] = get_string('numdays', 'core', $interval->d);
            }
        }
        if ($interval->h) {
            $result[] = get_string('numhours', 'core', $interval->h);
        }
        if ($interval->i) {
            $result[] = get_string('numminutes', 'core', $interval->i);
        }
        if ($interval->s) {
            $result[] = get_string('numseconds', 'core', $interval->s);
        }

        if ($result) {
            return implode(', ', $result);
        } else {
            return '';
        }
    }

    /**
     * Format duration of interval specified using seconds value.
     *
     * @param int|null $duration seconds
     * @return string
     */
    public static function format_duration(?int $duration): string {
        if ($duration < 0) {
            return get_string('error');
        }
        if (!$duration) {
            return get_string('notset', 'tool_mucertify');
        }
        $days = intval($duration / DAYSECS);
        $duration = $duration - $days * DAYSECS;
        $hours = intval($duration / HOURSECS);
        $duration = $duration - $hours * HOURSECS;
        $minutes = intval($duration / MINSECS);
        $seconds = $duration - $minutes * MINSECS;

        $interval = 'P';
        if ($days) {
            $interval .= $days . 'D';
        }
        if ($hours || $minutes || $seconds) {
            $interval .= 'T';
            if ($hours) {
                $interval .= $hours . 'H';
            }
            if ($minutes) {
                $interval .= $minutes . 'M';
            }
            if ($seconds) {
                $interval .= $seconds . 'S';
            }
        }

        return self::format_interval($interval);
    }

    /**
     * Convert SELECT query to format suitable for $DB->count_records_sql().
     *
     * @param string $sql
     * @return string
     */
    public static function convert_to_count_sql(string $sql): string {
        $count = null;
        $sql = preg_replace('/^\s*SELECT.*FROM/Uis', "SELECT COUNT('x') FROM", $sql, 1, $count);
        if ($count !== 1) {
            debugging('Cannot convert SELECT query to count compatible form', DEBUG_DEVELOPER);
        }
        // Subqueries should not have ORDER BYs, so this should be safe,
        // worst case there will be a fatal error caused by cutting the query short.
        $sql = preg_replace('/\s*ORDER BY.*$/is', '', $sql);
        return $sql;
    }

    /**
     * Stores csv file contents as normalised JSON file.
     *
     * NOTE: uploaded file is deleted and instead a new data.json file is stored.
     *
     * @param int $draftid
     * @param array $filedata
     * @return void
     */
    public static function store_uploaded_data(int $draftid, array $filedata): void {
        global $USER;

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);

        $fs->delete_area_files($context->id, 'tool_mucertify', 'upload', $draftid);

        $content = json_encode($filedata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $record = [
            'contextid' => $context->id,
            'component' => 'tool_mucertify',
            'filearea' => 'upload',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'data.json',
        ];

        $fs->create_file_from_string($record, $content);
    }

    /**
     * Returns preprocessed data.json user assignment file contents.
     *
     * @param int $draftid
     * @return array|null
     */
    public static function get_uploaded_data(int $draftid): ?array {
        global $USER;

        if (!$draftid) {
            return null;
        }

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);

        $file = $fs->get_file($context->id, 'tool_mucertify', 'upload', $draftid, '/', 'data.json');
        if (!$file) {
            return null;
        }
        $data = json_decode($file->get_content(), true);
        if (!is_array($data)) {
            return null;
        }
        $data = fix_utf8($data);
        return $data;
    }

    /**
     * Deletes old orphaned upload related data.
     *
     * @return void
     */
    public static function cleanup_uploaded_data(): void {
        global $DB;

        $fs = get_file_storage();
        $sql = "SELECT contextid, itemid
                  FROM {files}
                 WHERE component = 'tool_mucertify' AND filearea = 'upload' AND filepath = '/' AND filename = '.'
                       AND timecreated < :old";
        $rs = $DB->get_recordset_sql($sql, ['old' => time() - 60 * 60 * 24 * 2]);
        foreach ($rs as $dir) {
            $fs->delete_area_files($dir->contextid, 'tool_mucertify', 'upload', $dir->itemid);
        }
        $rs->close();
    }
}
