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
 * Create certification.
 *
 * @package    tool_mucertify
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucertify\local\certification;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid);

require_login();
require_capability('tool/mucertify:edit', $context);

if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
    throw new moodle_exception('invalidcontext');
}

$currenturl = new \core\url('/admin/tool/mucertify/management/certification_create.php', ['contextid' => $context->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$certification = new stdClass();
$certification->contextid = $context->id;
$certification->fullname = '';
$certification->idnumber = '';
$certification->description = '';
$certification->descriptionformat = FORMAT_HTML;

$editoroptions = certification::get_description_editor_options();

$form = new \tool_mucertify\local\form\certification_create(null, ['data' => $certification, 'editoroptions' => $editoroptions, 'context' => $context]);

if ($form->is_cancelled()) {
    redirect(new \core\url('/admin/tool/mucertify/management/index.php', ['contextid' => $context->id]));
}

if ($data = $form->get_data()) {
    $certification = certification::create($data);
    $returnurl = new \core\url('/admin/tool/mucertify/management/certification_settings.php', ['id' => $certification->id]);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
