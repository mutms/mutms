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
 * Update program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\program;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_update.php', ['id' => $program->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$editoroptions = program::get_description_editor_options($context->id);
$program = file_prepare_standard_editor(
    $program,
    'description',
    $editoroptions,
    $context,
    'tool_muprog',
    'description',
    $program->id
);
$program->tags = core_tag_tag::get_item_tags_array('tool_muprog', 'tool_muprog_program', $program->id);

$program->image = file_get_submitted_draft_itemid('image');
file_prepare_draft_area($program->image, $context->id, 'tool_muprog', 'image', $program->id, ['subdirs' => 0]);

$form = new \tool_muprog\local\form\program_update(null, ['data' => $program, 'editoroptions' => $editoroptions]);

$returnurl = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $program = program::update_general($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
