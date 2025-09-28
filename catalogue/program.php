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
 * Program browsing for learners.
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

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

$PAGE->set_url(new moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_secondary_navigation(false);

require_login();
require_capability('tool/muprog:viewcatalogue', context_system::instance());

if (!\tool_muprog\local\util::is_muprog_active()) {
    redirect(new moodle_url('/'));
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
    redirect(new moodle_url('/admin/tool/muprog/my/program.php', ['id' => $id]));
}

if (!\tool_muprog\local\catalogue::is_program_visible($program)) {
    if (has_capability('tool/muprog:view', $programcontext)) {
        redirect(new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]));
    } else {
        redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
    }
}

$actions = new \tool_mulib\output\header_actions(get_string('catalogue_actions', 'tool_muprog'));

$manageurl = \tool_muprog\local\management::get_management_url();
if ($manageurl) {
    $actions->get_dropdown()->add_item(get_string('management', 'tool_muprog'), $manageurl);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

/** @var \tool_muprog\output\catalogue\renderer $catalogueoutput */
$catalogueoutput = $PAGE->get_renderer('tool_muprog', 'catalogue');

$PAGE->set_title(get_string('catalogue', 'tool_muprog'));
$PAGE->navbar->add(get_string('catalogue', 'tool_muprog'), new moodle_url('/admin/tool/muprog/catalogue/'));
$PAGE->navbar->add(format_string($program->fullname));

echo $OUTPUT->header();

\tool_muprog\event\catalogue_program_viewed::create_from_program($program)->trigger();

echo $catalogueoutput->render_program($program);

echo $OUTPUT->footer();
