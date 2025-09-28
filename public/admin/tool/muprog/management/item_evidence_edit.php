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
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\allocation;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$allocationid = required_param('allocationid', PARAM_INT);
$itemid = required_param('itemid', PARAM_INT);

require_login();

$allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocationid], '*', MUST_EXIST);
$item = $DB->get_record('tool_muprog_item', ['id' => $itemid, 'programid' => $allocation->programid], '*', MUST_EXIST);
$completion = $DB->get_record('tool_muprog_completion', ['allocationid' => $allocation->id, 'itemid' => $item->id]);
$evidence = $DB->get_record('tool_muprog_evidence', ['userid' => $allocation->userid, 'itemid' => $item->id]);

$user = $DB->get_record('user', ['id' => $allocation->userid, 'deleted' => 0], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:manageevidence', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/item_evidence_edit.php', ['allocationid' => $allocation->id, 'itemid' => $item->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new moodle_url('/admin/tool/muprog/management/allocation.php', ['id' => $allocation->id]);

if ($program->archived || $allocation->archived) {
    redirect($returnurl);
}

$form = new \tool_muprog\local\form\item_evidence_edit(null, [
    'allocation' => $allocation, 'item' => $item, 'user' => $user,
    'completion' => $completion, 'evidence' => $evidence, 'context' => $context,
]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    allocation::update_item_evidence($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
