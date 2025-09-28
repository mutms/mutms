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
 * Update sudoer.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_musudo\local\util;
use tool_musudo\local\sudoer;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('tool_musudo_sudoers', '', null, '', ['pagelayout' => 'report', 'nosearch' => true]);

util::require_admin();

$sudoer = $DB->get_record('tool_musudo_sudoer', ['id' => $id], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $sudoer->userid]);

$returnurl = new moodle_url('/admin/tool/musudo/index.php');

$form = new \tool_musudo\local\form\sudoer_update(null, ['sudoer' => $sudoer, 'user' => $user]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $sudoer = sudoer::update($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
