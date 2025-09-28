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
 * My programs.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require('../../../../config.php');

require_login();

$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

if (!\tool_muprog\local\util::is_muprog_active()) {
    redirect(new moodle_url('/'));
}
if (isguestuser()) {
    redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
}

$currenturl = new moodle_url('/admin/tool/muprog/my/index.php');

$title = get_string('myprograms', 'tool_muprog');
$PAGE->navigation->extend_for_user($USER);
$PAGE->set_title($title);
$PAGE->set_url($currenturl);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', ['id' => $USER->id]));
$PAGE->navbar->add($title);

$actions = new \tool_mulib\output\header_actions(get_string('program_actions', 'tool_muprog'));

$manageurl = \tool_muprog\local\management::get_management_url();
if ($manageurl) {
    $actions->get_dropdown()->add_item(get_string('management', 'tool_muprog'), $manageurl);
}
$catalogueurl = \tool_muprog\local\catalogue::get_catalogue_url();
if ($catalogueurl) {
    $button = html_writer::link($catalogueurl, get_string('catalogue', 'tool_muprog'), ['class' => 'btn btn-secondary']);
    $actions->add_button($button);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_muprog\reportbuilder\local\systemreports\my_allocations::class,
    $usercontext
);
echo $report->output();

echo $OUTPUT->footer();
