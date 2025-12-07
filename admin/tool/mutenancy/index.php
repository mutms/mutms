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
 * List of all tenants.
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

require_login();
admin_externalpage_setup('tool_mutenancy_tenants', '', null, '', ['nosearch' => true]);

$syscontext = context_system::instance();
$tenantcount = $DB->count_records('tool_mutenancy_tenant', []);

$PAGE->set_heading(tenancy::get_tenants_string('tenants'));

if (!tenancy::is_active()) {
    echo $OUTPUT->header();
    $url = new moodle_url('/admin/tool/mutenancy/management/tenancy_activate.php');
    $button = new \tool_mulib\output\ajax_form\button($url, get_string('tenancy_activate', 'tool_mutenancy'), true);
    $button->set_form_size('sm');
    echo '<div class="buttons">' . $OUTPUT->render($button) . '</div>';
    echo $OUTPUT->footer();
    die;
}

$PAGE->set_secondary_navigation(false);

if (has_capability('tool/mutenancy:admin', $syscontext)) {
    $tenantlimit = get_config('tool_mutenancy', 'tenantlimit');
    $notenantsyet = !$DB->record_exists('tool_mutenancy_tenant', []);
    if (!$tenantlimit || $tenantlimit > $DB->count_records('tool_mutenancy_tenant', [])) {
        $url = new moodle_url('/admin/tool/mutenancy/management/tenant_create.php');
        $button = new \tool_mulib\output\ajax_form\button(
            $url,
            tenancy::get_tenant_string('tenant_create'),
            $notenantsyet
        );
        $button->set_submitted_action($button::SUBMITTED_ACTION_REDIRECT);
        $PAGE->add_header_action($OUTPUT->render($button));
    }
}

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_mutenancy\reportbuilder\local\systemreports\tenants::class,
    context_system::instance()
);
echo $report->output();

$buttons = [];

if (!$tenantcount && has_capability('moodle/site:config', $syscontext)) {
    $url = new moodle_url('/admin/tool/mutenancy/management/tenancy_deactivate.php');
    $button = new \tool_mulib\output\ajax_form\button($url, get_string('tenancy_deactivate', 'tool_mutenancy'));
    $button->set_form_size('sm');
    $buttons[] = $OUTPUT->render($button);
}

if ($buttons) {
    echo '<div class="buttons">' . implode(' ', $buttons) . '</div>';
}

echo $OUTPUT->footer();
