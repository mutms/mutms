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
 * Create content.
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

$chapterid = required_param('chapterid', PARAM_INT);
$type = required_param('type', PARAM_ALPHANUM);
$sortorder = optional_param('sortorder', 0, PARAM_INT);
$fromcreatechapterid = optional_param('fromcreatechapterid', -1, PARAM_INT);

$chapterrecord = $DB->get_record('mubook_chapter', ['id' => $chapterid], '*', MUST_EXIST);
$mubook = $DB->get_record('mubook', ['id' => $chapterrecord->mubookid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/mubook:view', $context);
require_capability('mod/mubook:editcontent', $context);

$currenturl = new url('/mod/mubook/management/content_create.php', ['chapterid' => $chapterid, 'type' => $type, 'sortorder' => $sortorder]);
$returnurl = new url('/mod/mubook/viewchapter.php', ['id' => $chapterid]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$cman = \core\di::get(\mod_mubook\local\content_manager::class);
$toc = new toc($mubook);

$chapter = $toc->get_chapter($chapterrecord->id);
if (!$chapter || $toc->is_orphaned_chapter($chapter->id)) {
    redirect($returnurl);
}

$classname = $cman->get_class($type);
if (!$classname || !$classname::can_create($chapter, $mubook, $context)) {
    redirect($returnurl);
}
$expectedurl = $classname::get_create_url($chapter, $sortorder);
if (!$currenturl->compare($expectedurl, URL_MATCH_BASE)) {
    throw new \core\exception\coding_exception('Incorrect content create page used.');
}

$formclass = $classname::get_create_form_classname();
$formclass::setup_content_page($chapter, $toc);

$form = $formclass::init_form($chapter, $sortorder, $toc, $fromcreatechapterid);

if ($form->is_cancelled()) {
    if ($fromcreatechapterid === 0) {
        $returnurl = new url('/mod/mubook/view.php', ['id' => $cm->id]);
        $returnurl->set_anchor('mubook-chapter-' . $chapter->id);
    } else if ($fromcreatechapterid > 0) {
        if ($chapter->parentid) {
            if ($fromcreatechapterid == $chapter->parentid) {
                $returnurl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $fromcreatechapterid]);
            } else {
                $returnurl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $chapter->parentid]);
            }
        } else {
            $returnurl = new url('/mod/mubook/viewchapter.php', ['id' => $fromcreatechapterid]);
        }
    }
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    $content = $classname::create($data);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('content_create_a', 'mod_mubook', $classname::get_name()));
echo $form->render();
echo $OUTPUT->footer();
