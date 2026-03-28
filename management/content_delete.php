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
 * Delete content.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use mod_mubook\local\toc;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

define('AJAX_SCRIPT', true);

require('../../../config.php');

$id = required_param('id', PARAM_INT);

$contentrecord = $DB->get_record('mubook_content', ['id' => $id], '*', MUST_EXIST);
$chapterrecord = $DB->get_record('mubook_chapter', ['id' => $contentrecord->chapterid], '*', MUST_EXIST);
$mubook = $DB->get_record('mubook', ['id' => $chapterrecord->mubookid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/mubook:view', $context);
require_capability('mod/mubook:editcontent', $context);

$currenturl = new url('/mod/mubook/management/content_delete.php', ['id' => $contentrecord->id]);
$returnurl = new url('/mod/mubook/viewchapter.php', ['id' => $chapterrecord->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$cman = \core\di::get(\mod_mubook\local\content_manager::class);
$toc = new toc($mubook);

$chapter = $toc->get_chapter($chapterrecord->id);
if (!$chapter || $toc->is_orphaned_chapter($chapter->id)) {
    redirect($returnurl);
}
$content = $cman->create_instance($contentrecord, $chapter);
if (!$content->can_delete()) {
    redirect($returnurl);
}

$form = new \mod_mubook\local\form\content_delete(null, ['content' => $content, 'chapter' => $chapter, 'toc' => $toc]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $content->delete();
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
