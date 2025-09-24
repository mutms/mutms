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
 * Delete subordinate and their supervisor.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_murelation\local\uimode_supervisors;
use tool_murelation\local\framework;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$subuserid = required_param('subuserid', PARAM_INT);
$frameworkid = required_param('frameworkid', PARAM_INT);

require_login();

$framework = $DB->get_record('tool_murelation_framework', ['id' => $frameworkid], '*', MUST_EXIST);
$subuser = $DB->get_record('user', ['id' => $subuserid], '*', MUST_EXIST);

$context = context_user::instance($subuser->id);
require_capability('tool/murelation:managepositions', $context);

if ($framework->uimode != framework::UIMODE_SUPERVISORS) {
    throw new \core\exception\coding_exception('supervisor management is possible for frameworks in Supervisors mode only');
}

$returnurl = new moodle_url('/user/profile.php', ['id' => $subuser->id]);
$currenturl = new moodle_url('/admin/tool/murelation/management/supervisor_delete.php', ['frameworkid' => $framework->id, 'subuserid' => $subuser->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$subordinate = $DB->get_record('tool_murelation_subordinate', ['frameworkid' => $framework->id, 'userid' => $subuser->id]);
if (!$subordinate) {
    redirect($returnurl);
}
$supervisor = $DB->get_record('tool_murelation_supervisor', ['id' => $subordinate->supervisorid], '*', MUST_EXIST);
if ($supervisor) {
    $supuser = $DB->get_record('user', ['id' => $supervisor->userid], '*', MUST_EXIST);
} else {
    $supuser = false;
}

if (!uimode_supervisors::can_manage_subordinate($framework, $subuser->id)) {
    redirect($returnurl);
}

$form = new \tool_murelation\local\form\supervisor_delete(
    null,
    ['framework' => $framework, 'subuser' => $subuser, 'supuser' => $supuser]
);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    \tool_murelation\local\supervisor::delete($supervisor->id);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
