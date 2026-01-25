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

namespace tool_muhome\callback;

use tool_muhome\local\page;
use core\url;

/**
 * Hook and event callbacks from core related code.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class core {
    /**
     * All pages hook.
     *
     * @param \core\hook\after_config $hook
     */
    public static function hook_after_config(\core\hook\after_config $hook): void {
        global $CFG, $SCRIPT;

        if (!is_callable([\tool_mulib\local\mulib::class, 'is_muhome_active'])) {
            return;
        }

        if (!PHPUNIT_TEST) {
            if (CLI_SCRIPT || NO_MOODLE_COOKIES || WS_SERVER || AJAX_SCRIPT) {
                return;
            }
        }

        if (!isset($CFG->defaulthomepage)) {
            return;
        }

        if (!\tool_mulib\local\mulib::is_muhome_active()) {
            return;
        }

        if (
            $SCRIPT === '/admin/settings.php'
            || $SCRIPT === '/admin/upgradesettings.php'
            || $SCRIPT === '/admin/search.php'
            || $SCRIPT === '/user/preferences.php'
            || $SCRIPT === '/login/index.php'
        ) {
            // Settings and preference pages must use the real $CFG->defaulthomepage value.
            return;
        }

        if (!get_config('tool_muhome', 'replacehome')) {
            return;
        }

        // Use session cache only if not on a custom home page.
        $pages = page::get_my_pages($SCRIPT !== '/admin/tool/muhome/index.php');
        if (!$pages) {
            return;
        }

        // Remember the real value of $CFG->defaulthomepage, we will need to undo it
        // when user loggs in to get correct homepage.
        if (!isset($CFG->tool_muhome_real_defaulthomepage)) {
            $CFG->tool_muhome_real_defaulthomepage = $CFG->defaulthomepage;
        }

        $homepage = page::get_url(null);
        $homepagelocal = $homepage->out_as_local_url(false);

        if ($SCRIPT === '/index.php') {
            // We have to redirect here because redirect logic in /index.php is incomplete.
            if (!isloggedin()) {
                redirect($homepage);
            }
            // Parameter redirect=0 means always redirect to custom home page.
            $redirect = optional_param('redirect', 1, PARAM_BOOL);
            if (!$redirect) {
                redirect($homepage);
            }
            if (isguestuser()) {
                if (!empty($CFG->allowguestmymoodle) && $CFG->defaulthomepage == HOMEPAGE_MY && !empty($CFG->enabledashboard)) {
                    redirect($CFG->wwwroot . '/my/');
                }
                redirect($homepage);
            }
            if ($CFG->defaulthomepage == HOMEPAGE_MY && !empty($CFG->enabledashboard)) {
                redirect($CFG->wwwroot . '/my/');
            }
            if ($CFG->defaulthomepage == HOMEPAGE_MYCOURSES) {
                redirect($CFG->wwwroot . '/my/courses.php');
            }
            if ($CFG->defaulthomepage == HOMEPAGE_USER) {
                $preference = get_user_preferences('user_home_page_preference', get_default_home_page());
                if ($preference == HOMEPAGE_MY && !empty($CFG->enabledashboard)) {
                    redirect($CFG->wwwroot . '/my/');
                }
                if ($preference == HOMEPAGE_MYCOURSES) {
                    redirect($CFG->wwwroot . '/my/courses.php');
                }
            }
            redirect($homepage);
        }

        // NOTE: do NOT use redirect() in hook observers on any other pages than /index.php !!!

        if (!isloggedin()) {
            $CFG->defaulthomepage = $homepagelocal;
        } else if (isguestuser()) {
            if (empty($CFG->allowguestmymoodle) || $CFG->defaulthomepage != HOMEPAGE_MY) {
                $CFG->defaulthomepage = $homepagelocal;
            }
        } else if ($CFG->defaulthomepage == HOMEPAGE_SITE) {
            $CFG->defaulthomepage = $homepagelocal;
        } else if ($CFG->defaulthomepage == HOMEPAGE_USER) {
            $preference = get_user_preferences('user_home_page_preference', get_default_home_page());
            if ($preference == HOMEPAGE_SITE) {
                $CFG->defaulthomepage = $homepagelocal;
            }
        }
    }

    /**
     * Primary menu integration hook.
     *
     * @param \core\hook\navigation\primary_extend $hook
     * @return void
     */
    public static function hook_primary_extend(\core\hook\navigation\primary_extend $hook): void {
        if (!is_callable([\tool_mulib\local\mulib::class, 'is_muhome_active'])) {
            return;
        }

        if (!\tool_mulib\local\mulib::is_muhome_active()) {
            return;
        }

        $replacehome = get_config('tool_muhome', 'replacehome');
        $addmenu = get_config('tool_muhome', 'addmenu');

        if (!$replacehome && ($addmenu === false || trim($addmenu) === '')) {
            return;
        }

        $mypages = page::get_my_pages(true);
        if (!$mypages) {
            return;
        }

        $primary = $hook->get_primaryview();

        // Find key right after 'Home' menu.
        $keys = $primary->get_children_key_list();
        $firstkey = null;
        $beforekey = null;
        $homefound = false;
        foreach ($keys as $key) {
            if ($firstkey === null) {
                $firstkey = $key;
            }
            if ($key === 'home') {
                $homefound = true;
                continue;
            }
            if ($homefound) {
                $beforekey = $key;
                break;
            }
        }
        if (!$homefound) {
            $beforekey = $firstkey;
        }

        if ($replacehome) {
            // Ignore the homepage name and use standard "Home" instead.
            $pagename = get_string('home');
            $node = \navigation_node::create(
                $pagename,
                page::get_url(null),
                $primary::TYPE_CUSTOM,
                $pagename,
                'tool_muhome_home'
            );
            $primary->add_node($node, $beforekey);

            if ($homefound) {
                $home = $primary->find('home', null);
                $home->remove();
            }
            // Do not add the new homepage to "Pages" menu.
            $firstpageid = array_key_first($mypages);
            unset($mypages[$firstpageid]);
        }

        if (!$mypages || $addmenu === false || trim($addmenu) === '') {
            return;
        }

        $pagesname = format_string($addmenu);
        $pagesnode = \navigation_node::create(
            $pagesname,
            null,
            $primary::TYPE_CUSTOM,
            $pagesname,
            'tool_muhome_pages'
        );
        foreach ($mypages as $pageid => $pagename) {
            $pagenode = \navigation_node::create(
                $pagename,
                page::get_url($pageid),
                $primary::TYPE_CUSTOM,
                $pagename,
                'tool_muhome_page_' . $pageid
            );
            $pagesnode->add_node($pagenode);
        }
        $primary->add_node($pagesnode, $beforekey);
    }

    /**
     * Undo default homepage override when user logs in.
     *
     * @param \core\event\user_loggedin $event
     * @return void
     */
    public static function event_user_logged_in(\core\event\user_loggedin $event): void {
        global $CFG, $SESSION;

        if (isset($CFG->tool_muhome_real_defaulthomepage)) {
            // Undo any $CFG->defaulthomepage hackery - user may be logged-in outside of normal login page.
            $CFG->defaulthomepage = $CFG->tool_muhome_real_defaulthomepage;
            unset($CFG->tool_muhome_real_defaulthomepage);
        }

        if (isset($SESSION->wantsurl) && $SESSION->wantsurl === "$CFG->wwwroot/admin/tool/muhome/") {
            if (get_config('tool_muhome', 'replacehome')) {
                // Go via the /index.php page to get correct redirect to preferred user start page.
                $SESSION->wantsurl = "$CFG->wwwroot/";
            }
        }
    }
}
