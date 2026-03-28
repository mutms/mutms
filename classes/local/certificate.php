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

namespace tool_muprog\local;

/**
 * Program certificate awarded via tool_certificate.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certificate {
    /**
     * Display feature to issue certificates for program completion?
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
     * Enable or update issuing of certificates for program completion.
     *
     * @param array $data
     * @return \stdClass record from tool_muprog_cert
     */
    public static function update_program_certificate(array $data): \stdClass {
        global $DB;

        $data = (object)$data;
        $program = $DB->get_record('tool_muprog_program', ['id' => $data->id], '*', MUST_EXIST);
        $cert = $DB->get_record('tool_muprog_cert', ['programid' => $program->id]);
        if (!$cert) {
            $cert = new \stdClass();
            $cert->id = null;
            $cert->programid = $program->id;
            $cert->timecreated = time();
        }
        $cert->templateid = $data->templateid;
        $cert->expirydatetype = $data->expirydatetype;
        if ($data->expirydatetype == 1) {
            $cert->expirydateoffset = $data->expirydateabsolute;
        } else if ($data->expirydatetype == 2) {
            $cert->expirydateoffset = $data->expirydaterelative;
        } else {
            $cert->expirydatetype = 0;
            $cert->expirydateoffset = null;
        }

        if ($cert->id) {
            $DB->update_record('tool_muprog_cert', $cert);
        } else {
            $cert->id = $DB->insert_record('tool_muprog_cert', $cert);
        }

        return $DB->get_record('tool_muprog_cert', ['id' => $cert->id], '*', MUST_EXIST);
    }

    /**
     * Stop issuing of certificates for program completion.
     *
     * @param int $programid
     * @return void
     */
    public static function delete_program_certificate(int $programid): void {
        global $DB;
        $DB->delete_records('tool_muprog_cert', ['programid' => $programid]);
    }

    /**
     * Issue certificate.
     *
     * @param int $programid
     * @param int $userid
     * @return bool success
     */
    public static function issue(int $programid, int $userid): bool {
        global $DB;

        if (!PHPUNIT_TEST && !CLI_SCRIPT) {
            throw new \coding_exception('Certificates cannot be awarded from normal web pages');
        }

        $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $programid, 'userid' => $userid]);
        if (!$allocation) {
            return false;
        }

        $lockfactory = \core\lock\lock_config::get_lock_factory('tool_muprog_certificate_lock');
        $lock = $lockfactory->get_lock("allocation_{$allocation->id}", MINSECS);
        if (!$lock) {
            debugging('locktimeout when issuing certificate for allocation ' . $allocation->id, DEBUG_DEVELOPER);
            return false;
        }
        if ($DB->record_exists('tool_muprog_cert_issue', ['allocationid' => $allocation->id])) {
            // Prevent multiple certificates for program completion at the same time of one user.
            $lock->release();
            return false;
        }

        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id]);
        if (!$allocation || $allocation->archived || !$allocation->timecompleted) {
            $lock->release();
            return false;
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid]);
        if (!$program || $program->archived) {
            $lock->release();
            return false;
        }
        $cert = $DB->get_record('tool_muprog_cert', ['programid' => $programid]);
        if (!$cert) {
            $lock->release();
            return false;
        }
        $template = $DB->get_record('tool_certificate_templates', ['id' => $cert->templateid]);
        if (!$template) {
            $lock->release();
            return false;
        }
        $user = $DB->get_record('user', ['id' => $allocation->userid, 'deleted' => 0, 'confirmed' => 1]);
        if (!$user) {
            $lock->release();
            return false;
        }

        $template = \tool_certificate\template::instance($cert->templateid, $template);
        $issuedata = [
            'programid' => $program->id,
            'programfullname' => $program->fullname,
            'programidnumber' => $program->idnumber,
            'programtimecompleted' => $allocation->timecompleted,
            'programallocationid' => $allocation->id,
        ];
        $expirydate = \tool_certificate\certificate::calculate_expirydate(
            $cert->expirydatetype,
            $cert->expirydateoffset,
            $cert->expirydateoffset
        );
        $issueid = $template->issue_certificate($user->id, $expirydate, $issuedata, 'tool_muprog');

        $issue = new \stdClass();
        $issue->programid = $program->id;
        $issue->allocationid = $allocation->id;
        $issue->timecompleted = $allocation->timecompleted;
        $issue->issueid = $issueid;
        $issue->timecreated = time();
        $DB->insert_record('tool_muprog_cert_issue', $issue);

        $lock->release();

        return true;
    }

    /**
     * Called after certificate template is deleted.
     *
     * @param \tool_certificate\event\template_deleted $event
     * @return void
     */
    public static function template_deleted(\tool_certificate\event\template_deleted $event): void {
        global $DB;
        $DB->delete_records('tool_muprog_cert', ['templateid' => $event->objectid]);
    }

    /**
     * Issues program certificates.
     *
     * @return void
     */
    public static function cron(): void {
        global $DB;

        if (!self::is_available()) {
            return;
        }

        $params = ['now' => time()];
        $sql = "SELECT a.id, a.programid, a.userid
                  FROM {tool_muprog_program} p
                  JOIN {tool_muprog_allocation} a ON a.programid = p.id AND a.archived = 0 AND a.timecompleted <= :now
                  JOIN {user} u ON u.id = a.userid AND u.deleted = 0 and u.confirmed = 1
                  JOIN {tool_muprog_cert} c ON c.programid = p.id
                  JOIN {tool_certificate_templates} t ON t.id = c.templateid
             LEFT JOIN {tool_muprog_cert_issue} ci ON ci.allocationid = a.id AND ci.programid = p.id
                 WHERE p.archived = 0 AND ci.id IS NULL
              ORDER BY p.id ASC, u.id ASC";
        $issues = $DB->get_records_sql($sql, $params);
        foreach ($issues as $issue) {
            self::issue($issue->programid, $issue->userid);
        }

        $sql = "SELECT i.id
                  FROM {tool_certificate_issues} i
             LEFT JOIN {tool_muprog_cert_issue} ci ON ci.issueid = i.id
             LEFT JOIN {tool_muprog_program} p ON p.id = ci.programid
             LEFT JOIN {tool_muprog_allocation} a ON a.id = ci.allocationid
                 WHERE i.component = 'tool_muprog' AND i.archived = 0
                       AND (ci.id IS NULL OR p.id IS NULL OR a.id IS NULL)
              ORDER BY i.id ASC";
        $issues = $DB->get_records_sql($sql, []);
        foreach ($issues as $issue) {
            $DB->set_field('tool_certificate_issues', 'archived', 1, ['id' => $issue->id]);
        }
    }
}
