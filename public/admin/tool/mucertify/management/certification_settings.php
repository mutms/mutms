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
 * Certification management interface.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucertify\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:view', $context);

$currenturl = new moodle_url('/admin/tool/mucertify/management/certification_settings.php', ['id' => $id]);

management::setup_certification_page($currenturl, $context, $certification, 'certification_periods');
\tool_mulib\local\plugindocs::set_path('tool_mucertify', 'management_certification_settings.md');

/** @var \tool_mucertify\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_mucertify', 'management');

echo $OUTPUT->header();

$updateicon = '';
if (has_capability('tool/mucertify:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/mucertify/management/certification_settings_edit1.php', ['id' => $certification->id]);
    $updateicon = new tool_mulib\output\ajax_form\icon($editurl, get_string('certification_update', 'tool_mucertify'), 'i/settings');
    $updateicon = ' <span style="font-size: .9375rem !important">' . $OUTPUT->render($updateicon) . '</span>';
}
echo $OUTPUT->heading(get_string('certification', 'tool_mucertify') . $updateicon, 3);
echo $managementoutput->render_certification_settings1($certification);

if ($certification->recertify !== null) {
    $updateicon = '';
    if (has_capability('tool/mucertify:edit', $context)) {
        $editurl = new moodle_url('/admin/tool/mucertify/management/certification_settings_edit2.php', ['id' => $certification->id]);
        $updateicon = new tool_mulib\output\ajax_form\icon($editurl, get_string('updaterecertification', 'tool_mucertify'), 'i/settings');
        $updateicon = ' <span style="font-size: .9375rem !important">' . $OUTPUT->render($updateicon) . '</span>';
    }
    echo $OUTPUT->heading(get_string('recertification', 'tool_mucertify') . $updateicon, 3);
    echo $managementoutput->render_certification_settings2($certification);
}

if (\tool_mucertify\local\certificate::is_available()) {
    $updateicon = '';
    if (has_capability('tool/mucertify:edit', $context)) {
        $editurl = new moodle_url('/admin/tool/mucertify/management/certification_certificate_edit.php', ['id' => $certification->id]);
        $updateicon = new tool_mulib\output\ajax_form\icon($editurl, get_string('updatecertificatetemplate', 'tool_mucertify'), 'i/settings');
        $updateicon = ' <span style="font-size: .9375rem !important">' . $OUTPUT->render($updateicon) . '</span>';
    }
    echo $OUTPUT->heading(get_string('certificates', 'tool_mucertify') . $updateicon, 3);
    echo $managementoutput->render_certification_certificate($certification);
}

echo $OUTPUT->footer();
