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

namespace tool_mutenancy\local;

use stdClass;

/**
 * Tenant member helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class member {
    /**
     * Confirm member account.
     *
     * @param int $userid
     * @return bool
     */
    public static function confirm(int $userid): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!$user->tenantid) {
            throw new \core\exception\invalid_parameter_exception('tenant members only');
        }

        if ($user->confirmed) {
            return true;
        }

        $auth = get_auth_plugin($user->auth);

        $auth->user_confirm($user->username, $user->secret);

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        return $user->confirmed;
    }

    /**
     * Resend confirmation member account email.
     *
     * @param int $userid
     * @return bool
     */
    public static function resend(int $userid): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!$user->tenantid) {
            throw new \core\exception\invalid_parameter_exception('tenant members only');
        }

        if ($user->confirmed) {
            return true;
        }

        return (bool)send_confirmation_email($user);
    }

    /**
     * Suspend member account.
     *
     * @param int $userid
     * @return stdClass
     */
    public static function suspend(int $userid): stdClass {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!$user->tenantid || isguestuser($user)) {
            throw new \core\exception\invalid_parameter_exception('tenant members only');
        }

        if ($user->id == $USER->id) {
            throw new \core\exception\invalid_parameter_exception('cannot suspend own account');
        }

        if ($user->suspended) {
            return $user;
        }

        $user->suspended = 1;
        user_update_user($user, false);

        // Force logout.
        \core\session\manager::destroy_user_sessions($user->id);

        return $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
    }

    /**
     * Unsuspend member account.
     *
     * @param int $userid
     * @return stdClass
     */
    public static function unsuspend(int $userid): stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!$user->tenantid) {
            throw new \core\exception\invalid_parameter_exception('tenant members only');
        }
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $user->tenantid], '*', MUST_EXIST);
        if ($tenant->archived) {
            throw new \core\exception\invalid_parameter_exception('Cannot activate members in archived tenants');
        }

        if (!$user->suspended) {
            return $user;
        }

        $user->suspended = 0;
        user_update_user($user, false);

        return $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
    }

    /**
     * Unlock member account.
     *
     * @param int $userid
     * @return void
     */
    public static function unlock(int $userid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/lib/authlib.php');

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!$user->tenantid) {
            throw new \core\exception\invalid_parameter_exception('tenant members only');
        }

        login_unlock_account($user);
    }

    /**
     * Delete member account.
     *
     * @param int $userid
     * @return void
     */
    public static function delete(int $userid): void {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!$user->tenantid || isguestuser($user)) {
            throw new \core\exception\invalid_parameter_exception('tenant members only');
        }

        if ($user->id == $USER->id) {
            throw new \core\exception\invalid_parameter_exception('cannot delete own account');
        }

        if ($user->deleted) {
            return;
        }

        user_delete_user($user);
    }

    /**
     * User created event observer.
     *
     * @param \core\event\user_created $event
     * @return void
     */
    public static function user_created(\core\event\user_created $event): void {
        global $CFG;
        if (!tenancy::is_active()) {
            return;
        }

        $context = \context_user::instance($event->objectid);
        if (!$context->tenantid) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');

        $tenant = tenant::fetch($context->tenantid);
        cohort_add_member($tenant->cohortid, $event->objectid);

        $role = user::get_role();
        $categorycontext = \context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);
        if ($categorycontext) {
            role_assign($role->id, $event->objectid, $categorycontext->id, 'tool_mutenancy', $tenant->id);
        }
    }
}
