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
 * Program management interface.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\source\manual;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);

require_login();

$source = $DB->get_record('tool_muprog_source', ['id' => $sourceid, 'type' => 'manual'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:allocate', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/source_manual_allocate.php', ['sourceid' => $source->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new moodle_url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);

if (!manual::is_allocation_possible($program, $source)) {
    redirect($returnurl);
}

$form = new \tool_muprog\local\form\source_manual_allocate(null, ['program' => $program, 'source' => $source, 'context' => $context]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $userids = [];
    $allocationids = [];
    if ($data->cohortid) {
        $userids = $DB->get_fieldset_select('cohort_members', 'userid', "cohortid = ?", [$data->cohortid]);
    }
    if ($data->users) {
        $userids = array_merge($userids, $data->users);
        $userids = array_unique($userids);
    }
    if ($userids) {
        $allocationids = manual::allocate_users($program->id, $source->id, $userids);
    }

    // Save custom fields.
    foreach ($allocationids as $allocationid) {
        /** @var \tool_muprog\customfield\allocation_handler $handler */
        $handler = \tool_muprog\customfield\allocation_handler::create();
        $data->id = $allocationid;
        $handler->instance_form_save($data);
    }

    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
