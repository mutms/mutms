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

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use tool_muprog\local\management;
use tool_muprog\local\program;

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);
$movetargetsfor = optional_param('movetargetsfor', null, PARAM_INT);
$moveitem = optional_param('moveitem', 0, PARAM_INT);
$movetoparent = optional_param('movetoparent', 0, PARAM_INT);
$moveposition = optional_param('moveposition', 0, PARAM_INT);
$autofix = optional_param('autofix', 0, PARAM_BOOL);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_content');
\tool_mulib\local\plugindocs::set_path('tool_muprog', 'management_program_content.md');

if ($autofix && !$program->archived) {
    require_sesskey();

    $top = program::load_content($program->id);
    $top->autorepair();

    redirect($currenturl);
}
if ($moveitem && $movetoparent && !$program->archived) {
    require_sesskey();

    $top = program::load_content($program->id);
    $top->move_item($moveitem, $movetoparent, $moveposition);

    redirect($currenturl);
}

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

echo $OUTPUT->header();

echo $managementoutput->render_content($program->id, ($movetargetsfor > 0 ? $movetargetsfor : null));

echo $managementoutput->render_content_orphans($program->id);

echo $OUTPUT->footer();
