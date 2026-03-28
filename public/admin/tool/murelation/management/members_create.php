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
 * Add team member(s).
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_murelation\local\subordinate;
use tool_murelation\local\framework;
use tool_murelation\local\uimode_teams;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$supervisorid = required_param('supervisorid', PARAM_INT);

require_login();

$supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $supervisorid], '*', MUST_EXIST);
$framework = $DB->get_record('tool_murelation_framework', ['id' => $supervisor->frameworkid], '*', MUST_EXIST);
if ($framework->uimode != framework::UIMODE_TEAMS) {
    redirect(new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]));
}
$context = uimode_teams::get_team_context($framework, $supervisor);

$currenturl = new moodle_url('/admin/tool/murelation/management/members_create.php', ['supervisorid' => $supervisorid]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new moodle_url('/admin/tool/murelation/management/team.php', ['id' => $supervisor->id]);

if (!uimode_teams::can_manage_members($framework, $supervisor)) {
    redirect($returnurl);
}

$form = new \tool_murelation\local\form\members_create(
    null,
    ['supervisor' => $supervisor, 'framework' => $framework, 'context' => $context]
);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    uimode_teams::members_create($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
