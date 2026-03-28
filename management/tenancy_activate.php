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
 * Multi-tenancy activation.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');

require_login();
$syscontext = context_system::instance();
require_capability('moodle/site:config', $syscontext);

$returnurl = new \core\url('/admin/tool/mutenancy/index.php');

if (tenancy::is_active()) {
    throw new \core\exception\invalid_parameter_exception('Multi-tenancy is already active');
}

$PAGE->set_url('/admin/tool/mutenancy/management/tenancy_activate.php');
$PAGE->set_context($syscontext);

$form = new \tool_mutenancy\local\form\tenancy_activate();

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tenancy::activate();
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
