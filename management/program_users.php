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
 * Program management interface.
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

$currenturl = new moodle_url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);

management::setup_program_page($currenturl, $context, $program, 'program_users');
\tool_mulib\local\plugindocs::set_path('tool_muprog', 'management_program_users.md');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id]);
/** @var \tool_muprog\local\source\base[] $sourceclasses */ // Type hack.
$sourceclasses = \tool_muprog\local\allocation::get_source_classes();

$actions = new \tool_mulib\output\header_actions(get_string('management_program_users_actions', 'tool_muprog'));
foreach ($sourceclasses as $sourceclass) {
    $sourcetype = $sourceclass::get_type();
    $sourcerecord = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => $sourcetype]);
    if (!$sourcerecord) {
        continue;
    }
    $sourceclass::add_management_program_users_actions($actions, $program, $sourcerecord);
}
$canmanageevidence = has_capability('tool/muprog:manageevidence', $context);
$totalcount = $DB->count_records('tool_muprog_allocation', ['programid' => $program->id]);
if ($totalcount && !$program->archived && $canmanageevidence) {
    $url = new \moodle_url('/admin/tool/muprog/management/program_evidence_upload.php', ['programid' => $id]);
    $link = new \tool_mulib\output\ajax_form\link($url, get_string('evidenceupload', 'tool_muprog'));
    $actions->get_dropdown()->add_ajax_form($link);
}
if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_muprog\reportbuilder\local\systemreports\allocations::class,
    $context,
    parameters:['programid' => $program->id]
);
echo $report->output();

echo $OUTPUT->footer();
