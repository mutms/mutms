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
 * Delete home page.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_muhome\local\page;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$page = $DB->get_record('tool_muhome_page', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($page->contextid, IGNORE_MISSING);
if (!$context) {
    $context = context_system::instance();
}

require_login();
require_capability('tool/muhome:manage', $context);

$currenturl = new url('/admin/tool/muhome/management/page_delete.php', ['id' => $page->id]);
$returnurl = new url('/admin/tool/muhome/management/index.php', ['contextid' => $context->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$page->cohortids = array_keys(page::get_cohortvisible_menu($page->id));

$form = new \tool_muhome\local\form\page_delete(null, ['currentdata' => $page, 'context' => $context]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    page::delete($page->id);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
