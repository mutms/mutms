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
 * Update content.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mubook\local\toc;
use core\url;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

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

$currenturl = new url('/mod/mubook/management/content_update.php', ['id' => $contentrecord->id]);
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
if (!$content->can_update()) {
    redirect($returnurl);
}
$expectedurl = $content->get_update_url();
if (!$currenturl->compare($expectedurl, URL_MATCH_BASE)) {
    throw new \core\exception\coding_exception('Incorrect content update page used.');
}

$formclass = $content->get_update_form_classname();
$formclass::setup_content_page($chapter, $toc);

$form = $formclass::init_form($content, $chapter, $toc);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    $content = $content->update($data);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('content_update_a', 'mod_mubook', $content::get_name()));
echo $form->render();
echo $OUTPUT->footer();
