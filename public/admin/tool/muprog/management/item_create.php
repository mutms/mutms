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
 * Create a new program item.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

use tool_muprog\local\program;
use core\url;

define('AJAX_SCRIPT', true);

require('../../../../config.php');

require_login();

$parentid = required_param('parentid', PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHANUM);

$parentrecord = $DB->get_record('tool_muprog_item', ['id' => $parentid], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $parentrecord->programid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);
if ($program->archived) {
    require_capability('tool/muprog:admin', $context);
}

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/muprog/management/item_create.php', ['parentid' => $parentrecord->id, 'type' => $type]);

$returnurl = new url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);

$top = program::load_content($program->id);
$parent = $top->find_item($parentrecord->id);

if ($parent::get_type() !== 'set' && $parent::get_type() !== 'top') {
    throw new \core\exception\invalid_parameter_exception('parent must be a set');
}

$types = $top::get_types();
unset($types['top']);
if (!\tool_mulib\local\mulib::is_mutrain_active()) {
    unset($types['credits']);
}
if (!isset($types[$type])) {
    $type = '';
}

if (!$type) {
    $currentdata = ['parentid' => $parent->get_id()];
    $form = new \tool_muprog\local\form\item_create(
        null,
        ['currentdata' => $currentdata, 'types' => $types]
    );
    if ($form->is_cancelled()) {
        $form->ajax_form_cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        $type = $data->type;
    } else {
        $form->ajax_form_render();
    }
}

$currentdata = [
    'parentid' => $parent->get_id(),
    'type' => $type,
    'points' => 1,
];
if ($type === 'set') {
    $currentdata['sequencetype'] = $top::SEQUENCE_TYPE_ALLINANYORDER;
    $currentdata['minprerequisites'] = 1;
    $currentdata['minpoints'] = 1;
    $form = new tool_muprog\local\form\item_create_set(
        null,
        ['currentdata' => $currentdata, 'types' => $types, 'parent' => $parent, 'context' => $context]
    );
} else if ($type === 'course') {
    $form = new tool_muprog\local\form\item_create_course(
        null,
        ['currentdata' => $currentdata, 'types' => $types, 'parent' => $parent, 'context' => $context]
    );
} else if ($type === 'attendance') {
    $form = new tool_muprog\local\form\item_create_attendance(
        null,
        ['currentdata' => $currentdata, 'types' => $types, 'parent' => $parent, 'context' => $context]
    );
} else if ($type === 'credits') {
    $form = new tool_muprog\local\form\item_create_credits(
        null,
        ['currentdata' => $currentdata, 'types' => $types, 'parent' => $parent, 'context' => $context]
    );
} else {
    throw new \core\exception\coding_exception('Unknown item type');
}

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    if ($type === 'set') {
        $set = $top->append_set($parent, (array)$data);
    } else if ($type === 'course') {
        $courseids = $data->courseids;
        unset($data->courseids);
        foreach ($courseids as $courseid) {
            $coursecontext = context_course::instance($courseid);
            $top->append_course($parent, $courseid, (array)$data);
        }
    } else if ($type === 'attendance') {
        $top->append_attendance($parent, (array)$data);
    } else if ($type === 'credits') {
        $top->append_credits($parent, $data->creditframeworkid, (array)$data);
    }
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
