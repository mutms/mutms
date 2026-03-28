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
 * Start sudo session.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_musudo\local\sudoer;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require('../../../config.php');

require_login();

$returnurl = new moodle_url('/');
if (!sudoer::can_sudo()) {
    redirect($returnurl);
}
if (sudoer::is_sudo_started()) {
    redirect($returnurl);
}

$factor = optional_param('factor', null, PARAM_ALPHANUM);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/musudo/sudo_start.php'));
$title = get_string('sudo_start', 'tool_musudo');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_pagelayout('login');
$PAGE->set_cacheable(false);

$sudoer = $DB->get_record('tool_musudo_sudoer', ['userid' => $USER->id], '*', MUST_EXIST);

$form = new \tool_musudo\local\form\sudo_start(null, ['sudoer' => $sudoer, 'factor' => $factor]);
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if (sudoer::start_sudo()) {
        $message = get_string('sudo_started', 'tool_musudo');
        redirect($returnurl, $message, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $message = get_string('error_sudo_start', 'tool_musudo');
        redirect($returnurl, $message, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo '<div class="mfa-verify-form">';
echo $form->render();
echo '</div>';
echo $OUTPUT->footer();
