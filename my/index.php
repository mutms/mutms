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
 * User certifications.
 *
 * @package    tool_mucertify
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $USER */

require('../../../../config.php');

$userid = optional_param('userid', 0, PARAM_INT);

require_login();
if (isguestuser()) {
    redirect(new core\url('/'));
}

$currenturl = new core\url('/admin/tool/mucertify/my/index.php');

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

$PAGE->navigation->extend_for_user($user);
$PAGE->set_title($title);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('profile'), new core\url('/user/profile.php', ['id' => $user->id]));
$PAGE->navbar->add($title);

$actions = new \tool_mulib\output\header_actions(get_string('certification_actions', 'tool_mucertify'));

$manageurl = \tool_mucertify\local\management::get_management_url();
if ($manageurl) {
    $actions->get_dropdown()->add_item(get_string('management', 'tool_mucertify'), $manageurl);
}
$catalogueurl = \tool_mucertify\local\catalogue::get_catalogue_url();
if ($catalogueurl) {
    $button = html_writer::link($catalogueurl, get_string('catalogue', 'tool_mucertify'), ['class' => 'btn btn-secondary']);
    $actions->add_button($button);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$report = \core_reportbuilder\system_report_factory::create(
    \tool_mucertify\reportbuilder\local\systemreports\assignments_user::class,
    $usercontext
);
echo $report->output();

echo $OUTPUT->footer();
