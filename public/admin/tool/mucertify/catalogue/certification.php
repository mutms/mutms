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
/** @var stdClass $USER */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

$PAGE->set_url(new moodle_url('/admin/tool/mucertify/catalogue/certification.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_secondary_navigation(false);

require_login();
require_capability('tool/mucertify:viewcatalogue', context_system::instance());

if (!\tool_mucertify\local\util::is_mucertify_active()) {
    redirect(new moodle_url('/'));
}

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $id]);
if (!$certification || $certification->archived) {
    if ($certification) {
        $context = context::instance_by_id($certification->contextid);
    } else {
        $context = context_system::instance();
    }
    if (has_capability('tool/mucertify:view', $context)) {
        if ($certification) {
            redirect(new moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]));
        } else {
            redirect(new moodle_url('/admin/tool/mucertify/management/index.php'));
        }
    } else {
        redirect(new moodle_url('/admin/tool/mucertify/catalogue/index.php'));
    }
}
$certificationcontext = context::instance_by_id($certification->contextid);

$assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $USER->id]);
if ($assignment && !$assignment->archived) {
    redirect(new moodle_url('/admin/tool/mucertify/my/certification.php', ['id' => $id]));
}

if (!\tool_mucertify\local\catalogue::is_certification_visible($certification)) {
    if (has_capability('tool/mucertify:view', $certificationcontext)) {
        redirect(new moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]));
    } else {
        redirect(new moodle_url('/admin/tool/mucertify/catalogue/index.php'));
    }
}

$actions = new \tool_mulib\output\header_actions(get_string('catalogue_actions', 'tool_mucertify'));

$manageurl = \tool_mucertify\local\management::get_management_url();
if ($manageurl) {
    $actions->get_dropdown()->add_item(get_string('management', 'tool_mucertify'), $manageurl);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

/** @var \tool_mucertify\output\catalogue\renderer $catalogueoutput */
$catalogueoutput = $PAGE->get_renderer('tool_mucertify', 'catalogue');

$PAGE->set_title(get_string('catalogue', 'tool_mucertify'));
$PAGE->navbar->add(get_string('catalogue', 'tool_mucertify'), new moodle_url('/admin/tool/mucertify/catalogue/'));
$PAGE->navbar->add(format_string($certification->fullname));

echo $OUTPUT->header();

echo $catalogueoutput->render_certification($certification);

echo $OUTPUT->footer();
