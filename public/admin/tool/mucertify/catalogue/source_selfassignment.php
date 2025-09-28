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
 * Confirm self-assignment to certification.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
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
$PAGE->set_url(new moodle_url('/admin/tool/mucertify/catalogue/source_selfassignment.php', ['sourceid' => $sourceid]));

require_login();
require_capability('tool/mucertify:viewcatalogue', context_system::instance());

if (!\tool_mucertify\local\util::is_mucertify_active()) {
    redirect(new moodle_url('/'));
}

$source = $DB->get_record('tool_mucertify_source', ['id' => $sourceid, 'type' => 'selfassignment'], '*', MUST_EXIST);
$certification = $DB->get_record('tool_mucertify_certification', ['id' => $source->certificationid], '*', MUST_EXIST);
$certificationcontext = context::instance_by_id($certification->contextid);

if (!\tool_mucertify\local\source\selfassignment::can_user_request($certification, $source, $USER->id)) {
    redirect(new moodle_url('/admin/tool/mucertify/catalogue/index.php'));
}

$returnurl = new moodle_url('/admin/tool/mucertify/catalogue/certification.php', ['id' => $certification->id]);

$form = new tool_mucertify\local\form\source_selfassignment(null, ['source' => $source, 'certification' => $certification]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tool_mucertify\local\source\selfassignment::signup($certification->id, $source->id);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
