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

use moodle_url, stdClass;
use tool_mulib\local\mulib;

/**
 * Certification management helper.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Guess if user can access certification management UI.
     *
     * @return moodle_url|null
     */
    public static function get_management_url(): ?moodle_url {
        if (isguestuser() || !isloggedin()) {
            return null;
        }

        // NOTE: this has to be very fast, do NOT loop all categories here!

        if (has_capability('tool/mucertify:view', \context_system::instance())) {
            return new moodle_url('/admin/tool/mucertify/management/index.php');
        } else if (mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
                if ($tenant) {
                    $catcontext = \context_coursecat::instance($tenant->categoryid);
                    if (has_capability('tool/mucertify:view', $catcontext)) {
                        return new moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $catcontext->id]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Fetch cohorts that allow certification visibility.
     *
     * @param int $certificationid
     * @return array
     */
    public static function fetch_current_cohorts_menu(int $certificationid): array {
        global $DB;

        $sql = "SELECT c.id, c.name
                  FROM {cohort} c
                  JOIN {tool_mucertify_cohort} pc ON c.id = pc.cohortid
                 WHERE pc.certificationid = :certificationid
              ORDER BY c.name ASC, c.id ASC";
        $params = ['certificationid' => $certificationid];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Set up $PAGE for certification management UI.
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
        $PAGE->set_title(get_string('certifications', 'tool_mucertify'));
        $PAGE->set_heading(get_string('certifications', 'tool_mucertify'));
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
            if (has_capability('tool/mucertify:view', $context)) {
                $url = new moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
    }

    /**
     * Set up $PAGE for certification management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @param stdClass $certification
     * @param string $secondarytab
     * @return void
     */
    public static function setup_certification_page(\moodle_url $pageurl, \context $context, stdClass $certification, string $secondarytab): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('certifications', 'tool_mucertify'));
        $PAGE->set_heading(format_string($certification->fullname));

        $secondarynav = new \tool_mucertify\navigation\views\certification_secondary($PAGE, $certification);
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
            if (has_capability('tool/mucertify:view', $context)) {
                $url = new moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
        $PAGE->navbar->add(format_string($certification->fullname));
    }
}
