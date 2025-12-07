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
 * Add a new chapter, optionally with some content.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mubook\local\chapter;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

define('AJAX_SCRIPT', true);

require('../../../config.php');

$mubookid = required_param('mubookid', PARAM_INT);
$subchapter = optional_param('subchapter', 0, PARAM_BOOL);
$position = optional_param('position', 0, PARAM_INT);
$fromcreatechapterid = optional_param('fromcreatechapterid', -1, PARAM_INT);

$mubook = $DB->get_record('mubook', ['id' => $mubookid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('mubook', $mubook->id, $mubook->course, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/mubook:view', $context);
require_capability('mod/mubook:editchapter', $context);

$currenturl = new \core\url('/mod/mubook/management/chapter_create.php', ['mubookid' => $mubook->id]);
if ($position) {
    $currenturl->param('position', $position);
}
$returnurl = new \core\url('/mod/mubook/view.php', ['id' => $cm->id]);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);

// Check chapter permissions.
if (!chapter::can_create($mubook, $context)) {
    redirect($returnurl);
}

$toc = new \mod_mubook\local\toc($mubook);
if (!$toc->get_chapters()) {
    $subchapter = 0;
}

$form = new \mod_mubook\local\form\chapter_create(null, [
    'toc' => $toc,
    'position' => $position,
    'subchapter' => $subchapter,
    'fromcreatechapterid' => $fromcreatechapterid,
]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $chapter = chapter::create($data);
    $toc = \mod_mubook\local\toc::fix_sortorders($mubook->id);
    $returnurl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $chapter->id]);

    if (empty($data->contentcreate)) {
        if ($fromcreatechapterid == 0) {
            $returnurl = new \core\url('/mod/mubook/view.php', ['id' => $cm->id]);
        } else if ($fromcreatechapterid > 0) {
            if ($chapter->parentid) {
                if ($fromcreatechapterid == $chapter->parentid) {
                    $returnurl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $fromcreatechapterid]);
                } else {
                    $returnurl = new \core\url('/mod/mubook/viewchapter.php', ['id' => $chapter->parentid]);
                }
            }
        }
    } else {
        $cman = \core\di::get(\mod_mubook\local\content_manager::class);
        $contentclasses = $cman->get_available_classes();
        if (isset($contentclasses[$data->contentcreate])) {
            /** @var class-string<\mod_mubook\local\content> $contentclass */
            $contentclass = $contentclasses[$data->contentcreate];
            $returnurl = $contentclass::get_create_url($chapter, 0);
            if ($fromcreatechapterid >= 0) {
                $returnurl->param('fromcreatechapterid', $fromcreatechapterid);
            }
        }
    }

    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
