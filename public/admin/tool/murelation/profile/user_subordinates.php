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
 * User subordinates.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\output\header_actions;
use tool_murelation\local\supervisor;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require_once('../../../../config.php');

$id = optional_param('id', 0, PARAM_INT);

require_login();

if (!$id) {
    $id = $USER->id;
}

if (isguestuser() || isguestuser($id)) {
    redirect(new moodle_url('/'));
}

$user = $DB->get_record('user', ['id' => $id, 'deleted' => 0, 'confirmed' => 1], '*', MUST_EXIST);
$usercontext = context_user::instance($user->id);

if ($USER->id == $user->id) {
    $context = $usercontext;
} else {
    $tenantid = null;
    if (\tool_mulib\local\mulib::is_mutenancy_active()) {
        $tenantid = $user->tenantid;
    }
    if ($tenantid) {
        $context = context_tenant::instance($tenantid);
    } else {
        $context = context_system::instance();
    }

    require_capability('tool/murelation:viewpositions', $context);
}


$pageurl = new \moodle_url('/admin/tool/murelation/profile/user_subordinates.php', ['id' => $user->id]);

$PAGE->set_url($pageurl);
$PAGE->set_context($usercontext);
$PAGE->navigation->extend_for_user($user);
$PAGE->set_pagelayout('report');

if ($user->id == $USER->id) {
    $pagetitle = get_string('subordinates_my', 'tool_murelation');
    $PAGE->set_title($pagetitle);
    $PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', ['id' => $user->id]));
    $PAGE->navbar->add($pagetitle);
} else {
    $pagetitle = get_string('subordinates', 'tool_murelation');
    $PAGE->set_title($pagetitle);
    $PAGE->navbar->add($pagetitle, 'tool_murelation');
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle, 2, 'h3');

$report = \core_reportbuilder\system_report_factory::create(
    \tool_murelation\reportbuilder\local\systemreports\user_subordinates::class,
    $usercontext
);
echo $report->output();

echo $OUTPUT->footer();
