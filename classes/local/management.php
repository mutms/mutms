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

namespace tool_mutrain\local;

use tool_mutrain\local\util;
use core\url, stdClass;

/**
 * Training management helper.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Guess if user can access framework management UI.
     *
     * @return url|null
     */
    public static function get_management_url(): ?url {
        if (isguestuser() || !isloggedin()) {
            return null;
        }

        // NOTE: this has to be very fast, do NOT loop all categories here!

        if (has_capability('tool/mutrain:viewframeworks', \context_system::instance())) {
            return new url('/admin/tool/mutrain/management/index.php');
        } else if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
                if ($tenant) {
                    $catcontext = \context_coursecat::instance($tenant->categoryid);
                    if (has_capability('tool/mutrain:viewframeworks', $catcontext)) {
                        return new url('/admin/tool/mutrain/management/index.php', ['contextid' => $catcontext->id]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Returns framework query data.
     *
     * @param \context|null $context
     * @param string $search
     * @param string $tablealias
     * @return array
     */
    public static function get_framework_search_query(?\context $context, string $search, string $tablealias = ''): array {
        global $DB;

        if ($tablealias !== '' && substr($tablealias, -1) !== '.') {
            $tablealias .= '.';
        }

        $conditions = [];
        $params = [];

        if ($context) {
            $contextselect = 'AND ' . $tablealias . 'contextid = :frwcontextid';
            $params['frwcontextid'] = $context->id;
        } else {
            $contextselect = '';
        }

        if (trim($search) !== '') {
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';
            $fields = ['name', 'idnumber', 'description'];
            $cnt = 0;
            foreach ($fields as $field) {
                $conditions[] = $DB->sql_like($tablealias . $field, ':frwsearch' . $cnt, false);
                $params['frwsearch' . $cnt] = $searchparam;
                $cnt++;
            }
        }

        if ($conditions) {
            $sql = '(' . implode(' OR ', $conditions) . ') ' . $contextselect;
            return [$sql, $params];
        } else {
            return ['1=1 ' . $contextselect, $params];
        }
    }

    /**
     * Set up $PAGE for framework management UI.
     *
     * @param url $pageurl
     * @param \context $context
     * @return void
     */
    public static function setup_index_page(url $pageurl, \context $context): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('management_frameworks', 'tool_mutrain'));
        $PAGE->set_heading(get_string('frameworks', 'tool_mutrain'));
        $PAGE->set_secondary_navigation(false);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('frameworks', 'tool_mutrain');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/mutrain:viewframeworks', $parentcontext)) {
                $url = new url('/admin/tool/mutrain/management/index.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }
    }

    /**
     * Set up $PAGE for framework management UI.
     *
     * @param url $pageurl
     * @param \context $context
     * @param stdClass $framework
     * @return void
     */
    public static function setup_framework_page(url $pageurl, \context $context, stdClass $framework): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);

        $frameworkname = format_string($framework->name);

        $PAGE->set_title($frameworkname . \moodle_page::TITLE_SEPARATOR . get_string('management_frameworks', 'tool_mutrain'));
        $PAGE->set_heading(format_string($framework->name));
        $PAGE->set_secondary_navigation(false);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('frameworks', 'tool_mutrain');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/mutrain:viewframeworks', $parentcontext)) {
                $url = new url('/admin/tool/mutrain/management/index.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $PAGE->navbar->add($frameworkname);
    }
}
