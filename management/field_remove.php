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
 * Remove field from training framework.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutrain\local\framework;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require('../../../../config.php');
require_once("$CFG->libdir/filelib.php");

$frameworkid = required_param('frameworkid', PARAM_INT);
$fieldid = required_param('fieldid', PARAM_INT);

require_login();

$framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid]);
$context = context::instance_by_id($framework->contextid);
require_capability('tool/mutrain:manageframeworks', $context);

$currenturl = new core\url('/admin/tool/mutrain/management/field_remove.php', ['framework' => $frameworkid, 'field' => $fieldid]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new core\url('/admin/tool/mutrain/management/framework.php', ['id' => $frameworkid]);

$field = $DB->get_record('customfield_field', ['id' => $fieldid], '*', MUST_EXIST);
if (!$DB->record_exists('tool_mutrain_field', ['frameworkid' => $framework->id, 'fieldid' => $field->id])) {
    redirect($returnurl);
}

if ($framework->archived) {
    redirect($returnurl);
}

$data = clone($framework);

$form = new \tool_mutrain\local\form\field_remove(null, ['framework' => $framework, 'field' => $field]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    framework::field_remove($data->frameworkid, $data->fieldid);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
