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
 * Certification browsing for learners.
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

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$catalogue = new \tool_mucertify\local\catalogue($_REQUEST);
$syscontext = context_system::instance();

$PAGE->set_url($catalogue->get_current_url());
$PAGE->set_context($syscontext);
$PAGE->set_secondary_navigation(false);

require_login();
require_capability('tool/mucertify:viewcatalogue', $syscontext);

if (!\tool_mucertify\local\util::is_mucertify_active()) {
    redirect(new moodle_url('/'));
}

$actions = new \tool_mulib\output\header_actions(get_string('catalogue_actions', 'tool_mucertify'));

$manageurl = \tool_mucertify\local\management::get_management_url();
if ($manageurl) {
    $actions->get_dropdown()->add_item(get_string('management', 'tool_mucertify'), $manageurl);
}
if (!isguestuser() && isloggedin()) {
    $mycertificationsurl = new moodle_url('/admin/tool/mucertify/my/index.php');
    $button = html_writer::link($mycertificationsurl, get_string('mycertifications', 'tool_mucertify'), ['class' => 'btn btn-secondary']);
    $actions->add_button($button);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

$PAGE->set_heading(get_string('catalogue', 'tool_mucertify'));
$PAGE->set_title(get_string('catalogue', 'tool_mucertify'));
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();

echo $catalogue->render_certifications();

echo $OUTPUT->footer();
