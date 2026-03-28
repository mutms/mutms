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
 * Create tenant member account.
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
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$tenantid = required_param('tenantid', PARAM_INT);

require_login();

if (!tenancy::is_active()) {
    redirect(new \core\url('/'));
}

$tenantcontext = context_tenant::instance($tenantid);
require_capability('tool/mutenancy:membercreate', $tenantcontext);

$tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);

$PAGE->set_url('/admin/tool/mutenancy/management/member_create.php', ['tenantid' => $tenantid]);
$PAGE->set_context($tenantcontext);

\tool_mutenancy\local\tenancy::force_current_tenantid($tenant->id);

$user = (object)[
    'id' => -1,
    'tenantid' => $tenant->id,
    'auth' => 'manual',
    'confirmed' => 1,
    'deleted' => 0,
    'timezone' => 99,
];

$returnurl = new \core\url('/admin/tool/mutenancy/tenant_users.php', ['id' => $user->tenantid]);

if ($tenant->memberlimit) {
    $mcount = $DB->count_records('user', ['deleted' => 0, 'tenantid' => $tenant->id]);
    if ($mcount >= $tenant->memberlimit) {
        redirect($returnurl);
    }
}

// Load custom profile fields data.
profile_load_data($user);

// Create form.
$userform = new \tool_mutenancy\local\form\member_edit(null, [
    'editoroptions' => [],
    'filemanageroptions' => [],
    'user' => $user,
]);

if ($userform->is_cancelled()) {
    $userform->ajax_form_cancelled($returnurl);
} else if ($usernew = $userform->get_data()) {
    unset($usernew->id);
    $usernew->auth = 'manual';
    $usernew->confirmed = 1;
    $usernew->mnethostid = $CFG->mnet_localhost_id;
    $usernew->timecreated = time();
    $usernew->timemodified = time();
    $usernew->tenantid = $tenantid;

    $authplugin = get_auth_plugin($usernew->auth);

    $createpassword = !empty($usernew->createpassword);
    unset($usernew->createpassword);

    $usernew = file_postupdate_standard_editor($usernew, 'description', [], context_system::instance());

    if ($createpassword || empty($usernew->newpassword)) {
        $usernew->password = '';
        $createpassword = true;
    } else {
        $usernew->password = hash_internal_user_password($usernew->newpassword);
    }
    $usernew->id = user_create_user($usernew, false, false);

    $usercontext = context_user::instance($usernew->id);

    // Update preferences.
    useredit_update_user_preference($usernew);

    // Update tags.
    if (isset($usernew->interests)) {
        useredit_update_interests($usernew, $usernew->interests);
    }

    // Update mail bounces.
    useredit_update_bounces($user, $usernew);

    // Update forum track preference.
    useredit_update_trackforums($user, $usernew);

    // Save custom profile fields data.
    profile_save_data($usernew);

    // Reload from db.
    $user = $DB->get_record('user', ['id' => $usernew->id, 'deleted' => 0], '*', MUST_EXIST);

    if ($createpassword) {
        setnew_password_and_mail($user);
        unset_user_preference('create_password', $user);
        set_user_preference('auth_forcepasswordchange', 1, $user);
    }

    // Trigger create event, after all fields are stored.
    \core\event\user_created::create_from_userid($user->id)->trigger();

    $userform->ajax_form_submitted($returnurl);
}

$userform->ajax_form_render(get_string('member_create', 'tool_mutenancy'));
