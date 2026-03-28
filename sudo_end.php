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
 * End sudo session.
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

define('TOOL_MUSUDO_END_SCRIPT', true);

require('../../../config.php');

require_login();

$returnurl = new moodle_url('/');
if (!sudoer::can_sudo()) {
    redirect($returnurl);
}
if (!sudoer::is_sudo_started()) {
    redirect($returnurl);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/musudo/sudo_end.php'));

require_sesskey();
sudoer::end_sudo();

$message = get_string('sudo_ended', 'tool_musudo');
redirect($returnurl, $message, 0, \core\output\notification::NOTIFY_SUCCESS);
