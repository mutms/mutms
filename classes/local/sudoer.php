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

namespace tool_musudo\local;

use stdClass;

/**
 * Sudoer helper.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer {
    /**
     * Add sudo user.
     *
     * @param stdClass $data
     * @return stdClass sudoer record
     */
    public static function create(stdClass $data): stdClass {
        global $DB;

        $user = $DB->get_record('user', ['id' => $data->userid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

        if ($DB->record_exists('tool_musudo_sudoer', ['userid' => $user->id])) {
            throw new \core\exception\invalid_parameter_exception('user is already privileged');
        }

        $record = new stdClass();
        $record->userid = $user->id;
        $record->mfarequired = (int)(bool)($data->mfarequired ?? 0);
        $record->note = $data->note ?? '';

        $privileges = [];
        foreach ($data->contextid as $i => $contextid) {
            if (!$contextid) {
                throw new \core\exception\invalid_parameter_exception('contextid is required');
            }
            if (empty($data->roleid[$i])) {
                throw new \core\exception\invalid_parameter_exception('roleid is required');
            }
            $context = \context::instance_by_id($contextid);
            $role = $DB->get_record('role', ['id' => $data->roleid[$i]]);
            $privileges[] = ['contextid' => (int)$context->id, 'roleid' => (int)$role->id];
        }
        $record->privilegesjson = json_encode($privileges);
        $record->timecreated = time();

        $record->id = $DB->insert_record('tool_musudo_sudoer', $record);

        util::fix_musudo_active();

        $sudoer = $DB->get_record('tool_musudo_sudoer', ['id' => $record->id], '*', MUST_EXIST);
        \tool_musudo\event\sudoer_created::create_from_sudoer($sudoer)->trigger();

        return $sudoer;
    }

    /**
     * Update sudo user.
     *
     * @param stdClass $data
     * @return stdClass sudoer record
     */
    public static function update(stdClass $data): stdClass {
        global $DB;

        $oldsudoer = $DB->get_record('tool_musudo_sudoer', ['id' => $data->id], '*', MUST_EXIST);

        $record = new stdClass();
        $record->id = $oldsudoer->id;
        if (property_exists($data, 'mfarequired')) {
            $record->mfarequired = (int)(bool)$data->mfarequired;
        }
        if (property_exists($data, 'note')) {
            $record->note = $data->note;
        }

        $privileges = [];
        foreach ($data->contextid as $i => $contextid) {
            if (!$contextid) {
                throw new \core\exception\invalid_parameter_exception('contextid is required');
            }
            if (empty($data->roleid[$i])) {
                throw new \core\exception\invalid_parameter_exception('roleid is required');
            }
            $context = \context::instance_by_id($contextid);
            $role = $DB->get_record('role', ['id' => $data->roleid[$i]]);
            $privileges[] = ['contextid' => (int)$context->id, 'roleid' => (int)$role->id];
        }
        $record->privilegesjson = json_encode($privileges);

        $DB->update_record('tool_musudo_sudoer', $record);

        util::fix_musudo_active();

        $sudoer = $DB->get_record('tool_musudo_sudoer', ['id' => $record->id], '*', MUST_EXIST);
        \tool_musudo\event\sudoer_updated::create_from_sudoer($sudoer)->trigger();

        return $sudoer;
    }

    /**
     * Remove sudo user.
     *
     * @param int $sudoerid
     */
    public static function delete(int $sudoerid): void {
        global $DB;

        $sudoer = $DB->get_record('tool_musudo_sudoer', ['id' => $sudoerid]);
        if (!$sudoer) {
            util::fix_musudo_active();
            return;
        }

        $DB->delete_records('tool_musudo_sudoer', ['id' => $sudoer->id]);

        util::fix_musudo_active();

        \tool_musudo\event\sudoer_deleted::create_from_sudoer($sudoer)->trigger();
    }

    /**
     * Fetch role options for sudoers.
     *
     * @return array
     */
    public static function get_role_options(): array {
        return role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL, true);
    }

    /**
     * Return human-readable description of privileges.
     *
     * @param stdClass $sudoer
     * @param bool $addlinks
     * @return string
     */
    public static function get_privileges_description(stdClass $sudoer, bool $addlinks = false): string {
        $roles = role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL, true);
        $result = [];
        $privileges = json_decode($sudoer->privilegesjson);
        foreach ($privileges as $privilege) {
            $context = \context::instance_by_id($privilege->contextid, IGNORE_MISSING);
            $rolename = $roles[$privilege->roleid] ?? null;
            if (!$context || !$rolename) {
                $result[] = get_string('error');
            } else {
                $contexname = $context->get_context_name(true);
                if ($addlinks && $context->contextlevel != CONTEXT_SYSTEM) {
                    $url = $context->get_url();
                    $contexname = \html_writer::link($url, $contexname);
                }
                $a = ['role' => $rolename, 'context' => $contexname];
                $result[] = get_string('privilege_details', 'tool_musudo', $a);
            }
        }
        return implode('<br />', $result);
    }

    /**
     * Can current user sudo?
     *
     * @param bool $usecache
     * @return bool
     */
    public static function can_sudo(bool $usecache = false): bool {
        global $DB, $USER;

        if (CLI_SCRIPT && !PHPUNIT_TEST) {
            return false;
        }
        if (WS_SERVER) {
            return false;
        }

        if (!util::is_musudo_active()) {
            return false;
        }

        if (\core\session\manager::is_loggedinas()) {
            return false;
        }

        if (is_siteadmin()) {
            return false;
        }

        if ($usecache && isset($USER->access['sudoer']) && is_bool($USER->access['sudoer'])) {
            return $USER->access['sudoer'];
        }

        $USER->access['sudoer'] = $DB->record_exists('tool_musudo_sudoer', ['userid' => $USER->id]);
        return $USER->access['sudoer'];
    }

    /**
     * Is sudo session started?
     *
     * @return bool
     */
    public static function is_sudo_started(): bool {
        global $USER;

        return !empty($USER->access['sudosince']);
    }

    /**
     * Start sudo session.
     *
     * @return bool
     */
    public static function start_sudo(): bool {
        global $DB, $USER;

        if (!self::can_sudo()) {
            return false;
        }

        $user = $DB->get_record('user', ['id' => $USER->id, 'deleted' => 0], '*', MUST_EXIST);

        // Restart user session to get new session caches.

        // Keep previous MFA state.
        if (isset($SESSION->tool_mfa_authenticated)) {
            $tma = $SESSION->tool_mfa_authenticated;
        } else {
            $tma = null;
        }

        // Do not use core API to prevent unexpected caches being filled,
        // see \core\session\manager::login_user() that should match this minus the enrolments.
        $sid = session_id();
        if (!PHPUNIT_TEST) {
            session_regenerate_id(true);
            \core\session\manager::destroy($sid);
            \core\session\manager::add_session($user->id);
            \core\session\manager::set_user($user);
        }

        load_all_capabilities();
        $USER->access['rsw'] = [];

        $sudoer = $DB->get_record('tool_musudo_sudoer', ['userid' => $USER->id], '*', MUST_EXIST);
        $privileges = json_decode($sudoer->privilegesjson);
        $started = false;
        foreach ($privileges as $privilege) {
            $context = \context::instance_by_id($privilege->contextid, IGNORE_MISSING);
            if (!$context) {
                continue;
            }
            $USER->access['rsw'][$context->path] = $privilege->roleid;
            $started = true;
        }
        if (!$started) {
            return false;
        }
        $USER->access['sudosince'] = time();

        if (isset($tma)) {
            $SESSION->tool_mfa_authenticated = $tma;
        }

        \tool_musudo\event\sudo_started::create_from_sudoer($sudoer)->trigger();

        return true;
    }

    /**
     * End sudo session.
     *
     * @return void
     */
    public static function end_sudo(): void {
        global $DB, $USER;

        if (!self::can_sudo()) {
            throw new \core\exception\coding_exception('current user cannot sudo');
        }

        $user = $DB->get_record('user', ['id' => $USER->id, 'deleted' => 0], '*', MUST_EXIST);

        // Keep previous MFA state.
        if (isset($SESSION->tool_mfa_authenticated)) {
            $tma = $SESSION->tool_mfa_authenticated;
        } else {
            $tma = null;
        }

        $isstarted = self::is_sudo_started();

        // Fully restart user session to get new session caches.
        \core\session\manager::login_user($user);

        if (isset($tma)) {
            $SESSION->tool_mfa_authenticated = $tma;
        }

        if ($isstarted) {
            $sudoer = $DB->get_record('tool_musudo_sudoer', ['userid' => $USER->id]);
            if ($sudoer) {
                \tool_musudo\event\sudo_ended::create_from_sudoer($sudoer)->trigger();
            }
        }
    }

    /**
     * User deleted event observer.
     *
     * @param \core\event\user_deleted $event
     * @return void
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;

        if (!util::is_musudo_active()) {
            return;
        }

        $userid = $event->objectid;

        $DB->delete_records('tool_musudo_sudoer', ['userid' => $userid]);
    }

    /**
     * Hook for all pages.
     *
     * @param \core\hook\after_config $hook
     * @return void
     */
    public static function after_config(\core\hook\after_config $hook): void {
        global $USER;

        if (during_initial_install()) {
            return;
        }

        if (!util::is_musudo_active()) {
            return;
        }

        if (empty($USER->access['sudosince'])) {
            return;
        }

        if (!empty($USER->access['rsw'])) {
            return;
        }

        // They must have removed the last switchrole in course context, let's terminate the session.
        if (defined('TOOL_MUSUDO_END_SCRIPT') && TOOL_MUSUDO_END_SCRIPT) {
            return;
        }

        redirect(new \moodle_url('/admin/tool/musudo/sudo_end.php', ['sesskey' => sesskey()]));
    }

    /**
     * User menu hook.
     *
     * @param \core_user\hook\extend_user_menu $hook
     * @return void
     */
    public static function extend_user_menu(\core_user\hook\extend_user_menu $hook): void {
        if (during_initial_install()) {
            return;
        }

        if (!util::is_musudo_active()) {
            return;
        }

        if (self::is_sudo_started()) {
            $item = new \stdClass();
            $item->itemtype = 'link';
            $item->url = new \moodle_url('/admin/tool/musudo/sudo_end.php', ['sesskey' => sesskey()]);
            $item->title = get_string('sudo_end', 'tool_musudo');
            $item->titleidentifier = 'sudo_end,tool_musudo';
            $hook->add_navitem($item);
            return;
        }

        if (!self::can_sudo(true)) {
            return;
        }

        $item = new \stdClass();
        $item->itemtype = 'link';
        $item->url = new \moodle_url('/admin/tool/musudo/sudo_start.php');
        $item->title = get_string('sudo_start', 'tool_musudo');
        $item->titleidentifier = 'sudo_start,tool_musudo';
        $hook->add_navitem($item);
    }
}
