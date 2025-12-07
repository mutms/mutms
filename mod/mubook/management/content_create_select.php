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
 * Select content type for creation - result is the redirect.
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

$chapterid = required_param('chapterid', PARAM_INT);
$sortorder = optional_param('sortorder', 0, PARAM_INT);

$chapterrecord = $DB->get_record('mubook_chapter', ['id' => $chapterid], '*', MUST_EXIST);
$mubook = $DB->get_record('mubook', ['id' => $chapterrecord->mubookid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/mubook:view', $context);
require_capability('mod/mubook:editcontent', $context);

$currenturl = new url('/mod/mubook/management/content_create_select.php', ['chapterid' => $chapterid, 'sortorder' => $sortorder]);
$returnurl = new url('/mod/mubook/viewchapter.php', ['id' => $chapterid]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$cman = \core\di::get(\mod_mubook\local\content_manager::class);
$toc = new toc($mubook);

$chapter = $toc->get_chapter($chapterrecord->id);
if (!$chapter || $toc->is_orphaned_chapter($chapter->id)) {
    redirect($returnurl);
}

$form = new \mod_mubook\local\form\content_create_select(null, ['chapter' => $chapter, 'sortorder' => $sortorder, 'toc' => $toc]);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $classname = $cman->get_class($data->type);
    if ($classname && $classname::can_create($chapter, $mubook, $context)) {
        $createurl = $classname::get_create_url($chapter, $data->sortorder);
        $form->ajax_form_submitted($createurl);
    }
}

$form->ajax_form_render();
