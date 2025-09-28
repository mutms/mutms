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
 * Update tenant member account.
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

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/gdlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$userid = required_param('id', PARAM_INT);

require_login();

if (!tenancy::is_active()) {
    redirect(new moodle_url('/'));
}

$personalcontext = context_user::instance($userid);
require_capability('tool/mutenancy:memberupdate', $personalcontext);

$user = $DB->get_record('user', ['id' => $userid]);

if (
    !$user || $user->deleted || !$user->tenantid || isguestuser($user)
    || is_siteadmin($user) || $USER->id == $user->id || $user->mnethostid != $CFG->mnet_localhost_id
) {
    throw new moodle_exception('invaliduserid');
}

\tool_mutenancy\local\tenancy::force_current_tenantid($user->tenantid);

$PAGE->set_url('/admin/tool/mutenancy/management/member_update.php', ['id' => $userid]);
$PAGE->set_context($personalcontext);

$user->interests = core_tag_tag::get_item_tags_array('core', 'user', $user->id);

// Make sure normap profile editing is allowed.
$userauth = get_auth_plugin($user->auth);
if (!$userauth->can_edit_profile() || $userauth->edit_profile_url()) {
    throw new \moodle_exception('noprofileedit', 'auth');
}

// Load user preferences.
useredit_load_preferences($user);

// Load custom profile fields data.
profile_load_data($user);

$editoroptions = [
    'maxfiles'   => EDITOR_UNLIMITED_FILES,
    'maxbytes'   => $CFG->maxbytes,
    'trusttext'  => false,
    'forcehttps' => false,
    'context'    => $personalcontext,
];
$user = file_prepare_standard_editor($user, 'description', $editoroptions, $editoroptions['context'], 'user', 'profile', 0);

$filemanageroptions = [
    'maxbytes'       => $CFG->maxbytes,
    'subdirs'        => 0,
    'maxfiles'       => 1,
    'accepted_types' => 'optimised_image',
    'context'        => $personalcontext,
];
file_prepare_draft_area($draftitemid, $filemanageroptions['context']->id, 'user', 'newicon', 0, $filemanageroptions);
$user->imagefile = $draftitemid;

// Create form.
$userform = new \tool_mutenancy\local\form\member_edit(null, [
    'editoroptions' => $editoroptions,
    'filemanageroptions' => $filemanageroptions,
    'user' => $user,
]);

$returnurl = new moodle_url('/admin/tool/mutenancy/tenant_users.php', ['id' => $user->tenantid]);

if ($userform->is_cancelled()) {
    $userform->ajax_form_cancelled($returnurl);
} else if ($usernew = $userform->get_data()) {
    $user = $DB->get_record('user', ['id' => $user->id, 'deleted' => 0], '*', MUST_EXIST);

    $usernew->auth = $user->auth;
    $usernew->timemodified = time();
    $usernew->tenantid = $user->tenantid;

    $authplugin = get_auth_plugin($usernew->auth);

    $createpassword = !empty($usernew->createpassword);
    unset($usernew->createpassword);

    $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, $personalcontext, 'user', 'profile', 0);
    // Pass a true old $user here.
    if (!$authplugin->user_update($user, $usernew)) {
        // Auth update failed.
        throw new \moodle_exception('cannotupdateuseronexauth', '', '', $user->auth);
    }
    user_update_user($usernew, false, false);

    // Set new password if specified.
    if (!empty($usernew->newpassword)) {
        if ($authplugin->can_change_password()) {
            if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                throw new \moodle_exception('cannotupdatepasswordonextauth', '', '', $usernew->auth);
            }
            unset_user_preference('create_password', $usernew); // Prevent cron from generating the password.

            if (!empty($CFG->passwordchangelogout)) {
                // We can use SID of other user safely here because they are unique,
                // the problem here is we do not want to logout admin here when changing own password.
                \core\session\manager::destroy_user_sessions($usernew->id, session_id());
            }
            if (!empty($usernew->signoutofotherservices)) {
                webservice::delete_user_ws_tokens($usernew->id);
            }
        }
    }

    // Force logout if user just suspended.
    if (isset($usernew->suspended) && $usernew->suspended && !$user->suspended) {
        \core\session\manager::destroy_user_sessions($user->id);
    }

    $usercontext = context_user::instance($usernew->id);

    // Update preferences.
    useredit_update_user_preference($usernew);

    // Update tags.
    if (isset($usernew->interests)) {
        useredit_update_interests($usernew, $usernew->interests);
    }

    // Update user picture.
    core_user::update_picture($usernew, $filemanageroptions);

    // Update mail bounces.
    useredit_update_bounces($user, $usernew);

    // Update forum track preference.
    useredit_update_trackforums($user, $usernew);

    // Save custom profile fields data.
    profile_save_data($usernew);

    // Reload from db.
    $user = $DB->get_record('user', ['id' => $user->id, 'deleted' => 0], '*', MUST_EXIST);

    if ($createpassword) {
        setnew_password_and_mail($user);
        unset_user_preference('create_password', $user);
        set_user_preference('auth_forcepasswordchange', 1, $user);
    }

    // Trigger update event, after all fields are stored.
    \core\event\user_updated::create_from_userid($user->id)->trigger();

    $userform->ajax_form_submitted($returnurl);
}

$PAGE->set_heading(fullname($user, true));

$userform->ajax_form_render(get_string('member_update', 'tool_mutenancy'));
