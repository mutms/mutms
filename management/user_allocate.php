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
 * Allocate users to tenants.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $USER */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');

$userid = required_param('id', PARAM_INT);

if (!tenancy::is_active()) {
    redirect('/admin/tool/user.php');
}

$syscontext = context_system::instance();
require_login();
require_capability('tool/mutenancy:allocate', $syscontext);

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

$PAGE->set_url('/admin/tool/mutenancy/management/user_allocate.php', ['id' => $user->id]);
$PAGE->set_context($syscontext);

if (isguestuser($user) || is_siteadmin($user)) {
    throw new \core\exception\invalid_parameter_exception('guests and admins cannot be allocated');
}
if ($USER->id == $user->id) {
    throw new \core\exception\invalid_parameter_exception('cannot allocate own account');
}

$form = new \tool_mutenancy\local\form\user_allocate(null, ['user' => $user, 'context' => $syscontext]);

if ($form->is_cancelled()) {
    if ($user->tenantid) {
        $returnurl = new moodle_url('/admin/tool/mutenancy/tenant_members', ['id' => $user->tenantid]);
    } else {
        $returnurl = new moodle_url('/admin/tool/user.php');
    }
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $user = \tool_mutenancy\local\user::allocate($user->id, (int)$data->tenantid);

    if ($user->tenantid) {
        $returnurl = new moodle_url('/admin/tool/mutenancy/tenant_members', ['id' => $user->tenantid]);
    } else {
        $returnurl = new moodle_url('/admin/tool/user.php');
    }
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
