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

namespace tool_muprog\local;

use tool_muprog\local\content\course;
use tool_muprog\local\content\item;
use tool_muprog\local\content\set;
use tool_muprog\local\content\top;
use moodle_url, stdClass;
use tool_mulib\local\sql;
use tool_mulib\local\mulib;

/**
 * Program management helper.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Guess if user can access programs management UI.
     *
     * @return moodle_url|null
     */
    public static function get_management_url(): ?moodle_url {
        if (isguestuser() || !isloggedin()) {
            return null;
        }

        // NOTE: this has to be very fast, do NOT loop all categories here!

        if (has_capability('tool/muprog:view', \context_system::instance())) {
            return new moodle_url('/admin/tool/muprog/management/index.php');
        } else if (mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
                if ($tenant) {
                    $catcontext = \context_coursecat::instance($tenant->categoryid);
                    if (has_capability('tool/muprog:view', $catcontext)) {
                        return new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $catcontext->id]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Returns program query data.
     *
     * @param \context|null $context
     * @param string $search
     * @param string $tablealias
     * @return sql
     */
    public static function get_program_search_query(?\context $context, string $search, string $tablealias = ''): sql {
        global $DB;

        if ($tablealias !== '' && !str_ends_with($tablealias, '.')) {
            $tablealias .= '.';
        }

        $conditions = [];
        if (trim($search) !== '') {
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';
            $fields = ['fullname', 'idnumber', 'description'];
            foreach ($fields as $field) {
                $conditions[] = new sql($DB->sql_like($tablealias . $field, '?', false), [$searchparam]);
            }
        }

        if ($conditions) {
            $sql = sql::join(' OR ', $conditions)->wrap('(', ')');
        } else {
            $sql = new sql('');
        }

        if ($context) {
            $contextselect = new sql($tablealias . 'contextid = ?', [$context->id]);
            $sql = sql::join(' AND ', [$sql, $contextselect]);
        }

        return $sql;
    }

    /**
     * Fetch cohorts that allow program visibility.
     *
     * @param int $programid
     * @return array
     */
    public static function fetch_current_cohorts_menu(int $programid): array {
        global $DB;

        $sql = "SELECT c.id, c.name
                  FROM {cohort} c
                  JOIN {tool_muprog_cohort} pc ON c.id = pc.cohortid
                 WHERE pc.programid = :programid
              ORDER BY c.name ASC, c.id ASC";
        $params = ['programid' => $programid];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Set up $PAGE for programs management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @return void
     */
    public static function setup_index_page(\moodle_url $pageurl, \context $context): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('programs', 'tool_muprog'));
        $PAGE->set_heading(get_string('programs', 'tool_muprog'));
        $PAGE->set_secondary_navigation(false);

        $contexts = [];
        while (true) {
            $contexts[] = $context;
            $parent = $context->get_parent_context();
            if (!$parent) {
                break;
            }
            $context = $parent;
        }

        $contexts = array_reverse($contexts);

        /** @var \context $context */
        foreach ($contexts as $context) {
            $url = null;
            if (has_capability('tool/muprog:view', $context)) {
                $url = new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
    }

    /**
     * Set up $PAGE for programs management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @param stdClass $program
     * @param string $secondarytab
     * @return void
     */
    public static function setup_program_page(moodle_url $pageurl, \context $context, stdClass $program, string $secondarytab): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('programs', 'tool_muprog'));
        $PAGE->set_heading(format_string($program->fullname));

        $secondarynav = new \tool_muprog\navigation\views\program_secondary($PAGE, $program);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($secondarytab);
        $secondarynav->initialise();

        $contexts = [];
        while (true) {
            $contexts[] = $context;
            $parent = $context->get_parent_context();
            if (!$parent) {
                break;
            }
            $context = $parent;
        }

        $contexts = array_reverse($contexts);

        /** @var \context $context */
        foreach ($contexts as $context) {
            $url = null;
            if (has_capability('tool/muprog:view', $context)) {
                $url = new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
        $PAGE->navbar->add(format_string($program->fullname));
    }
}
