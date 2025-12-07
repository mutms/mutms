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
 * List of all external queries.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var stdClass $CFG */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require(__DIR__ . '/../../../../config.php');
require_once("$CFG->libdir/adminlib.php");

require_login();
admin_externalpage_setup('tool_mulib_extdb_queries', '', null, '', ['nosearch' => true]);

$syscontext = context_system::instance();
require_capability('tool/mulib:useextdb', $syscontext);

$PAGE->set_heading(get_string('extdb_queries', 'tool_mulib'));
$PAGE->set_secondary_navigation(false);

if ($DB->record_exists('tool_mulib_extdb_server', [])) {
    $url = new moodle_url('/admin/tool/mulib/extdb/query_create.php');
    $button = new \tool_mulib\output\ajax_form\button(
        $url,
        get_string('extdb_query_create', 'tool_mulib')
    );
    $button->set_form_size('xl');
    $PAGE->add_header_action($OUTPUT->render($button));
}

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_mulib\reportbuilder\local\systemreports\queries::class,
    context_system::instance()
);
echo $report->output();

echo $OUTPUT->footer();
