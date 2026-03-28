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

/**
 * Framework supervisors.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_murelation\local\framework;
use tool_mulib\output\header_actions;
use tool_murelation\local\uimode_teams;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require_once('../../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

require_login();
require_capability('tool/murelation:viewframeworks', $syscontext);

$framework = $DB->get_record('tool_murelation_framework', ['id' => $id], '*', MUST_EXIST);

if ($framework->uimode != framework::UIMODE_TEAMS) {
    redirect(new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]));
}

$pageurl = new \moodle_url('/admin/tool/murelation/management/framework_teams.php', ['id' => $framework->id]);

\tool_murelation\local\management::setup_framework_page($pageurl, $framework, 'framework_teams');
$PAGE->navbar->add(get_string('teams', 'tool_murelation'));

/** @var \tool_murelation\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_murelation', 'management');

$createcontext = context_system::instance();
if (\tool_mulib\local\mulib::is_mutenancy_active()) {
    // For now use current tenant when creating teams.
    $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
    if ($tenantid) {
        $createcontext = context_tenant::instance($tenantid);
    }
}

$actions = new header_actions(get_string('management_framework_actions', 'tool_murelation'));

if (uimode_teams::can_create_team($framework, $createcontext)) {
    $url = new \moodle_url('/admin/tool/murelation/management/team_create.php', ['frameworkid' => $framework->id]);
    $button = new \tool_mulib\output\ajax_form\button($url, get_string('team_create', 'tool_murelation'));
    $button->set_submitted_action($button::SUBMITTED_ACTION_REDIRECT);
    $actions->add_button($button);
}

if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_murelation\reportbuilder\local\systemreports\framework_teams::class,
    $syscontext,
    parameters:['frameworkid' => $framework->id]
);
echo $report->output();

echo $OUTPUT->footer();
