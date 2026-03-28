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
 * Tenant members.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;

/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

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
$PAGE->set_url('/admin/tool/mutenancy/tenant_users.php', ['id' => $tenant->id]);

/** @var \tool_mutenancy\output\tenant_users\renderer $output */
$output = $PAGE->get_renderer('tool_mutenancy', 'tenant_users');

$output->setup_page($tenant);

$buttons = [];

if ($tenant->assoccohortid) {
    $acohort = $DB->get_record('cohort', ['id' => $tenant->assoccohortid]);
    if ($acohort && !$acohort->component) {
        $acohortcontext = context::instance_by_id($acohort->contextid);
        if (has_capability('moodle/cohort:assign', $acohortcontext)) {
            $url = new \core\url('/admin/tool/mutenancy/management/associate_add.php', ['tenantid' => $tenant->id]);
            $button = new tool_mulib\output\ajax_form\button($url, get_string('associate_add', 'tool_mutenancy'));
            $buttons[] = $OUTPUT->render($button);
        }
    }
}

if (has_capability('tool/mutenancy:membercreate', $context)) {
    $limitreached = false;
    if ($tenant->memberlimit) {
        $mcount = $DB->count_records('user', ['deleted' => 0, 'tenantid' => $tenant->id]);
        if ($mcount >= $tenant->memberlimit) {
            $limitreached = true;
        }
    }

    if (!$limitreached) {
        $url = new \core\url('/admin/tool/mutenancy/management/member_create.php', ['tenantid' => $tenant->id]);
        $button = new tool_mulib\output\ajax_form\button($url, get_string('member_create', 'tool_mutenancy'));
        $button->set_form_size('xl');
        $buttons[] = $OUTPUT->render($button);
    }
}

if ($buttons) {
    $PAGE->add_header_action('<div class="buttons">' . implode(' ', $buttons) . '</div>');
}

echo $OUTPUT->header();

echo $output->render_section($tenant);

echo $OUTPUT->footer();
