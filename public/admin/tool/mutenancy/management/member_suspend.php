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

/**
 * Suspend tenant member account.
 *
 * See original code in user/editadvanced.php file.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */
/** @var stdClass $USER */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');

$userid = required_param('id', PARAM_INT);

require_login();

if (!tenancy::is_active()) {
    redirect(new moodle_url('/'));
}

$personalcontext = context_user::instance($userid);
require_capability('tool/mutenancy:memberupdate', $personalcontext);

$PAGE->set_url('/admin/tool/mutenancy/management/member_suspend.php', ['id' => $userid]);
$PAGE->set_context($personalcontext);

$user = $DB->get_record('user', ['id' => $userid]);

if (
    !$user || $user->deleted || !$user->tenantid || isguestuser($user)
    || is_siteadmin($user) || $USER->id == $user->id || $user->mnethostid != $CFG->mnet_localhost_id
) {
    throw new moodle_exception('invaliduserid');
}

$returnurl = new moodle_url('/admin/tool/mutenancy/tenant_users.php', ['id' => $user->tenantid]);

if ($user->suspended) {
    redirect($returnurl);
}

$form = new \tool_mutenancy\local\form\member_suspend(null, ['user' => $user]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    \tool_mutenancy\local\member::suspend($user->id);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
