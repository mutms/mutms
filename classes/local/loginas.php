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

namespace tool_muloginas\local;

use stdClass;

/**
 * Incognito Log-in-as utility class.
 *
 * @package    tool_muloginas
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class loginas {
    /** @var int max token lifetime in seconds - short so that it cannot be shared with other people */
    public const LIFETIME = 15;
    /** @var string cookies name - helps keeping track of Incognito sessions */
    public const COOKIE_NAME = 'TOOL_MULOGINAS_INCOGNITO';

    /**
     * Can current user Log-in-as given user?
     *
     * @param stdClass $targetuser
     * @return bool
     */
    public static function can_loginas(stdClass $targetuser): bool {
        global $USER;

        if (!$targetuser->id || isguestuser($targetuser)) {
            return false;
        }
        if ($targetuser->deleted || !$targetuser->confirmed) {
            return false;
        }

        if ($targetuser->id == $USER->id || is_siteadmin($targetuser->id)) {
            return false;
        }

        if (\core\session\manager::is_loggedinas()) {
            return false;
        }

        if (!has_capability('tool/muloginas:loginas', \context_system::instance())) {
            return false;
        }

        return true;
    }

    /**
     * Create new log-in-as token for new incognito/private window.
     *
     * @param int $targetuserid
     * @return stdClass request record with token
     */
    public static function create_request(int $targetuserid): stdClass {
        global $USER, $DB;

        if (!isloggedin() || isguestuser()) {
            throw new \core\exception\coding_exception('login required');
        }

        if (PHPUNIT_TEST) {
            $sid = 'testsessionid_' . random_string(10);
        } else {
            $sid = session_id();
        }
        if ($sid === false || $sid === '') {
            throw new \core\exception\coding_exception('invalid session');
        }

        $record = new stdClass();
        $record->token = random_string(40);
        $record->userid = $USER->id;
        $record->timecreated = time();
        $record->sid = $sid;
        $record->useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $record->targetuserid = $targetuserid;
        $record->id = $DB->insert_record('tool_muloginas_request', $record);

        return $DB->get_record('tool_muloginas_request', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Is this a valid Log-in-as request token?
     *
     * NOTE: the request is always marked as used here.
     *
     * @param string $token
     * @return array|null target user record
     */
    public static function validate_request(string $token): ?array {
        global $DB;

        $now = time();

        if ($token === '') {
            return null;
        }

        $request = $DB->get_record('tool_muloginas_request', ['token' => $token]);
        if (!$request || $request->timeused) {
            return null;
        }

        if ($now - $request->timecreated > self::LIFETIME) {
            return null;
        }

        if ($request->useragent !== $_SERVER['HTTP_USER_AGENT']) {
            return null;
        }

        $user = $DB->get_record('user', ['id' => $request->userid, 'deleted' => 0]);
        if (!$user) {
            return null;
        }

        $targetuser = $DB->get_record('user', ['id' => $request->targetuserid, 'deleted' => 0]);
        if (!$targetuser) {
            return null;
        }

        if (is_siteadmin($targetuser->id)) {
            // Make sure this is not used to gain admin access no matter what.
            return null;
        }

        return [$targetuser, $request, $user];
    }

    /**
     * Set up fake user session and log-in-as target user session.
     *
     * @param stdClass $targetuser
     * @param stdClass $request
     * @param stdClass $user
     * @return void
     */
    public static function log_in_as(stdClass $targetuser, stdClass $request, stdClass $user): void {
        global $USER, $DB, $CFG;
        $targetuserid = $targetuser->id;
        $realuserid = $user->id;

        $DB->set_field('tool_muloginas_request', 'timeused', time(), ['id' => $request->id]);

        ignore_user_abort(true);

        try {
            \core\session\manager::set_user($user);
            \core\session\manager::loginas($targetuser->id, \context_system::instance());
        } finally {
            if ($realuserid != $_SESSION['REALUSER']->id || $targetuserid != $USER->id || !$USER->realuser) {
                \core\session\manager::terminate_current();
                throw new \core\exception\coding_exception('Invalid log-in-as state detected');
            }
        }

        if (PHPUNIT_TEST) {
            $sid = 'testsessionid_' . random_string(10);
        } else {
            $sid = session_id();
        }

        $DB->set_field('tool_muloginas_request', 'targetsid', $sid, ['id' => $request->id]);
        $fullname = fullname($targetuser);
        $message = get_string('loggedinas', 'tool_muloginas', $fullname);

        if (PHPUNIT_TEST) {
            return;
        }

        $cookiesecure = is_moodle_cookie_secure();
        setcookie(self::COOKIE_NAME, '1', time() + DAYSECS, $CFG->sessioncookiepath, $CFG->sessioncookiedomain, $cookiesecure, $CFG->cookiehttponly);

        \core\notification::add($message, \core\output\notification::NOTIFY_SUCCESS);
    }

    /**
     * Log out observer.
     *
     * @param \core\event\user_loggedout $event
     * @return void
     */
    public static function user_loggedout(\core\event\user_loggedout $event): void {
        global $DB, $PAGE, $OUTPUT, $SITE;

        if (empty($event->other['sessionid'])) {
            return;
        }

        $sid = $event->other['sessionid'];
        $userid = $event->objectid;

        // End all log-in-as sessions of logged-out user.

        if ($DB->record_exists('tool_muloginas_request', ['userid' => $userid, 'sid' => $sid])) {
            $select = "userid = :userid AND sid = :sid AND targetsid IS NOT NULL";
            $sessions = $DB->get_records_select('tool_muloginas_request', $select, ['sid' => $sid, 'userid' => $userid]);
            foreach ($sessions as $session) {
                \core\session\manager::destroy($session->targetsid);
            }
            $DB->delete_records('tool_muloginas_request', ['sid' => $sid, 'userid' => $userid]);
        }

        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return;
        }

        // Terminate log-in-as session.

        if (!$DB->record_exists('tool_muloginas_request', ['targetsid' => $sid, 'targetuserid' => $userid])) {
            return;
        }

        // Keep track of session id so that we can reuse the Incognito session if they do not close all windows.
        $DB->set_field('tool_muloginas_request', 'targetsid', session_id(), ['targetsid' => $sid, 'targetuserid' => $userid]);

        $PAGE->set_pagelayout('login');
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url(new \moodle_url('/'));
        $heading = get_string('info_heading', 'tool_muloginas', format_string($SITE->shortname));
        $PAGE->set_heading($heading);

        echo $OUTPUT->header();

        echo $OUTPUT->heading($heading);

        echo '<div class="alert alert-warning">';
        echo get_string('logoutinfo', 'tool_muloginas');
        echo '</div>';

        echo $OUTPUT->footer();
        die();
    }

    /**
     * User deletion observer.
     *
     * @param \core\event\user_deleted $event
     * @return void
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;

        $userid = $event->objectid;

        if ($DB->record_exists('tool_muloginas_request', ['userid' => $userid])) {
            $select = "userid = :userid AND targetsid IS NOT NULL AND timeused > :cutoff";
            $params = ['userid' => $userid, 'cutoff' => time() - DAYSECS];
            $sessions = $DB->get_records_select('tool_muloginas_request', $select, $params);
            foreach ($sessions as $session) {
                \core\session\manager::destroy($session->targetsid);
            }
            $DB->delete_records('tool_muloginas_request', ['userid' => $userid]);
        }

        if ($DB->record_exists('tool_muloginas_request', ['targetuserid' => $userid])) {
            $select = "targetuserid = :targetuserid AND targetsid IS NOT NULL AND timeused > :cutoff";
            $params = ['targetuserid' => $userid, 'cutoff' => time() - DAYSECS];
            $sessions = $DB->get_records_select('tool_muloginas_request', $select, $params);
            foreach ($sessions as $session) {
                \core\session\manager::destroy($session->targetsid);
            }
            $DB->delete_records('tool_muloginas_request', ['targetuserid' => $userid]);
        }
    }

    /**
     * Old data purging.
     */
    public static function cron_cleanup(): void {
        global $DB;

        // Delete expired unused tokens.
        $select = "timecreated < :cutoff AND timeused IS NULL";
        $params = ['cutoff' => time() - HOURSECS];
        $DB->delete_records_select('tool_muloginas_request', $select, $params);

        // Delete very old sessions.
        $select = "timeused < :cutoff";
        $params = ['cutoff' => time() - DAYSECS * 3];
        $DB->delete_records_select('tool_muloginas_request', $select, $params);

        // Terminate sessions that are more than a day old.
        $select = "targetsid IS NOT NULL AND timeused < :cutoff ";
        $params = ['cutoff' => time() - DAYSECS];
        $rs = $DB->get_recordset_select('tool_muloginas_request', $select, $params);
        foreach ($rs as $session) {
            \core\session\manager::destroy($session->targetsid);
            $DB->delete_records('tool_muloginas_request', ['id' => $session->id]);
        }
        $rs->close();
    }
}
