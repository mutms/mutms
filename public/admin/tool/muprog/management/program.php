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
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_general');
\tool_mulib\local\plugindocs::set_path('tool_muprog', 'management_program.md');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$actions = new header_actions(get_string('management_program_general_actions', 'tool_muprog'));
if ($program->archived && has_capability('tool/muprog:delete', $context)) {
    $url = new moodle_url('/admin/tool/muprog/management/program_delete.php', ['id' => $program->id]);
    $link = new tool_mulib\output\ajax_form\link($url, get_string('program_delete', 'tool_muprog'));
    $link->set_form_size('sm');
    $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
    $actions->get_dropdown()->add_ajax_form($link);
}
if (has_capability('tool/muprog:export', $context)) {
    $url = new moodle_url('/admin/tool/muprog/management/export.php', ['id' => $program->id]);
    $actions->get_dropdown()->add_item(get_string('export', 'tool_muprog'), $url);
}
if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

$buttons = [];
if (has_capability('tool/muprog:edit', $context)) {
    $url = new moodle_url('/admin/tool/muprog/management/program_update.php', ['id' => $program->id]);
    $editbutton = new tool_mulib\output\ajax_form\button($url, get_string('edit'));
    $buttons[] = $OUTPUT->render($editbutton);
}

echo $managementoutput->render_program_general($program);

if ($buttons) {
    $buttons = implode(' ', $buttons);
    echo $OUTPUT->box($buttons, 'buttons');
}

echo $OUTPUT->footer();
