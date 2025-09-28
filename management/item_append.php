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

use tool_muprog\local\program;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$parentitemid = required_param('parentitemid', PARAM_INT);

require_login();

$parentitem = $DB->get_record('tool_muprog_item', ['id' => $parentitemid], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $parentitem->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/item_append.php', ['parentitemid' => $parentitem->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);

if ($program->archived) {
    redirect($returnurl);
}

$top = program::load_content($program->id);
$set = $top->find_item($parentitem->id);
if (!$set || !($set instanceof \tool_muprog\local\content\set)) {
    redirect($returnurl);
}

$form = new \tool_muprog\local\form\item_append(null, ['parentset' => $set, 'context' => $context]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    if ($data->addset) {
        $set = $top->append_set($set, (array)$data);
    }
    foreach ($data->courses as $cid) {
        $coursecontext = context_course::instance($cid);
        require_capability('tool/muprog:addcourse', $coursecontext);
        $idata = ['points' => $data->points];
        if (!empty($data->completiondelay)) {
            $idata['completiondelay'] = $data->completiondelay;
        }
        $top->append_course($set, $cid, $idata);
    }
    if (!empty($data->trainingid)) {
        $idata = ['points' => $data->points];
        if (!empty($data->completiondelay)) {
            $idata['completiondelay'] = $data->completiondelay;
        }
        $top->append_training($set, $data->trainingid, $idata);
    }

    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
