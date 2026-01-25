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
 * Add a new credit framework.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutrain\local\framework;
use tool_mutrain\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid);

require_login();
require_capability('tool/mutrain:manageframeworks', $context);

$currenturl = new core\url('/admin/tool/mutrain/management/framework_create.php', ['contextid' => $context->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new core\url('/admin/tool/mutrain/management/index.php', ['contextid' => $context->id]);

$framework = new \stdClass();
$framework->contextid = $context->id;
$framework->name = '';
$framework->idnumber = '';
$framework->description = '';
$framework->descriptionformat = FORMAT_HTML;
$framework->restrictafter = null;
$framework->restrictcontext = 0;
$framework->publicaccess = 1; // Not visible until fields are added and users obtain credits.

$editoroptions = framework::get_description_editor_options();

$form = new \tool_mutrain\local\form\framework_create(null, ['data' => $framework, 'editoroptions' => $editoroptions, 'context' => $context]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    framework::create((array)$data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
