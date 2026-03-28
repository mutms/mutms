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
 * Incognito log-in-as.
 *
 * @package    tool_muloginas
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muloginas\local\loginas;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */
/** @var stdClass $SITE */

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
$newprivatewindow = true;
// Only allow log-in-as in brand-new browser window that never accessed the site,
// this is as close as we get to detecting new incognito windows.
if (!empty($_COOKIE)) {
    $newprivatewindow = false;
} else if (isset($_SERVER['HTTP_COOKIE'])) {
    $newprivatewindow = false;
} else if (isset($_SERVER['HTTP_REFERER'])) {
    $newprivatewindow = false;
}

require_once('../../../config.php');

$token = optional_param('token', '', PARAM_RAW);

$syscontext = context_system::instance();

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/admin/tool/muloginas/loginas.php'));
$PAGE->set_pagelayout('login');
$heading = get_string('info_heading', 'tool_muloginas', format_string($SITE->shortname));
$PAGE->set_heading($heading);

$warning = null;
if (!empty($_COOKIE[loginas::COOKIE_NAME])) {
    if ($USER->id) {
        if ($DB->record_exists('tool_muloginas_request', ['targetsid' => session_id(), 'targetuserid' => $USER->id])) {
            $warning = get_string('loggedoutprev', 'tool_muloginas', fullname($USER));
            core\session\manager::init_empty_session(true);
            $newprivatewindow = true;
        }
    } else {
        if ($DB->record_exists('tool_muloginas_request', ['targetsid' => session_id()])) {
            core\session\manager::init_empty_session(true);
            $newprivatewindow = true;
        }
    }
}

if (!$newprivatewindow || $USER->id) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($heading);

    $request = $DB->get_record('tool_muloginas_request', ['token' => $token]);
    if ($request && $request->userid == $USER->id) {
        $info = get_string('error_notincognito', 'tool_muloginas');
    } else {
        $info = get_string('error_incognitoproglem', 'tool_muloginas');
    }

    echo '<div class="alert alert-danger">';
    echo $info;
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

$result = loginas::validate_request($token);
if (!$result) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($heading);

    echo '<div class="alert alert-danger">';
    echo get_string('error_invalidrequest', 'tool_muloginas');
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

list($targetuser, $request, $user) = $result;

loginas::log_in_as($targetuser, $request, $user);

if ($warning !== null) {
    \core\notification::add($warning, \core\output\notification::NOTIFY_ERROR);
}

redirect(new moodle_url('/'));
