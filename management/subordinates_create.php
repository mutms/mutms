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
use tool_murelation\local\uimode_supervisors;
use tool_murelation\external\form_autocomplete\subordinates_create_select_supuserid;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$frameworkid = required_param('frameworkid', PARAM_INT);
$supuserid = optional_param('supuserid', 0, PARAM_INT);

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

$currenturl = new moodle_url('/admin/tool/murelation/management/subordinates_create.php', ['frameworkid' => $frameworkid]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$framework = $DB->get_record('tool_murelation_framework', ['id' => $frameworkid], '*', MUST_EXIST);
if ($framework->uimode != framework::UIMODE_SUPERVISORS) {
    redirect(new moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]));
}

$returnurl = new moodle_url('/admin/tool/murelation/management/framework_subordinates.php', ['id' => $framework->id]);

if (!uimode_supervisors::can_bulk_create($framework, $context)) {
    redirect($returnurl);
}

if ($supuserid) {
    $error = subordinates_create_select_supuserid::validate_value(
        $supuserid,
        ['frameworkid' => $framework->id, 'tenantid' => $tenantid],
        $context,
    );
    if ($error !== null) {
        $supuserid = 0;
    }
}

if (!$supuserid) {
    $form = new \tool_murelation\local\form\subordinates_create_select(
        null,
        ['framework' => $framework, 'tenantid' => $tenantid, 'context' => $context]
    );
    if ($form->is_cancelled()) {
        $form->ajax_form_cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        $supuserid = $data->supuserid;
        unset($form);
    } else {
        $form->ajax_form_render();
    }
}

$supuser = $DB->get_record('user', ['id' => $supuserid, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);

$form = new \tool_murelation\local\form\subordinates_create(
    null,
    ['framework' => $framework, 'tenantid' => $tenantid, 'context' => $context, 'supuser' => $supuser]
);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $data->tenantid = $tenantid;
    $supervisor = uimode_supervisors::bulk_create($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
