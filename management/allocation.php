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
use tool_muprog\local\allocation;
use tool_mulib\output\header_actions;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$allocation = $DB->get_record('tool_muprog_allocation', ['id' => $id], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);
$source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$user = $DB->get_record('user', ['id' => $allocation->userid], '*', MUST_EXIST);

$currenturl = new \core\url('/admin/tool/muprog/management/allocation.php', ['id' => $allocation->id]);

management::setup_program_page($currenturl, $context, $program, 'program_users');

$url = new core\url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);
$PAGE->navbar->add(get_string('tabusers', 'tool_muprog'), $url);
$PAGE->navbar->add(fullname($user));

$PAGE->set_docs_path('https://github.com/mutms/moodle-tool_muprog/wiki/Program-users');

$sourceclasses = allocation::get_source_classes();
/** @var \tool_muprog\local\source\base $sourceclass */
$sourceclass = $sourceclasses[$source->type];

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

// Refresh allocation data just in case.
allocation::fix_user_enrolments($program->id, $allocation->userid);
$allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);

\tool_muprog\event\allocation_viewed::create_from_allocation($allocation, $program)->trigger();

echo $OUTPUT->header();

echo $OUTPUT->heading($OUTPUT->user_picture($user) . fullname($user), 2, ['h3']);

echo $managementoutput->render_user_allocation($program, $source, $allocation);
echo $managementoutput->render_user_progress($program, $allocation);
echo $managementoutput->render_user_notifications($program, $allocation);

echo $OUTPUT->footer();
