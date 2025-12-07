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
 * Switch tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\tenant;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

require_login();

if (!tenancy::is_active()) {
    redirect(new moodle_url('/'));
}

$context = context_system::instance();
$PAGE->set_url('/admin/tool/mutenancy/tenant_switch.php');
$PAGE->set_context($context);

$returnurl = new moodle_url('/');

if (!tenancy::can_switch()) {
    redirect($returnurl);
}

$form = new \tool_mutenancy\local\form\tenant_switch(null, []);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tenancy::switch($data->tenantid);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
