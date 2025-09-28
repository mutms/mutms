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
 * Program content import interface.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
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
$fromprogram = optional_param('fromprogram', 0, PARAM_INT);

require_login();

$targetprogram = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($targetprogram->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_content_import.php', ['id' => $targetprogram->id, 'fromprogram' => $fromprogram]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $targetprogram->id]);

if ($targetprogram->archived) {
    redirect($returnurl);
}

$top = program::load_content($targetprogram->id);

$form = null;
if (!$fromprogram) {
    $form = new \tool_muprog\local\form\program_content_import(
        null,
        ['targetprogram' => $targetprogram, 'context' => $context]
    );
    if ($form->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $form->get_data()) {
        $fromprogram = $data->fromprogram;
        unset($data);
        $form = null;
    }
}

if (!$form) {
    $form = new \tool_muprog\local\form\program_content_import_confirmation(
        null,
        ['id' => $targetprogram->id, 'contextid' => $context->id, 'fromprogram' => $fromprogram]
    );

    if ($form->is_cancelled()) {
        redirect($returnurl);
    }

    if ($data = $form->get_data()) {
        $from = $DB->get_record('tool_muprog_program', ['id' => $data->fromprogram], '*', MUST_EXIST);
        $top->content_import($data);
        $form->ajax_form_submitted($returnurl);
    }
}

$form->ajax_form_render();
