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
 * List of all relation frameworks.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require_once('../../../../config.php');

$syscontext = context_system::instance();

require_login();
require_capability('tool/murelation:viewframeworks', $syscontext);

$pageurl = new moodle_url('/admin/tool/murelation/management/index.php');

\tool_murelation\local\management::setup_index_page($pageurl);

$buttons = [];

if (has_capability('tool/murelation:manageframeworks', $syscontext)) {
    $url = new moodle_url('/admin/tool/murelation/management/framework_create.php');
    $button = new tool_mulib\output\ajax_form\button($url, get_string('framework_create', 'tool_murelation'));
    $button->set_form_size('xl');
    $button->set_submitted_action($button::SUBMITTED_ACTION_REDIRECT);
    $buttons[] = $OUTPUT->render($button);
}

if ($buttons) {
    $action = '';
    if ($buttons) {
        $action .= implode($buttons);
    }
    $PAGE->add_header_action($action);
}

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_murelation\reportbuilder\local\systemreports\frameworks::class,
    $syscontext
);
echo $report->output();

echo $OUTPUT->footer();
