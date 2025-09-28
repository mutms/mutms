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
use tool_mulib\output\header_actions;

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

$currenturl = new moodle_url('/admin/tool/muprog/management/program_allocation.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_allocation');
\tool_mulib\local\plugindocs::set_path('tool_muprog', 'management_program_allocation.md');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$actions = new header_actions(get_string('management_program_allocation_actions', 'tool_muprog'));

if (has_capability('tool/muprog:edit', $context) && !$program->archived) {
    $url = new moodle_url('/admin/tool/muprog/management/program_allocation_import.php', ['id' => $program->id]);
    $link = new \tool_mulib\output\ajax_form\link($url, get_string('importprogramallocation', 'tool_muprog'));
    $actions->get_dropdown()->add_ajax_form($link);
}

if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

if (has_capability('tool/muprog:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/muprog/management/program_allocations_edit.php', ['id' => $program->id]);
    $updateicon = new tool_mulib\output\ajax_form\icon($editurl, get_string('program_allocations_edit', 'tool_muprog'), 'i/settings');
    $updateicon->set_modal_title(get_string('allocations', 'tool_muprog'));
    $updateicon = ' <span style="font-size: .9375rem !important">' . $OUTPUT->render($updateicon) . '</span>';
} else {
    $updateicon = '';
}
echo $OUTPUT->heading(get_string('allocations', 'tool_muprog') . $updateicon, 2, ['h3']);
echo $managementoutput->render_program_allocation($program);

if (has_capability('tool/muprog:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/muprog/management/program_scheduling_edit.php', ['id' => $program->id]);
    $updateicon = new tool_mulib\output\ajax_form\icon($editurl, get_string('updatescheduling', 'tool_muprog'), 'i/settings');
    $updateicon->set_modal_title(get_string('scheduling', 'tool_muprog'));
    $updateicon = ' <span style="font-size: .9375rem !important">' . $OUTPUT->render($updateicon) . '</span>';
} else {
    $updateicon = '';
}
echo $OUTPUT->heading(get_string('scheduling', 'tool_muprog') . $updateicon, 2, ['h3']);
echo $managementoutput->render_program_scheduling($program);

echo $OUTPUT->heading(get_string('allocationsources', 'tool_muprog'), 2, ['h3']);
echo $managementoutput->render_program_sources($program);

echo $OUTPUT->footer();
