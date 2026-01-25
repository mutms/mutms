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
 * Disassociate user from tenant
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');

$tenantid = required_param('tenantid', PARAM_INT);
$userid = required_param('id', PARAM_INT);

require_login();

if (!tenancy::is_active()) {
    redirect(new \core\url('/'));
}

$tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);

$context = context_tenant::instance($tenant->id);
require_capability('tool/mutenancy:view', $context);

if (!$tenant->assoccohortid) {
    throw new \core\exception\invalid_parameter_exception('tenant does not have associated cohort');
}
$cohort = $DB->get_record('cohort', ['id' => $tenant->assoccohortid], '*', MUST_EXIST);
if ($cohort->component) {
    throw new \core\exception\invalid_parameter_exception('Associate cohort cannot belong to any component');
}
$cohortcontext = \context::instance_by_id($cohort->contextid);
require_capability('moodle/cohort:assign', $cohortcontext);

$user = $DB->get_record('user', ['id' => $userid, 'tenantid' => null]);

$PAGE->set_url('/admin/tool/mutenancy/management/associate_remove.php', ['tenantid' => $tenant->id, 'id' => $user->id]);
$PAGE->set_context($context);

$returnurl = new \core\url('/admin/tool/mutenancy/tenant_users.php', ['id' => $tenant->id]);

if (!$DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $user->id])) {
    redirect($returnurl);
}

$form = new \tool_mutenancy\local\form\associate_remove(null, ['tenant' => $tenant, 'cohort' => $cohort, 'user' => $user]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    cohort_remove_member($cohort->id, $user->id);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
