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
 * certification management interface.
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

$assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $id], '*', MUST_EXIST);
$certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);
$source = $DB->get_record('tool_mucertify_source', ['id' => $assignment->sourceid], '*', MUST_EXIST);

$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:view', $context);

$user = $DB->get_record('user', ['id' => $assignment->userid], '*', MUST_EXIST);

$currenturl = new moodle_url('/admin/tool/mucertify/management/assignment.php', ['id' => $assignment->id]);

management::setup_certification_page($currenturl, $context, $certification, 'certification_users');
\tool_mulib\local\plugindocs::set_path('tool_mucertify', 'management_assignment.md');

/** @var \tool_mucertify\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_mucertify', 'management');

echo $OUTPUT->header();

// Refresh user certification data just in case.
\tool_mucertify\local\period::process_recertifications($assignment->certificationid, $assignment->userid);
\tool_muprog\local\source\mucertify::sync_certifications($assignment->certificationid, $assignment->userid);
$assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $assignment->id], '*', MUST_EXIST);

echo $OUTPUT->heading($OUTPUT->user_picture($user) . fullname($user), 2, ['h3']);

echo $managementoutput->render_user_assignment($certification, $assignment);
echo $managementoutput->render_user_periods($certification, $assignment);
echo $managementoutput->render_user_notifications($certification, $assignment);

echo $OUTPUT->footer();
