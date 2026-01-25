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
 * certification management interface.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucertify\local\management;

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

$currenturl = new \core\url('/admin/tool/mucertify/management/certification_notifications.php', ['id' => $id]);

management::setup_certification_page($currenturl, $context, $certification, 'certification_notifications');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('notifications', 'tool_mucertify'), 2, ['h3']);
echo \tool_mucertify\local\notification_manager::render_notifications($certification->id);

if ($certification->programid1) {
    $program1 = $DB->get_record('tool_muprog_program', ['id' => $certification->programid1]);
    if ($program1) {
        echo $OUTPUT->heading(format_string($program1->fullname), 2, ['h3']);
        echo \tool_muprog\local\notification_manager::render_notifications($certification->programid1, 'tool_muprog_1_notifications');
    }
}
if (isset($certification->recertify) && $certification->programid2 && $certification->programid2 != $certification->programid1) {
    $program2 = $DB->get_record('tool_muprog_program', ['id' => $certification->programid2]);
    if ($program2) {
        echo $OUTPUT->heading(format_string($program2->fullname), 2, ['h3']);
        echo \tool_muprog\local\notification_manager::render_notifications($certification->programid2, 'tool_muprog_2_notifications');
    }
}

echo $OUTPUT->footer();
