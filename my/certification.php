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
 * User certification view.
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
/** @var stdClass $USER */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

require_login();
if (isguestuser()) {
    redirect(new core\url('/'));
}

$currenturl = new core\url('/admin/tool/mucertify/my/certification.php', ['id' => $id]);

if ($userid) {
    $currenturl->param('userid', $userid);
} else {
    $userid = $USER->id;
}
$PAGE->set_url($currenturl);

$usercontext = context_user::instance($userid);
$PAGE->set_context($usercontext);

if (!\tool_mulib\local\mulib::is_mucertify_active()) {
    redirect(new core\url('/'));
}

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
if (isguestuser($user)) {
    redirect(new core\url('/'));
}

if ($userid != $USER->id) {
    require_capability('tool/mucertify:viewusercertifications', $usercontext);
    $title = get_string('certifications', 'tool_mucertify');
} else {
    $title = get_string('mycertifications', 'tool_mucertify');
}

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $id]);
if (!$certification || $certification->archived) {
    if ($certification) {
        $context = context::instance_by_id($certification->contextid);
    } else {
        $context = context_system::instance();
    }
    if (has_capability('tool/mucertify:view', $context)) {
        if ($certification) {
            redirect(new core\url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]));
        } else {
            redirect(new core\url('/admin/tool/mucertify/management/index.php'));
        }
    } else {
        redirect(new core\url('/admin/tool/mucertify/catalogue/index.php'));
    }
}
$certificationcontext = context::instance_by_id($certification->contextid);

$assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $user->id]);
// Make sure the enrolments are 100% up-to-date for the current user,
// this is where are they going to look first in case of any problems.
$assignment = \tool_mucertify\local\assignment::sync_current_status($assignment);

if (!$assignment || $assignment->archived) {
    if (\tool_mucertify\local\catalogue::is_certification_visible($certification)) {
        redirect(new \core\url('/admin/tool/mucertify/catalogue/certification.php', ['id' => $id]));
    } else {
        if (has_capability('tool/mucertify:view', $certificationcontext)) {
            redirect(new \core\url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]));
        } else {
            redirect(new \core\url('/admin/tool/mucertify/catalogue/index.php'));
        }
    }
}

$PAGE->navigation->extend_for_user($user);
$PAGE->set_title($title);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('profile'), new core\url('/user/profile.php', ['id' => $user->id]));
$PAGE->navbar->add($title, new core\url('/admin/tool/mucertify/my/index.php', ['userid' => $user->id]));
$PAGE->navbar->add(format_string($certification->fullname));

$actions = new \tool_mulib\output\header_actions(get_string('certification_actions', 'tool_mucertify'));

if (has_capability('tool/mucertify:view', $certificationcontext)) {
    $url = new core\url('/admin/tool/mucertify/management/assignment.php', ['id' => $assignment->id]);
    $actions->get_dropdown()->add_item(get_string('assignment', 'tool_mucertify'), $url);

    $manageurl = new core\url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
    $actions->get_dropdown()->add_item(get_string('management', 'tool_mucertify'), $manageurl);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

\tool_mucertify\event\assignment_viewed::create_from_assignment($certification, $assignment)->trigger();

/** @var \tool_mucertify\output\my\renderer $myouput */
$myouput = $PAGE->get_renderer('tool_mucertify', 'my');

echo $OUTPUT->header();

echo $myouput->render_certification($certification);

echo $myouput->render_user_assignment($certification, $assignment);

echo $myouput->render_user_periods($certification, $assignment);

echo $OUTPUT->footer();
