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
 * Delete credit framework.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutrain\local\framework;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$framework = $DB->get_record('tool_mutrain_framework', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($framework->contextid);
require_capability('tool/mutrain:manageframeworks', $context);

$currenturl = new core\url('/admin/tool/mutrain/management/framework_delete.php', ['id' => $framework->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new core\url('/admin/tool/mutrain/management/index.php', ['contextid' => $context->id]);

if (!framework::is_deletable($framework->id)) {
    // We should not get here.
    redirect($returnurl, get_string('error'));
}

$data = clone($framework);

$form = new \tool_mutrain\local\form\framework_delete(null, ['data' => $data]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    framework::delete($data->id);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
