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

namespace tool_murelation\local;

use moodle_url, stdClass;

/**
 * Framework management helper.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Hook callback for fetching of default tenant manager capabilities.
     *
     * @param \tool_mutenancy\hook\tenant_manager_capabilities $hook
     * @return void
     */
    public static function callback_tenant_manager_capabilities(\tool_mutenancy\hook\tenant_manager_capabilities $hook): void {
        $hook->add_capability('tool/murelation:viewpositions', CAP_ALLOW);
        $hook->add_capability('tool/murelation:managepositions', CAP_ALLOW);
    }

    /**
     * Set up $PAGE for list of frameworks UI.
     *
     * @param moodle_url $pageurl
     * @return void
     */
    public static function setup_index_page(moodle_url $pageurl): void {
        global $PAGE;

        $title = get_string('frameworks', 'tool_murelation');

        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url($pageurl);
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $PAGE->set_secondary_navigation(false);
    }

    /**
     * Set up $PAGE for framework UI.
     *
     * @param moodle_url $pageurl
     * @param stdClass $framework
     * @param string $secondarytab
     * @return void
     */
    public static function setup_framework_page(moodle_url $pageurl, stdClass $framework, string $secondarytab): void {
        global $PAGE;
        $PAGE->set_pagelayout('admin');
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('frameworks', 'tool_murelation'));
        $PAGE->set_heading(format_string($framework->name));

        $secondarynav = new \tool_murelation\navigation\views\framework_secondary($PAGE, $framework);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($secondarytab);
        $secondarynav->initialise();

        $url = new moodle_url('/admin/tool/murelation/management/index.php');
        $PAGE->navbar->add(get_string('frameworks', 'tool_murelation'), $url);
        $url = new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]);
        $PAGE->navbar->add(format_string($framework->name), $url);
    }
}
