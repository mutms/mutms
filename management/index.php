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
 * List of all credit frameworks.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutrain\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require_once('../../../../config.php');

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($contextid) {
    $context = context::instance_by_id($contextid);
} else {
    $context = context_system::instance();
}

require_login();
require_capability('tool/mutrain:viewframeworks', $context);

if ($context->contextlevel == CONTEXT_SYSTEM) {
    $category = null;
} else if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', ['id' => $context->instanceid], '*', MUST_EXIST);
} else {
    throw new moodle_exception('invalidcontext');
}

$currenturl = new core\url('/admin/tool/mutrain/management/index.php', ['contextid' => $context->id]);

management::setup_index_page($currenturl, $context);

$buttons = [];

if (has_capability('tool/mutrain:manageframeworks', $context)) {
    $url = new core\url('/admin/tool/mutrain/management/framework_create.php', ['contextid' => $context->id]);
    $button = new tool_mulib\output\ajax_form\button($url, get_string('framework_create', 'tool_mutrain'));
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
    \tool_mutrain\reportbuilder\local\systemreports\frameworks::class,
    $context
);
echo $report->output();

echo $OUTPUT->footer();
