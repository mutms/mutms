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
 * Bulk Allocate user to tenant/global.
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

require(__DIR__ . '/../../../../config.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
if ($returnurl) {
    $returnurl = new \core\url($returnurl);
} else {
    $returnurl = new \core\url('/admin/user/user_bulk.php');
}

if (!tenancy::is_active()) {
    redirect($returnurl);
}
if (empty($SESSION->bulk_users)) {
    redirect($returnurl);
}

$syscontext = context_system::instance();
require_login();
require_capability('tool/mutenancy:allocate', $syscontext);

$PAGE->set_url('/admin/tool/mutenancy/management/bulk_allocate.php');
$PAGE->set_context($syscontext);

$form = new \tool_mutenancy\local\form\bulk_allocate(null, ['returnurl' => $returnurl->out_as_local_url(false)]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $tenantid = $data->tenantid;
    foreach ($SESSION->bulk_users as $userid) {
        if (is_siteadmin($userid)) {
            continue;
        }
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || $user->deleted || !$user->confirmed) {
            continue;
        }
        if ($user->tenantid == $tenantid) {
            continue;
        }
        \tool_mutenancy\local\user::allocate($userid, $tenantid);
    }
    redirect($returnurl);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
