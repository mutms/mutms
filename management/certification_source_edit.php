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
 * certification management interface.
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
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$certificationid = required_param('certificationid', PARAM_INT);
$type = required_param('type', PARAM_ALPHANUMEXT);

require_login();

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);
$source = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification->id, 'type' => $type]);
$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:edit', $context);

$currenturl = new moodle_url('/admin/tool/mucertify/management/source_edit.php', ['id' => $certification->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new moodle_url('/admin/tool/mucertify/management/certification_assignment.php', ['id' => $certification->id]);

/** @var \tool_mucertify\local\source\base[] $sourceclasses */
$sourceclasses = \tool_mucertify\local\assignment::get_source_classes();
if (!isset($sourceclasses[$type])) {
    throw new coding_exception('Invalid source type');
}
$sourceclass = $sourceclasses[$type];

if ($source) {
    if (!$sourceclass::is_update_allowed($certification)) {
        redirect($returnurl);
    }
    $source->enable = 1;
    $source->hasassignments = $DB->record_exists('tool_mucertify_assignment', ['sourceid' => $source->id]);
} else {
    if (!$sourceclass::is_new_allowed($certification)) {
        redirect($returnurl);
    }
    $source = new stdClass();
    $source->id = null;
    $source->type = $type;
    $source->certificationid = $certification->id;
    $source->enable = 0;
    $source->hasassignments = false;
}
$source = $sourceclass::decode_datajson($source);

$formclass = $sourceclass::get_edit_form_class();
$form = new $formclass(null, ['source' => $source, 'certification' => $certification, 'context' => $context]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tool_mucertify\local\source\base::update_source($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
