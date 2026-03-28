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

/**
 * Certification certificates are awarded via tool_certificate.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certificate {
    /**
     * Display feature to issue certificates for certification and recertification?
     *
     * @return bool
     */
    public static function is_available(): bool {
        if (!file_exists(__DIR__ . '/../../../certificate/version.php')) {
            return false;
        }
        $version = get_config('tool_certificate', 'version');
        if (!$version || $version < 2023042500) {
            return false;
        }
        return true;
    }

    /**
     * Issue certificate.
     *
     * @param int $periodid
     * @return bool success
     */
    public static function issue(int $periodid): bool {
        global $DB;

        if (!PHPUNIT_TEST && !CLI_SCRIPT) {
            throw new \coding_exception('Certificates cannot be awarded from normal web pages');
        }

        $period = $DB->get_record('tool_mucertify_period', ['id' => $periodid]);
        if (!$period || $period->certificateissueid) {
            return false;
        }

        $lockfactory = \core\lock\lock_config::get_lock_factory('tool_mucertify_certificate_lock');
        $lock = $lockfactory->get_lock('period_' . $period->id, MINSECS);
        if (!$lock) {
            debugging('locktimeout when issuing certificate for period ' . $periodid, DEBUG_DEVELOPER);
            return false;
        }

        $period = $DB->get_record('tool_mucertify_period', ['id' => $periodid]);
        if (!$period || $period->certificateissueid || !$period->timecertified || !$period->timefrom || $period->timerevoked) {
            $lock->release();
            return false;
        }
        $assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $period->certificationid, 'userid' => $period->userid]);
        if (!$assignment) {
            $lock->release();
            return false;
        }
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $period->certificationid]);
        if (!$certification || !$certification->templateid) {
            $lock->release();
            return false;
        }
        $template = $DB->get_record('tool_certificate_templates', ['id' => $certification->templateid]);
        if (!$template) {
            $lock->release();
            return false;
        }
        $user = $DB->get_record('user', ['id' => $assignment->userid, 'deleted' => 0, 'confirmed' => 1]);
        if (!$user) {
            $lock->release();
            return false;
        }

        $template = \tool_certificate\template::instance($template->id, $template);
        $issuedata = [
            'certificationid' => $certification->id,
            'certificationfullname' => $certification->fullname,
            'certificationidnumber' => $certification->idnumber,
            'certificationassignmentid' => $assignment->id,
            'certificationperiodid' => $period->id,
            'certificationtimecertified' => $period->timecertified,
            'certificationtimefrom' => $period->timefrom,
            'certificationtimeuntil' => $period->timeuntil,
            'certificationfirst' => $period->first,
        ];
        $issueid = $template->issue_certificate($user->id, $period->timeuntil, $issuedata, 'tool_mucertify');

        $DB->set_field('tool_mucertify_period', 'certificateissueid', $issueid, ['id' => $period->id]);

        $lock->release();

        return true;
    }

    /**
     * Delete issued certificate for given period.
     *
     * @param int $periodid
     * @return void
     */
    public static function revoke(int $periodid): void {
        global $DB;

        $period = $DB->get_record('tool_mucertify_period', ['id' => $periodid]);
        if (!$period->certificateissueid) {
            return;
        }

        if (self::is_available()) {
            $issue = $DB->get_record('tool_certificate_issues', ['id' => $period->certificateissueid]);
            if ($issue) {
                $template = \tool_certificate\template::instance($issue->templateid);
                $template->revoke_issue($issue->id);
            }
        }

        $DB->set_field('tool_mucertify_period', 'certificateissueid', null, ['id' => $period->id]);
    }

    /**
     * Called after certificate template is deleted.
     *
     * @param \tool_certificate\event\template_deleted $event
     * @return void
     */
    public static function template_deleted(\tool_certificate\event\template_deleted $event): void {
        global $DB;

        // Minor cleanup only, cron will remove any leftovers if templeteid changed in the past.
        $sql = "SELECT cp.id
                  FROM {tool_mucertify_period} cp
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
             LEFT JOIN {tool_certificate_issues} i ON i.id = cp.certificateissueid
                 WHERE c.templateid = :templateid AND i.id IS NULL";
        $params = ['templateid' => $event->objectid];
        $periodids = $DB->get_fieldset_sql($sql, $params);
        foreach ($periodids as $periodid) {
            $DB->set_field('tool_mucertify_period', 'certificateissueid', null, ['id' => $periodid]);
        }

        // Remove the setting value, if they add a new template it will rebuild all historic PDFs.
        $DB->set_field('tool_mucertify_certification', 'templateid', null, ['templateid' => $event->objectid]);
    }

    /**
     * Issues certification certificates.
     *
     * @return void
     */
    public static function cron(): void {
        global $DB;

        if (!self::is_available()) {
            return;
        }

        // Delete revoked, orphaned or invalid certificates.
        $sql = "SELECT i.id, i.templateid, cp.id AS cpid
                  FROM {tool_certificate_issues} i
             LEFT JOIN {tool_mucertify_period} cp ON cp.certificateissueid = i.id
                 WHERE i.component = 'tool_mucertify'
                       AND (cp.id IS NULL OR cp.timerevoked IS NOT NULL)
              ORDER BY i.id ASC";
        $issues = $DB->get_records_sql($sql, []);
        foreach ($issues as $issue) {
            $template = \tool_certificate\template::instance($issue->templateid);
            $template->revoke_issue($issue->id);
            if ($issue->cpid) {
                $DB->set_field('tool_mucertify_period', 'certificateissueid', null, ['id' => $issue->cpid]);
            }
        }
        unset($issues);

        // Remove references to invalid certificates.
        $sql = "SELECT cp.id
                  FROM {tool_mucertify_period} cp
                  JOIN {tool_mucertify_certification} c ON c.id = cp.certificationid
             LEFT JOIN {tool_certificate_issues} i ON i.id = cp.certificateissueid
                 WHERE i.id IS NULL";
        $periodids = $DB->get_fieldset_sql($sql, []);
        foreach ($periodids as $periodid) {
            $DB->set_field('tool_mucertify_period', 'certificateissueid', null, ['id' => $periodid]);
        }
        unset($periodids);

        // Add certificates.
        $params = ['now' => time()];
        $sql = "SELECT cp.id
                  FROM {tool_mucertify_certification} c
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = c.id
                  JOIN {tool_mucertify_period} cp ON cp.certificationid = c.id AND cp.userid = ca.userid
                  JOIN {user} u ON u.id = cp.userid AND u.deleted = 0 and u.confirmed = 1
                  JOIN {tool_certificate_templates} t ON t.id = c.templateid
                 WHERE cp.certificateissueid IS NULL
                       AND c.archived = 0 AND ca.archived = 0
                       AND cp.timecertified IS NOT NULL AND cp.timerevoked IS NULL
              ORDER BY cp.id ASC";
        $periodids = $DB->get_fieldset_sql($sql, $params);
        foreach ($periodids as $periodid) {
            self::issue($periodid);
        }
    }
}
