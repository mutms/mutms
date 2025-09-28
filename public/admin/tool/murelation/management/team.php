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
 * Team.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_murelation\local\framework;
use tool_murelation\local\uimode_teams;
use tool_mulib\output\header_actions;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require_once('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $id], '*', MUST_EXIST);
$framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
if ($framework->uimode != framework::UIMODE_TEAMS) {
    redirect(new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]));
}

$context = uimode_teams::get_team_context($framework, $supervisor);
$curretnurl = new \moodle_url('/admin/tool/murelation/management/team.php', ['id' => $supervisor->id]);

require_capability('tool/murelation:viewpositions', $context);
$PAGE->set_context($context);
$PAGE->set_url($curretnurl);

$teamname = format_string($supervisor->teamname);

$PAGE->set_secondary_navigation(false);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('team', 'tool_murelation'));
$PAGE->set_heading($teamname);

$syscontext = \context_system::instance();

if (has_capability('tool/murelation:viewframeworks', $syscontext)) {
    $url = new moodle_url('/admin/tool/murelation/management/index.php');
    $PAGE->navbar->add(get_string('frameworks', 'tool_murelation'), $url);

    $url = new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]);
    $PAGE->navbar->add(format_string($framework->name), $url);

    $url = new moodle_url('/admin/tool/murelation/management/framework_teams.php', ['id' => $framework->id]);
    $PAGE->navbar->add(get_string('teams', 'tool_murelation'), $url);
} else {
    $url = null;
}
$PAGE->navbar->add($teamname, $url);

/** @var \tool_murelation\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_murelation', 'management');

$supervisortitle = format_string($framework->supervisortitle);
$subordinatestitle = format_string($framework->subordinatestitle);

$actions = new header_actions(get_string('management_team_actions', 'tool_murelation'));

if (uimode_teams::can_manage_members($framework, $supervisor)) {
    $limitreached = false;
    if ($supervisor->maxsubordinates) {
        $current = $DB->count_records('tool_murelation_subordinate', ['supervisorid' => $supervisor->id]);
        if ($current >= $supervisor->maxsubordinates) {
            $limitreached = true;
        }
    }
    if (!$limitreached) {
        $url = new \moodle_url('/admin/tool/murelation/management/members_create.php', ['supervisorid' => $supervisor->id]);
        $button = new \tool_mulib\output\ajax_form\button($url, get_string('members_create_a', 'tool_murelation', $subordinatestitle));
        $actions->add_button($button);
    }
}

if (uimode_teams::can_update_team($framework, $supervisor)) {
    $url = new \moodle_url('/admin/tool/murelation/management/team_update.php', ['id' => $supervisor->id]);
    $link = new \tool_mulib\output\ajax_form\link($url, get_string('team_update', 'tool_murelation'), 'i/settings');
    $actions->get_dropdown()->add_ajax_form($link);

    $url = new \moodle_url('/admin/tool/murelation/management/team_delete.php', ['id' => $supervisor->id]);
    $link = new \tool_mulib\output\ajax_form\link($url, get_string('team_delete', 'tool_murelation'), 'i/delete');
    $link->add_class('text-danger');
    $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
    $actions->get_dropdown()->add_ajax_form($link);
}

if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

echo $managementoutput->render_team($framework, $supervisor);

echo $OUTPUT->heading(format_string($framework->subordinatestitle), 2, 'h3');

$report = \core_reportbuilder\system_report_factory::create(
    \tool_murelation\reportbuilder\local\systemreports\team_members::class,
    $context,
    parameters:['supervisorid' => $supervisor->id]
);
echo $report->output();

echo $OUTPUT->footer();
