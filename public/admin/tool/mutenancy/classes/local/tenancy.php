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

/**
 * Multi-tenancy tenancy helper.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenancy {
    /**
     * Activate multi-tenancy.
     *
     * @return void
     */
    public static function activate(): void {
        $oldactive = get_config('tool_mutenancy', 'active');
        set_config('active', '1', 'tool_mutenancy');
        add_to_config_log('active', (int)$oldactive, '1', 'tool_mutenancy');
        \core\context_helper::reset_levels();

        user::create_role();
        manager::create_role();
    }

    /**
     * Deactivate multi-tenancy.
     *
     * @return void
     */
    public static function deactivate(): void {
        global $DB;

        $oldactive = get_config('tool_mutenancy', 'active');
        set_config('active', '0', 'tool_mutenancy');
        add_to_config_log('active', $oldactive, '0', 'tool_mutenancy');

        $DB->delete_records('tool_mutenancy_config', []);
        $DB->delete_records('tool_mutenancy_manager', []);

        manager::delete_role();
        $DB->set_field('role', 'archetype', '', ['archetype' => manager::ROLESHORTNAME]);

        user::delete_role();
        $DB->set_field('role', 'archetype', '', ['archetype' => user::ROLESHORTNAME]);

        role_unassign_all(['component' => 'tool_mutenancy']);

        $DB->set_field('cohort', 'component', '', ['component' => 'tool_mutenancy']);

        if ($DB->get_manager()->field_exists('context', 'tenantid')) {
            $DB->set_field_select('context', 'tenantid', null, "tenantid IS NOT NULL");
        }
        if ($DB->get_manager()->field_exists('user', 'tenantid')) {
            $DB->set_field_select('user', 'tenantid', null, "tenantid IS NOT NULL");
        }

        $base = '/' . SYSCONTEXTID . '/';
        $path = $DB->sql_concat("'$base'", 'id');
        $level = CONTEXT_USER;

        $sql = "UPDATE {context}
                   SET depth = 2, path = {$path}
                 WHERE contextlevel = {$level} AND depth = 3";
        $DB->execute($sql);

        \core\context_helper::reset_levels();

        \cache_helper::purge_by_event('tool_mutenancy_invalidatecaches');

        accesslib_clear_all_caches(true);
        \cache_helper::purge_by_event('changesincoursecat');
    }

    /**
     * Is multi-tenancy active?
     *
     * @return bool
     */
    public static function is_active(): bool {
        if (!function_exists('mutenancy_is_active')) {
            return false;
        }
        if (during_initial_install()) {
            return false;
        }
        return (bool)get_config('tool_mutenancy', 'active');
    }

    /**
     * Returns user's tenant id.
     *
     * @param int $userid
     * @return int|null tenant id
     */
    public static function get_user_tenantid(int $userid): ?int {
        if (!$userid || isguestuser($userid)) {
            return null;
        }

        $usercontext = \context_user::instance($userid, IGNORE_MISSING);
        if (!$usercontext) {
            return null;
        }
        return $usercontext->tenantid;
    }

    /**
     * Force current tenant temporarily.
     *
     * @param int|null $tenatid
     * @return void
     */
    public static function force_current_tenantid(?int $tenatid): void {
        global $CFG;

        if ($tenatid < 0) {
            debugging('Invalid negative tenantid to be forced', DEBUG_DEVELOPER);
        }

        if (isset($CFG->tool_mutenancy_forced_tenantid)) {
            debugging('Tenant is already forced: ' . $CFG->tool_mutenancy_forced_tenantid, DEBUG_DEVELOPER);
        }

        if ($tenatid) {
            $tenant = tenant::fetch($tenatid);
            if (!$tenant) {
                debugging('Unknown tenant id to be forced: ' . $tenatid, DEBUG_DEVELOPER);
                return;
            }
        }

        $CFG->tool_mutenancy_forced_tenantid = (int)$tenatid;

        self::fix_site();
    }

    /**
     * Stop temporary forcing of current tenant.
     *
     * @return void
     */
    public static function unforce_current_tenantid(): void {
        global $CFG;

        if (!isset($CFG->tool_mutenancy_forced_tenantid)) {
            debugging('Tenant is not forced, please check the code', DEBUG_DEVELOPER);
        }

        unset($CFG->tool_mutenancy_forced_tenantid);

        self::fix_site();
    }

    /**
     * Returns current tenant id.
     *
     * Result is forced tenant, current selected or switched tenant
     * with user tenant id as fallback.
     *
     * @return int|null
     */
    public static function get_current_tenantid(): ?int {
        global $USER, $SESSION, $CFG;

        if (isset($CFG->tool_mutenancy_forced_tenantid)) {
            if ($CFG->tool_mutenancy_forced_tenantid) {
                return $CFG->tool_mutenancy_forced_tenantid;
            } else {
                return null;
            }
        }

        if (isset($SESSION->tool_mutenancy_tenantid)) {
            // This is both tenant switching and speed up.
            if ($SESSION->tool_mutenancy_tenantid > 0) {
                return $SESSION->tool_mutenancy_tenantid;
            } else {
                return null;
            }
        }

        return self::get_user_tenantid($USER->id);
    }

    /**
     * Can current user see the tenant switching widget?
     *
     * NOTE: this information is cached and may not be always accurate.
     *
     * @return bool
     */
    public static function can_switch(): bool {
        global $DB, $USER, $SESSION;

        if (!self::is_active()) {
            return false;
        }

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if (self::get_user_tenantid($USER->id)) {
            return false;
        }

        $syscontext = \context_system::instance();
        if (has_capability('tool/mutenancy:switch', $syscontext)) {
            unset($SESSION->tool_mutenancy_can_switch);
            return true;
        }

        if (!empty($SESSION->tool_mutenancy_can_switch)) {
            return true;
        }

        $sql = "SELECT 'x'
                  FROM {cohort_members} cm
                  JOIN {tool_mutenancy_tenant} t ON t.assoccohortid = cm.cohortid AND t.archived = 0
                 WHERE cm.userid = :me";
        if ($DB->record_exists_sql($sql, ['me' => $USER->id])) {
            $SESSION->tool_mutenancy_can_switch = true;
            return true;
        }

        // For now, we just guess here to make this faster.
        [$needed, $forbidden] = get_roles_with_cap_in_context($syscontext, 'tool/mutenancy:switch');
        if (!$needed) {
            $SESSION->tool_mutenancy_can_switch = false;
            return false;
        }
        $needed = implode(',', $needed);
        $sql = "SELECT 'x'
                  FROM {role_assignments} ra
                  JOIN {context} c ON c.id = ra.contextid AND c.contextlevel = :tenantlevel
                  JOIN {tool_mutenancy_tenant} t ON t.id = c.instanceid AND t.archived = 0
                 WHERE ra.userid = :me AND ra.roleid IN ($needed)";
        $params = ['tenantlevel' => \context_tenant::LEVEL, 'me' => $USER->id];
        $SESSION->tool_mutenancy_can_switch = $DB->record_exists_sql($sql, $params);

        return $SESSION->tool_mutenancy_can_switch;
    }

    /**
     * Switch current tenant and set tenant session cookie.
     *
     * NOTE: this cannot be used when current user is tenant member!
     *
     * @param int|null $tenantid
     * @return void
     */
    public static function switch(?int $tenantid): void {
        global $SESSION, $USER;

        // Purge switching cache just in case.
        unset($SESSION->tool_mutenancy_can_switch);

        if ($tenantid < 0) {
            throw new \core\exception\invalid_parameter_exception('Invalid tenant id');
        }

        $usertenantid = self::get_user_tenantid($USER->id);
        if ($usertenantid && $usertenantid != $tenantid) {
            throw new \core\exception\coding_exception('Tenant members cannot switch tenant');
        }

        if (isset($CFG->tool_mutenancy_forced_tenantid)) {
            debugging('Tenant is forced, un-enforcing before switch', DEBUG_DEVELOPER);
            unset($CFG->tool_mutenancy_forced_tenantid);
        }

        if ($tenantid) {
            $tenant = tenant::fetch($tenantid);
            if (!$tenant) {
                throw new \core\exception\coding_exception('Invalid tenant id');
            }
            $SESSION->tool_mutenancy_tenantid = (int)$tenant->id;
        } else {
            // Always set current tenant to speed up get_current_tenantid(),
            // it is expected that callback_session_set_user() is called when $USER changes.
            $SESSION->tool_mutenancy_tenantid = 0;
        }

        self::set_cookie($tenantid);
        self::fix_site();
    }

    /**
     * Fix $SITE global based on current tenant.
     *
     * @param int|null $tenantid -1 menas use current tenant
     * @return void
     */
    public static function fix_site(?int $tenantid = -1): void {
        global $DB;

        if ($tenantid < 0) {
            $tenantid = self::get_current_tenantid();
        }

        // phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase

        if (isset($GLOBALS['SITE']->tenantid)) {
            if ($GLOBALS['SITE']->tenantid == $tenantid) {
                return;
            }
            $GLOBALS['SITE'] = $DB->get_record('course', ['category' => 0], '*', MUST_EXIST);
        } else {
            if (!$tenantid) {
                return;
            }
        }

        if ($tenantid) {
            $tenant = tenant::fetch($tenantid);
            if ($tenant) {
                if (isset($tenant->sitefullname)) {
                    $GLOBALS['SITE']->fullname = $tenant->sitefullname;
                } else {
                    $GLOBALS['SITE']->fullname = $tenant->name;
                }
                if (isset($tenant->siteshortname)) {
                    $GLOBALS['SITE']->shortname = $tenant->siteshortname;
                } else {
                    $GLOBALS['SITE']->shortname = $tenant->idnumber;
                }
            }
            $GLOBALS['SITE']->tenantid = (int)$tenantid;
        } else {
            $GLOBALS['SITE']->tenantid = 0;
        }

        if (isset($GLOBALS['COURSE']) && $GLOBALS['COURSE']->id == $GLOBALS['SITE']->id) {
            $GLOBALS['COURSE'] = clone($GLOBALS['SITE']);
            unset($GLOBALS['COURSE']->tenantid);
        }

        // phpcs:enable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
    }

    /**
     * Returns tenant cookie name.
     *
     * @return string
     */
    public static function get_cookie_name(): string {
        global $CFG;
        return 'TENANT' . $CFG->sessioncookie;
    }

    /**
     * Send current tenant cookie for pages that do not use sessions.
     * @param int|null $tenantid
     */
    private static function set_cookie(?int $tenantid): void {
        global $CFG;

        if (!PHPUNIT_TEST && (CLI_SCRIPT || WS_SERVER || NO_MOODLE_COOKIES)) {
            return;
        }

        $cname = self::get_cookie_name();

        $cookie = '0';
        if ($tenantid) {
            $tenant = tenant::fetch($tenantid);
            if ($tenant && !$tenant->archived) {
                $cookie = $tenant->idnumber;
            }
        }

        if (PHPUNIT_TEST) {
            $_COOKIE[$cname] = $cookie;
        } else {
            setcookie(
                $cname,
                $cookie,
                time() + (30 * DAYSECS),
                $CFG->sessioncookiepath,
                $CFG->sessioncookiedomain,
                is_moodle_cookie_secure(),
                true
            );
        }
    }

    /**
     * Returns tenant related restriction for adding users somewhere.
     *
     * @param string $useridfield
     * @param \context $context relevant context, note current tenant id is used for non-tenant contexts
     * @param string $glue glue for non-empty result, for example "AND"; if '' used then "1=1" is returned instead ''
     * @return string WHERE condition linked via $userfield
     */
    public static function get_related_users_exists(string $useridfield, \context $context, string $glue = "AND"): string {
        if (self::is_active()) {
            $tenantid = (int)$context->tenantid;
            if (!$tenantid) {
                $tenantid = (int)self::get_current_tenantid();
            }
        } else {
            debugging('tenancy::get_related_users_exists() should not be called if tenants not active', DEBUG_DEVELOPER);
            $tenantid = 0;
        }

        if (!$tenantid) {
            if ($glue === '') {
                // Return something always true as WHERE condition.
                return "1=1";
            } else {
                // This is expected to be appended to existing WHERE conditions.
                return "";
            }
        }

        $sql = "SELECT 'x'
                  FROM {user} ugrue
                  JOIN {tool_mutenancy_tenant} t ON t.id = $tenantid
             LEFT JOIN {cohort_members} acm ON acm.userid = ugrue.id AND acm.cohortid = t.assoccohortid
                 WHERE ugrue.deleted = 0 AND ugrue.id = $useridfield
                       AND (ugrue.tenantid = $tenantid OR (acm.id IS NOT NULL AND ugrue.tenantid IS NULL))";
        return "$glue EXISTS ($sql)";
    }

    /**
     * Return string with tenant entity name singular.
     *
     * NOTE: customised strings are meant for end users, tenant management may still show the word "Tenant".
     *
     * @param string $identifier
     * @param string $component
     * @return string
     */
    public static function get_tenant_string(string $identifier, string $component = 'tool_mutenancy'): string {
        $entity = get_config('tool_mutenancy', 'tenantentity');
        if ($identifier === 'tenant' && $component === 'tool_mutenancy') {
            if ($entity) {
                return $entity;
            }
            return get_string('tenant', $component);
        }
        if ($entity) {
            return get_string($identifier . '_a', $component, $entity);
        }
        return get_string($identifier, $component);
    }

    /**
     * Return string with tenant entity name plural.
     *
     * NOTE: customised strings are meant for end users, tenant management may still show the word "Tenants".
     *
     * @param string $identifier
     * @param string $component
     * @return string
     */
    public static function get_tenants_string(string $identifier, string $component = 'tool_mutenancy'): string {
        $entities = get_config('tool_mutenancy', 'tenantentities');
        if ($identifier === 'tenants' && $component === 'tool_mutenancy') {
            if ($entities) {
                return $entities;
            }
            return get_string('tenants', $component);
        }
        if ($entities) {
            return get_string($identifier . '_a', $component, $entities);
        }
        return get_string($identifier, $component);
    }

    /**
     * Called from lib/setup.php to initialise $SITE always
     * and to set current tenant on pages that do not use sessions.
     * Is also sets, refreshes and uses cookies.
     */
    public static function callback_lib_setup(): void {
        global $SESSION, $USER;

        $cname = self::get_cookie_name();

        if (!isset($SESSION->tool_mutenancy_tenantid)) {
            $usertenantid = self::get_user_tenantid($USER->id);
            if ($usertenantid) {
                // Tenant members cannot switch tenants!
                $tenant = tenant::fetch($usertenantid);
                if ($tenant && !$tenant->archived) {
                    $SESSION->tool_mutenancy_tenantid = (int)$tenant->id;
                } else {
                    // This should not happen.
                    $SESSION->tool_mutenancy_tenantid = 0;
                }
            } else {
                if (isset($_COOKIE[$cname])) {
                    $cookie = clean_param($_COOKIE[$cname], PARAM_ALPHANUM);
                    // Restore selected tenant from cookie.
                    if ($cookie === '0') {
                        $SESSION->tool_mutenancy_tenantid = 0;
                    } else {
                        $tenant = tenant::fetch_by_idnumber($cookie);
                        if ($tenant && !$tenant->archived) {
                            $SESSION->tool_mutenancy_tenantid = (int)$tenant->id;
                        } else {
                            $SESSION->tool_mutenancy_tenantid = 0;
                        }
                    }
                } else {
                    $SESSION->tool_mutenancy_tenantid = 0;
                }
            }
            self::set_cookie($SESSION->tool_mutenancy_tenantid);
        }

        self::fix_site();
    }

    /**
     * Fix $SITE after login and any other current user change,
     * and remember current tenant using cookie.
     */
    public static function callback_session_set_user(): void {
        global $SESSION, $USER;

        $usertenantid = self::get_user_tenantid($USER->id);

        // Guests and no-logged-in users cannot access tenants.
        $SESSION->tool_mutenancy_tenantid = (int)$usertenantid;

        // Always set session cookie here so that we have it ready for next login
        // and for page that do not use sessions.
        self::set_cookie($SESSION->tool_mutenancy_tenantid);
        self::fix_site();
    }

    /**
     * Switch tenants at the login page.
     */
    public static function callback_login_page(): void {
        if (!isloggedin() || isguestuser()) {
            $tenant = optional_param('tenant', null, PARAM_ALPHANUM);
            if ($tenant === null) {
                return;
            }
            if ($tenant !== '' && $tenant !== '0') {
                $tenant = \tool_mutenancy\local\tenant::fetch_by_idnumber($tenant);
                if ($tenant && !$tenant->archived) {
                    if ($tenant->id != self::get_current_tenantid()) {
                        self::switch($tenant->id);
                        redirect(new \moodle_url('/login/', ['tenant' => $tenant->idnumber]));
                    }
                    return;
                }
            }
            if (self::get_current_tenantid()) {
                self::switch(0);
                redirect(new \moodle_url('/login/', ['tenant' => '0']));
            }
        }
    }

    /**
     * Un-switch tenants on logout page.
     */
    public static function callback_logout(): void {
        global $USER;

        $usertenantid = self::get_user_tenantid($USER->id);
        self::set_cookie($usertenantid);
    }
}
