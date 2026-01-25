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
// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch

/**
 * Custom home pages management.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muhome\local\management;
use core\url;

/** @var core_renderer $OUTPUT */

require('../../../../config.php');

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($contextid) {
    $context = context::instance_by_id($contextid);
} else {
    $context = context_system::instance();
}
unset($contextid);
if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
    throw new moodle_exception('invalidcontext');
}

require_login();
require_capability('tool/muhome:view', $context);

$currenturl = new url('/admin/tool/muhome/management/index.php', ['contextid' => $context->id]);

management::setup_index_page($currenturl, $context);

echo $OUTPUT->header();
$report = \core_reportbuilder\system_report_factory::create(
    \tool_muhome\reportbuilder\local\systemreports\pages::class,
    $context
);
echo $report->output();
echo $OUTPUT->footer();
