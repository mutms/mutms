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
 * Delete certification assignment.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucertify\local\assignment;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $id], '*', MUST_EXIST);
$certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);
$source = $DB->get_record('tool_mucertify_source', ['id' => $assignment->sourceid], '*', MUST_EXIST);

$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:unassign', $context);

$currenturl = new \core\url('/admin/tool/mucertify/management/assignment_delete.php', ['id' => $assignment->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new \core\url('/admin/tool/mucertify/management/certification_users.php', ['id' => $certification->id]);

$user = $DB->get_record('user', ['id' => $assignment->userid], '*', MUST_EXIST);

$sourceclass = assignment::get_source_classname($source->type);
if (!$sourceclass || !$sourceclass::is_assignment_delete_possible($certification, $source, $assignment)) {
    redirect($returnurl);
}

$form = new \tool_mucertify\local\form\assignment_delete(
    null,
    ['certification' => $certification, 'assignment' => $assignment, 'user' => $user, 'context' => $context]
);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $sourceclass::assignment_delete($certification, $source, $assignment);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
