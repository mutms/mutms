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

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$period = $DB->get_record('tool_mucertify_period', ['id' => $id], '*', MUST_EXIST);
$certification = $DB->get_record('tool_mucertify_certification', ['id' => $period->certificationid], '*', MUST_EXIST);
$assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $period->userid]);

$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:admin', $context);

$currenturl = new \core\url('/admin/tool/mucertify/management/period_update.php', ['id' => $period->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new \core\url('/admin/tool/mucertify/management/period.php', ['id' => $period->id]);
if (!$period->timerevoked || ($assignment && $assignment->archived) || $certification->archived) {
    redirect($returnurl);
}

$user = $DB->get_record('user', ['id' => $period->userid], '*', MUST_EXIST);

$form = new \tool_mucertify\local\form\period_delete(null, ['period' => $period, 'user' => $user, 'context' => $context]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    \tool_mucertify\local\period::delete($data->id);
    if ($assignment) {
        $returnurl = new \core\url('/admin/tool/mucertify/management/assignment.php', ['id' => $assignment->id]);
    } else {
        $returnurl = new \core\url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
    }
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
