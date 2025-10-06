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

namespace tool_muprog\output\catalogue;

use tool_muprog\local\allocation;
use tool_muprog\local\program;
use tool_muprog\local\util;
use tool_muprog\local\content\item,
    tool_muprog\local\content\top,
    tool_muprog\local\content\set,
    tool_muprog\local\content\course;
use stdClass;

/**
 * Program catalogue renderer.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render program general.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program(\stdClass $program): string {
        global $CFG, $DB;

        $strnotset = get_string('notset', 'tool_muprog');

        $context = \context::instance_by_id($program->contextid);
        $fullname = format_string($program->fullname);

        $description = file_rewrite_pluginfile_urls($program->description, 'pluginfile.php', $context->id, 'tool_muprog', 'description', $program->id);
        $description = format_text($description, $program->descriptionformat, ['context' => $context]);

        $tagsdiv = '';
        if ($CFG->usetags) {
            $tags = \core_tag_tag::get_item_tags('tool_muprog', 'tool_muprog_program', $program->id);
            if ($tags) {
                $tagsdiv = $this->output->tag_list($tags, '', 'program-tags mb-3');
            }
        }

        $programimage = '';
        $presentation = (array)json_decode($program->presentationjson);
        if (!empty($presentation['image'])) {
            $imageurl = \moodle_url::make_file_url(
                "$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/tool_muprog/image/' . $program->id . '/' . $presentation['image'],
                false
            );
            $programimage = '<div class="programimage">' . \html_writer::img($imageurl, '') . '</div>';
        }

        $result = $this->output->heading($fullname);
        $result .= $tagsdiv;
        $result .= "<div class='d-flex'><div class='w-100'>$description</div><div class='flex-shrink-1'>$programimage</div></div>";

        $details = new \tool_mulib\output\entity_details();

        $details->add(
            get_string('programstatus', 'tool_muprog'),
            get_string('errornoallocation', 'tool_muprog')
        );
        $details->add(
            get_string('allocationstart', 'tool_muprog'),
            (isset($program->timeallocationstart) ? userdate($program->timeallocationstart) : $strnotset)
        );
        $details->add(
            get_string('allocationend', 'tool_muprog'),
            (isset($program->timeallocationend) ? userdate($program->timeallocationend) : $strnotset)
        );
        $handler = \tool_muprog\customfield\program_handler::create();
        foreach ($handler->get_instance_data($program->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }

        $result .= $this->output->render($details);

        $actions = [];
        /** @var \tool_muprog\local\source\base[] $sourceclasses */ // Type hack.
        $sourceclasses = allocation::get_source_classes();
        foreach ($sourceclasses as $type => $classname) {
            $source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => $type]);
            if (!$source) {
                continue;
            }
            $actions = array_merge($actions, $classname::get_catalogue_actions($program, $source));
        }

        if ($actions) {
            $result .= '<div class="buttons mb-5">';
            $result .= implode(' ', $actions);
            $result .= '</div>';
        }

        $result .= $this->output->heading(get_string('tabcontent', 'tool_muprog'), 3);

        $result .= $this->render_program_content($program);

        return $result;
    }

    /**
     * Render program content.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program_content(stdClass $program): string {
        global $DB;

        $top = program::load_content($program->id);

        $rows = [];
        $renderercolumns = function (item $item, $itemdepth) use (&$renderercolumns, &$rows, &$DB): void {
            $fullname = $item->get_fullname();
            $id = $item->get_id();
            $padding = str_repeat('&nbsp;', $itemdepth * 6);

            $completiontype = '';
            if ($item instanceof set) {
                $completiontype = $item->get_sequencetype_info();
            }
            if ($completiondelay = $item->get_completiondelay()) {
                if ($completiontype !== '') {
                    $completiontype .= '<br />';
                }
                $completiontype .= '<small>' . get_string('completiondelay', 'tool_muprog') . ': ' . util::format_duration($completiondelay) . '</small>';
            }

            if ($item instanceof course) {
                $courseid = $item->get_courseid();
                $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
                if ($coursecontext) {
                    $canaccesscourse = false;
                    if (has_capability('moodle/course:view', $coursecontext)) {
                        $canaccesscourse = true;
                    } else {
                        $course = get_course($courseid);
                        if ($course && can_access_course($course, null, '', true)) {
                            $canaccesscourse = true;
                        }
                    }
                    if ($canaccesscourse) {
                        $detailurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
                        $fullname = \html_writer::link($detailurl, $fullname);
                    }
                } else {
                    $fullname .= ' <span class="badge bg-danger">' . get_string('errorcoursemissing', 'tool_muprog') . '</span>';
                }
            }

            if ($item instanceof top) {
                $itemname = $this->output->pix_icon('itemtop', get_string('program', 'tool_muprog'), 'tool_muprog') . '&nbsp;' . $fullname;
            } else if ($item instanceof course) {
                $itemname = $padding . $this->output->pix_icon('itemcourse', get_string('course'), 'tool_muprog') . $fullname;
            } else {
                $itemname = $padding . $this->output->pix_icon('itemset', get_string('set', 'tool_muprog'), 'tool_muprog') . $fullname;
            }

            $row = [$itemname, $completiontype];

            $rows[] = $row;

            foreach ($item->get_children() as $child) {
                $renderercolumns($child, $itemdepth + 1);
            }
        };
        $renderercolumns($top, 0);

        $table = new \html_table();
        $table->head = [get_string('item', 'tool_muprog'), get_string('sequencetype', 'tool_muprog')];
        $table->id = 'program_content';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $rows;

        return \html_writer::table($table);
    }
}
