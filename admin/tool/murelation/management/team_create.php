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
 * Create a team.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_murelation\local\framework;
use tool_murelation\local\uimode_teams;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$frameworkid = required_param('frameworkid', PARAM_INT);

require_login();

$context = context_system::instance();
$tenantid = null;
if (\tool_mulib\local\mulib::is_mutenancy_active()) {
    // NOTE: picking tenant here would be tricky, make them switch tenants for now.
    $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
    if ($tenantid) {
        $context = context_tenant::instance($tenantid);
    }
}

require_capability('tool/murelation:managepositions', $context);

$currenturl = new moodle_url('/admin/tool/murelation/management/team_create.php', ['frameworkid' => $frameworkid]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$framework = $DB->get_record('tool_murelation_framework', ['id' => $frameworkid], '*', MUST_EXIST);
if ($framework->uimode != framework::UIMODE_TEAMS) {
    redirect(new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]));
}

$returnurl = new moodle_url('/admin/tool/murelation/management/framework_teams.php', ['id' => $framework->id]);

if (!uimode_teams::can_create_team($framework, $context)) {
    redirect($returnurl);
}

$form = new \tool_murelation\local\form\team_create(
    null,
    ['framework' => $framework, 'tenantid' => $tenantid, 'context' => $context]
);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $data->tenantid = $tenantid;
    $supervisor = uimode_teams::team_create($data);
    $returnurl = new moodle_url('/admin/tool/murelation/management/team.php', ['id' => $supervisor->id]);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
