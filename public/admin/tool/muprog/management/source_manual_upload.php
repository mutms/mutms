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
 * Uploads user allocations to program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\source\manual;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);
$draftitemid = optional_param('csvfile', null, PARAM_INT);

require_login();

$source = $DB->get_record('tool_muprog_source', ['id' => $sourceid, 'type' => 'manual'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:allocate', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/source_manual_upload.php', ['sourceid' => $source->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new moodle_url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);

if (!manual::is_allocation_possible($program, $source)) {
    redirect($returnurl);
}

$filedata = null;
if ($draftitemid && confirm_sesskey()) {
    $filedata = \tool_muprog\local\util::get_uploaded_data($draftitemid);
}

if (!$filedata) {
    $form = new \tool_muprog\local\form\source_manual_upload_file(null, ['program' => $program, 'source' => $source, 'context' => $context]);
} else {
    $form = new \tool_muprog\local\form\source_manual_upload_options(null, ['program' => $program,
        'source' => $source, 'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
}

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    if ($filedata && $form instanceof \tool_muprog\local\form\source_manual_upload_options) {
        $result = manual::process_uploaded_data($data, $filedata);

        if ($result['assigned']) {
            $message = get_string('source_manual_result_assigned', 'tool_muprog', $result['assigned']);
            \core\notification::add($message, \core\output\notification::NOTIFY_SUCCESS);
        }
        if ($result['skipped']) {
            $message = get_string('source_manual_result_skipped', 'tool_muprog', $result['skipped']);
            \core\notification::add($message, \core\output\notification::NOTIFY_INFO);
        }
        if ($result['errors']) {
            $message = get_string('source_manual_result_errors', 'tool_muprog', $result['errors']);
            \core\notification::add($message, \core\output\notification::NOTIFY_WARNING);
        }

        $form->ajax_form_submitted($returnurl);
    }
    if (!$filedata && $form instanceof \tool_muprog\local\form\source_manual_upload_file) {
        $filedata = \tool_muprog\local\util::get_uploaded_data($draftitemid);
        if ($filedata) {
            $form = new \tool_muprog\local\form\source_manual_upload_options(null, ['program' => $program,
                'source' => $source, 'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
        }
    }
}

$form->ajax_form_render();
