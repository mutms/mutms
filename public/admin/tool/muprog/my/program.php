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
 * My program view.
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
/** @var stdClass $COURSE */
/** @var stdClass $USER */

use tool_muprog\local\allocation;
use tool_muprog\local\notification_manager;

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);
$PAGE->set_url(new moodle_url('/admin/tool/muprog/my/program.php', ['id' => $id]));

if (!\tool_muprog\local\util::is_muprog_active()) {
    redirect(new moodle_url('/'));
}
if (isguestuser()) {
    redirect(new moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $id]));
}

$program = $DB->get_record('tool_muprog_program', ['id' => $id]);
if (!$program || $program->archived) {
    if ($program) {
        $context = context::instance_by_id($program->contextid);
    } else {
        $context = context_system::instance();
    }
    if (has_capability('tool/muprog:view', $context)) {
        if ($program) {
            redirect(new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]));
        } else {
            redirect(new moodle_url('/admin/tool/muprog/management/index.php'));
        }
    } else {
        redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
    }
}
$programcontext = context::instance_by_id($program->contextid);

$allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $USER->id]);
if ($allocation && !$allocation->archived) {
    // Make sure the enrolments are 100% up-to-date for the current user,
    // this is where are they going to look first in case of any problems.
    allocation::fix_user_enrolments($program->id, $USER->id);
    notification_manager::trigger_notifications($program->id, $USER->id);
    $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $USER->id]);
}
if (!$allocation || $allocation->archived) {
    if (\tool_muprog\local\catalogue::is_program_visible($program)) {
        redirect(new moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $id]));
    } else {
        if (has_capability('tool/muprog:view', $programcontext)) {
            redirect(new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]));
        } else {
            redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
        }
    }
}
$source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);

$title = get_string('myprograms', 'tool_muprog');
$PAGE->navigation->extend_for_user($USER);
$PAGE->set_title($title);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', ['id' => $USER->id]));
$PAGE->navbar->add($title, new moodle_url('/admin/tool/muprog/my/index.php'));
$PAGE->navbar->add(format_string($program->fullname));

$actions = new \tool_mulib\output\header_actions(get_string('program_actions', 'tool_muprog'));

if (has_capability('tool/muprog:view', $programcontext)) {
    $manageurl = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
    $actions->get_dropdown()->add_item(get_string('management', 'tool_muprog'), $manageurl);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

/** @var \tool_muprog\output\my\renderer $myouput */
$myouput = $PAGE->get_renderer('tool_muprog', 'my');

echo $OUTPUT->header();

$event = \tool_muprog\event\program_viewed::create_from_program($program);
$event->trigger();

echo $myouput->render_program($program);

echo $myouput->render_user_allocation($program, $source, $allocation);

echo $myouput->render_user_progress($program, $allocation);

echo $OUTPUT->footer();
