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
 * List of sudoers.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_musudo_sudoers', '', null, '', ['pagelayout' => 'report', 'nosearch' => true]);

$buttons = [];

if (is_siteadmin()) {
    $url = new moodle_url('/admin/tool/musudo/management/sudoer_create.php');
    $button = new tool_mulib\output\ajax_form\button($url, get_string('sudoer_create', 'tool_musudo'));
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
    \tool_musudo\reportbuilder\local\systemreports\sudoers::class,
    context_system::instance()
);
echo $report->output();

echo $OUTPUT->footer();
