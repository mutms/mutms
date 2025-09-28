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
 * Program management interface - certificate editing.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

if (!\tool_muprog\local\certificate::is_available()) {
    redirect(new moodle_url('/admin/tool/muprog/program.php', ['id' => $program->id]));
}

$currenturl = new moodle_url('/admin/tool/muprog/management/program_certificate_edit.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$cert = $DB->get_record('tool_muprog_cert', ['programid' => $program->id]);

$current = new stdClass();
$current->id = $program->id;

if ($cert) {
    $current->templateid = $cert->templateid;
    $current->expirydatetype = $cert->expirydatetype;
    if ($cert->expirydatetype == 1) {
        $current->expirydateabsolute = $cert->expirydateoffset;
        $current->expirydaterelative = null;
    } else if ($cert->expirydatetype == 2) {
        $current->expirydateabsolute = null;
        $current->expirydaterelative = $cert->expirydateoffset;
    } else {
        $current->expirydatetype = 0;
        $current->expirydaterelative = null;
        $current->expirydateabsolute = null;
    }
    $current->existing = true;
} else {
    $current->templateid = null;
    $current->expirydatetype = 0;
    $current->expirydaterelative = null;
    $current->expirydateabsolute = null;
    $current->existing = false;
}

$form = new \tool_muprog\local\form\program_certificate_edit(null, ['data' => $current, 'context' => $context]);

$returnurl = new moodle_url('/admin/tool/muprog/management/program_certificate.php', ['id' => $program->id]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    \tool_muprog\local\certificate::update_program_certificate((array)$data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
