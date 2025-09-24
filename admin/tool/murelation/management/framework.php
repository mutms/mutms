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
 * Relation frameworks.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\output\header_actions;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require_once('../../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

require_login();
require_capability('tool/murelation:viewframeworks', $syscontext);

$framework = $DB->get_record('tool_murelation_framework', ['id' => $id], '*', MUST_EXIST);

$pageurl = new \moodle_url('/admin/tool/murelation/management/framework.php', ['id' => $framework->id]);

\tool_murelation\local\management::setup_framework_page($pageurl, $framework, 'framework_details');

/** @var \tool_murelation\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_murelation', 'management');

$actions = new header_actions(get_string('management_framework_actions', 'tool_murelation'));
if (has_capability('tool/murelation:manageframeworks', $syscontext)) {
    if (\tool_murelation\local\framework::is_deletable($framework->id)) {
        $url = new moodle_url('/admin/tool/murelation/management/framework_delete.php', ['id' => $framework->id]);
        $link = new tool_mulib\output\ajax_form\link($url, get_string('framework_delete', 'tool_murelation'), 'i/delete');
        $link->add_class('text-danger');
        $link->set_submitted_action($link::SUBMITTED_ACTION_REDIRECT);
        $actions->get_dropdown()->add_ajax_form($link);
    }
    $url = new \moodle_url('/admin/tool/murelation/management/framework_update.php', ['id' => $framework->id]);
    $button = new \tool_mulib\output\ajax_form\button($url, get_string('framework_update', 'tool_murelation'));
    $button->set_form_size('xl');
    $actions->add_button($button);
}
if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

echo $managementoutput->render_framework($framework);

echo $OUTPUT->footer();
