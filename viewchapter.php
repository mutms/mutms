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
 * Interactive book chapter page.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mubook\local\toc;
use mod_mubook\local\chapter;
use mod_mubook\local\content;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$record = $DB->get_record('mubook_chapter', ['id' => $id], '*', MUST_EXIST);
$mubook = $DB->get_record('mubook', ['id' => $record->mubookid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_course_login($course, true, $cm);
require_capability('mod/mubook:view', $context);

$currenturl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $record->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$cman = core\di::get(\mod_mubook\local\content_manager::class);
$toc = new toc($mubook);

$chapter = $toc->get_chapter($record->id);
if (!$chapter) {
    redirect(new \core\url('/mod/mubook/view.php', ['id' => $cm->id]));
}

$parent = null;
if ($toc->is_orphaned_chapter($chapter->id)) {
    if (!$chapter->can_move() && !$chapter->can_delete()) {
        redirect(new \core\url('/mod/mubook/view.php', ['id' => $cm->id]));
    }
} else if ($chapter->parentid) {
    $parent = $toc->get_chapter($chapter->parentid);
}

$PAGE->set_other_editing_capability(['mod/mubook:editchapter', 'mod/mubook:editcontent']);
$PAGE->set_title(implode(moodle_page::TITLE_SEPARATOR, [$mubook->name, $chapter->title]));
$PAGE->add_body_class('limitedwidth');
$PAGE->set_secondary_navigation(false);

if ($parent) {
    $PAGE->navbar->add(
        $toc->format_chapter_numbers($parent->id) . ' ' . $parent->format_title(),
        new \core\url('/mod/mubook/viewchapter.php', ['id' => $parent->id])
    );
}
$PAGE->navbar->add($toc->format_chapter_numbers($chapter->id) . ' ' . $chapter->format_title());

/** @var \mod_mubook\output\viewchapter\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_mubook', 'viewchapter');

// Hide book intro and completion when showing individual chapters.
$PAGE->activityheader->set_hidecompletion(true);
$PAGE->activityheader->set_description('');

$actions = new \mod_mubook\hook\book_actions($toc, $PAGE->url, $PAGE->user_is_editing());
if ($actions->dropdown->has_items()) {
    $PAGE->add_header_action($renderer->render($actions->dropdown));
}

\mod_mubook\event\chapter_viewed::create_from_chapter($chapter)->trigger();

$lastchapter = $toc->get_last_chapter();
if ($lastchapter && $chapter->id == $lastchapter->id) {
    // We cheat a bit here in assuming that viewing
    // the last page means the user viewed the whole book.
    $completion = new \completion_info($course);
    $completion->set_module_viewed($cm);
}

echo $OUTPUT->header();
echo $renderer->render_page($chapter, $toc);
echo $OUTPUT->footer();
