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
 * Request allocation to program.
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

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/muprog/catalogue/source_approval_requests.php', ['sourceid' => $sourceid]));

require_login();
require_capability('tool/muprog:viewcatalogue', context_system::instance());

if (!\tool_muprog\local\util::is_muprog_active()) {
    redirect(new moodle_url('/'));
}

$source = $DB->get_record('tool_muprog_source', ['id' => $sourceid, 'type' => 'approval'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
$programcontext = context::instance_by_id($program->contextid);

if (!\tool_muprog\local\source\approval::can_user_request($program, $source, $USER->id)) {
    redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
}

$returnurl = new moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $program->id]);

$form = new tool_muprog\local\form\source_approval_request(null, ['source' => $source, 'program' => $program]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tool_muprog\local\source\approval::request($program->id, $source->id);
    $form->ajax_form_submitted($returnurl);
}

/** @var \tool_muprog\output\catalogue\renderer $catalogueoutput */
$catalogueoutput = $PAGE->get_renderer('tool_muprog', 'catalogue');

$form->ajax_form_render();
