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
 * Framework teams members.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_murelation\local\framework;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require_once('../../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

require_login();
require_capability('tool/murelation:viewpositions', $syscontext);

$framework = $DB->get_record('tool_murelation_framework', ['id' => $id], '*', MUST_EXIST);

if ($framework->uimode != framework::UIMODE_TEAMS) {
    redirect(new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]));
}

$pageurl = new \moodle_url('/admin/tool/murelation/management/framework_members.php', ['id' => $framework->id]);

\tool_murelation\local\management::setup_framework_page($pageurl, $framework, 'framework_members');
$title = format_string($framework->subordinatestitle);
$PAGE->navbar->add($title);

/** @var \tool_murelation\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_murelation', 'management');

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_murelation\reportbuilder\local\systemreports\framework_members::class,
    $syscontext,
    parameters:['frameworkid' => $framework->id]
);
echo $report->output();

echo $OUTPUT->footer();
