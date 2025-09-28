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
 * Program management interface - certificate awarding.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

if (!\tool_muprog\local\certificate::is_available()) {
    redirect(new moodle_url('/admin/tool/muprog/program.php', ['id' => $program->id]));
}

$currenturl = new moodle_url('/admin/tool/muprog/management/program_certificate.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_certificate');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$cert = $DB->get_record('tool_muprog_cert', ['programid' => $program->id]);

echo $OUTPUT->header();

$buttons = [];
if (has_capability('tool/muprog:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/muprog/management/program_certificate_edit.php', ['id' => $program->id]);
    $editbutton = new tool_mulib\output\ajax_form\button($editurl, get_string('edit'));
    $editbutton->set_modal_title(get_string('certificate', 'tool_certificate'));
    $buttons[] = $OUTPUT->render($editbutton);

    if ($cert) {
        $deleteurl = new moodle_url('/admin/tool/muprog/management/program_certificate_delete.php', ['id' => $program->id]);
        $deletebutton = new tool_mulib\output\ajax_form\button($deleteurl, get_string('delete'));
        $deletebutton->set_modal_title(get_string('certificate', 'tool_certificate'));
        $buttons[] = $OUTPUT->render($deletebutton);
    }
}

$cert = $DB->get_record('tool_muprog_cert', ['programid' => $program->id]);

$details = new \tool_mulib\output\entity_details();

if ($cert) {
    $template = $DB->get_record('tool_certificate_templates', ['id' => $cert->templateid]);
    if (!$template) {
        $details->add(get_string('certificatetemplate', 'tool_certificate'), get_string('error'));
    } else {
        $details->add(get_string('certificatetemplate', 'tool_certificate'), format_string($template->name));
        if ($cert->expirydatetype == 1) {
            $expiry = userdate($cert->expirydateoffset);
        } else if ($cert->expirydatetype == 2) {
            $expiry = format_time($cert->expirydateoffset);
        } else {
            $expiry = get_string('never', 'tool_certificate');
        }
        $details->add(get_string('expirydate', 'tool_certificate'), $expiry);
    }
} else {
    $details->add(get_string('certificatetemplate', 'tool_certificate'), get_string('notset', 'tool_muprog'));
}

echo $OUTPUT->render($details);

if ($buttons) {
    $buttons = implode(' ', $buttons);
    echo $OUTPUT->box($buttons, 'buttons');
}

echo $OUTPUT->footer();
