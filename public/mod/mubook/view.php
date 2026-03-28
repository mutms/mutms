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
 * Interactive book TOC page.
 *
 * @package    mod_mubook
 * @copyright  2004 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mubook\local\toc;
use mod_mubook\local\chapter;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$m = optional_param('m', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('mubook', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $mubook = $DB->get_record('mubook', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $mubook = $DB->get_record('mubook', ['id' => $m], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $id = $cm->id;
}
$context = context_module::instance($cm->id);

require_course_login($course, true, $cm);
require_capability('mod/mubook:view', $context);

$currenturl = new \core\url('/mod/mubook/view.php', ['id' => $cm->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_title($mubook->name);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_other_editing_capability(['mod/mubook:editchapter', 'mod/mubook:editcontent']);

$toc = new toc($mubook);

/** @var \mod_mubook\output\view\renderer $renderer */
$renderer = $PAGE->get_renderer('mod_mubook', 'view');

$headeractions = '';
if ($PAGE->user_is_editing()) {
    if (chapter::can_create($mubook, $context)) {
        if ($toc->get_chapters()) {
            $lastchapter = $toc->get_last_chapter();
            $link = chapter::get_create_link($mubook, $lastchapter->parentid ?? $lastchapter->id, false, 0);
            $button = $link->create_button(true, false, true);
            $headeractions = $renderer->render($button);
        } else {
            $link = chapter::get_create_link($mubook, 0, false, 0);
            $button = $link->create_button(true, false, false);
            $headeractions = $renderer->render($button);
        }
    }
}
$actions = new \mod_mubook\hook\book_actions($toc, $PAGE->url, $PAGE->user_is_editing());
if ($actions->dropdown->has_items()) {
    $headeractions .= $renderer->render($actions->dropdown);
}
if ($headeractions) {
    $PAGE->add_header_action($headeractions);
}

\mod_mubook\event\course_module_viewed::create_from_mubook($mubook, $context)->trigger();

echo $OUTPUT->header();
echo $renderer->render_page($toc);
echo $OUTPUT->footer();
