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
 * Certification management interface.
 *
 * @package    tool_mucertify
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucertify\local\management;
use tool_mulib\output\header_actions;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:view', $context);

$currenturl = new \core\url('/admin/tool/mucertify/management/certification.php', ['id' => $id]);

management::setup_certification_page($currenturl, $context, $certification, 'certification_general');
$PAGE->set_docs_path('https://github.com/mutms/moodle-tool_mucertify/wiki/Certification-settings');

/** @var \tool_mucertify\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_mucertify', 'management');

$actions = new header_actions(get_string('management_certification_general_actions', 'tool_mucertify'));
if ($certification->archived && has_capability('tool/mucertify:delete', $context)) {
    $url = new \core\url('/admin/tool/mucertify/management/certification_delete.php', ['id' => $certification->id]);
    $link = new tool_mulib\output\ajax_form\link($url, get_string('certification_delete', 'tool_mucertify'), 'i/delete');
    $link->add_class('text-danger');
    $link->set_form_size('sm');
    $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
    $actions->get_dropdown()->add_ajax_form($link);
}
if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

$buttons = [];
if (has_capability('tool/mucertify:edit', $context)) {
    $url = new \core\url('/admin/tool/mucertify/management/certification_update.php', ['id' => $certification->id]);
    $editbutton = new tool_mulib\output\ajax_form\button($url, get_string('edit'));
    $buttons[] = $OUTPUT->render($editbutton);
}

echo $managementoutput->render_certification_general($certification);

if ($buttons) {
    $buttons = implode(' ', $buttons);
    echo $OUTPUT->box($buttons, 'buttons');
}

echo $OUTPUT->footer();
