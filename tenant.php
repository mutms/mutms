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

/**
 * Tenant details.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;
use tool_mulib\output\header_actions;

/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var stdClass $USER */

require(__DIR__ . '/../../../config.php');
require_once("$CFG->libdir/adminlib.php");

$tenantid = required_param('id', PARAM_INT);

require_login();

if (!tenancy::is_active()) {
    redirect('/admin/tool/mutenancy/index.php');
}

$tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
$context = context_tenant::instance($tenant->id);
require_capability('tool/mutenancy:view', $context);

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/mutenancy/tenant.php', ['id' => $tenant->id]);

/** @var \tool_mutenancy\output\tenant\renderer $output */
$output = $PAGE->get_renderer('tool_mutenancy', 'tenant');

$actions = new header_actions(get_string('tenant_actions', 'tool_mutenancy'));
if (get_assignable_roles($context, ROLENAME_ORIGINAL, false)) {
    $actions->get_dropdown()->add_item(
        get_string('assignroles', 'role'),
        new moodle_url('/admin/roles/assign.php', ['contextid' => $context->id])
    );
}
if (has_capability('moodle/role:review', $context) || get_overridable_roles($context)) {
    $actions->get_dropdown()->add_item(
        get_string('permissions', 'role'),
        new moodle_url('/admin/roles/permissions.php', ['contextid' => $context->id])
    );
}
if (has_any_capability(['moodle/role:assign', 'moodle/role:safeoverride', 'moodle/role:override'], $context)) {
    $actions->get_dropdown()->add_item(
        get_string('checkpermissions', 'role'),
        new moodle_url('/admin/roles/check.php', ['contextid' => $context->id])
    );
}
if ($actions->has_items()) {
    $PAGE->add_header_action($output->render($actions));
}

$output->setup_page($tenant);

echo $output->header();

echo $output->render_section($tenant);

$buttons = [];

if (has_capability('tool/mutenancy:admin', $context)) {
    $membercount = $DB->record_exists('user', ['tenantid' => $tenant->id, 'deleted' => 0]);

    if (
        $tenant->archived && $USER->tenantid != $tenant->id
        && (!$membercount || has_capability('tool/mutenancy:allocate', context_system::instance()))
    ) {
        $url = new moodle_url('/admin/tool/mutenancy/management/tenant_delete.php', ['id' => $tenant->id]);
        $button = new tool_mulib\output\ajax_form\button($url, tenancy::get_tenant_string('tenant_delete'));
        $button->set_submitted_action($button::SUBMITTED_ACTION_REDIRECT);
        $buttons[] = $output->render($button);
    }

    $url = new moodle_url('/admin/tool/mutenancy/management/tenant_update.php', ['id' => $tenant->id]);
    $button = new tool_mulib\output\ajax_form\button($url, tenancy::get_tenant_string('tenant_update'));
    $buttons[] = $output->render($button);
}

if ($buttons) {
    echo '<div class="buttons">' . implode(' ', $buttons) . '</div>';
}

echo $output->footer();
