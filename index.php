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
 * This page lists all the instances of book in a particular course
 *
 * @package    mod_mubook
 * @copyright  2004 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course, true);

$strbooks = get_string('modulenameplural', 'mod_mubook');
$strbook = get_string('modulename', 'mod_mubook');
$strname = get_string('name');
$strintro = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/mubook/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname . ': ' . $strbooks);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strbooks);

\mod_mubook\event\course_module_instance_list_viewed::create_from_course($course)->trigger();

echo $OUTPUT->header();

// Get all the appropriate data.
if (!$books = get_all_instances_in_course('mubook', $course)) {
    notice(get_string('thereareno', 'moodle', $strbooks), "$CFG->wwwroot/course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = course_get_format($course)->get_generic_section_name();
    $table->head  = [$strsectionname, $strname, $strintro];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head  = [$strlastmodified, $strname, $strintro];
    $table->align = ['left', 'left', 'left'];
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($books as $book) {
    $cm = $modinfo->get_cm($book->coursemodule);
    if ($usesections) {
        $printsection = '';
        if ($book->section !== $currentsection) {
            if ($book->section) {
                $printsection = get_section_name($course, $book->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $book->section;
        }
    } else {
        $printsection = html_writer::tag('span', userdate($book->timemodified), ['class' => 'smallinfo']);
    }

    // Hidden modules are dimmed.
    $class = $book->visible ? null : ['class' => 'dimmed'];

    $table->data[] = [
        $printsection,
        html_writer::link(new \core\url('/mod/mubook/view.php', ['id' => $cm->id]), format_string($book->name), $class),
        format_module_intro('mubook', $book, $cm->id)];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
