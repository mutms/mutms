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

namespace tool_muprog\output\management;

use tool_muprog\local\notification_manager;
use tool_muprog\local\allocation;
use tool_muprog\local\management;
use tool_muprog\local\program;
use tool_muprog\local\util;
use tool_muprog\local\content\item,
    tool_muprog\local\content\top,
    tool_muprog\local\content\set,
    tool_muprog\local\content\course,
    tool_muprog\local\content\training;
use stdClass, moodle_url, html_writer;

/**
 * Program management renderer.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render program.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program_general(stdClass $program): string {
        global $CFG;

        $context = \context::instance_by_id($program->contextid);

        $programimage = '';
        $presentation = (array)json_decode($program->presentationjson);
        if (!empty($presentation['image'])) {
            $imageurl = moodle_url::make_file_url(
                "$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/tool_muprog/image/' . $program->id . '/' . $presentation['image'],
                false
            );
            $programimage = '<div class="programimage">' . html_writer::img($imageurl, '') . '</div>';
        }

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('programname', 'tool_muprog'), format_string($program->fullname));
        $details->add(get_string('programidnumber', 'tool_muprog'), s($program->idnumber));
        $url = new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);
        $details->add(get_string('category'), html_writer::link($url, $context->get_context_name(false)));
        $details->add(get_string('creategroups', 'tool_muprog'), ($program->creategroups ? get_string('yes') : get_string('no')));
        if ($CFG->usetags) {
            $tags = \core_tag_tag::get_item_tags('tool_muprog', 'tool_muprog_program', $program->id);
            if ($tags) {
                $details->add(get_string('tags'), $this->output->tag_list($tags, '', 'program-tags'));
            }
        }
        $description = file_rewrite_pluginfile_urls($program->description, 'pluginfile.php', $context->id, 'tool_muprog', 'description', $program->id);
        $description = format_text($description, $program->descriptionformat, ['context' => $context]);
        if (trim($description) === '') {
            $description = '&nbsp;';
        }
        $details->add(get_string('description'), $description);
        $archived = $program->archived ? get_string('yes') : get_string('no');
        if (has_capability('tool/muprog:edit', $context)) {
            if ($program->archived) {
                $url = new moodle_url('/admin/tool/muprog/management/program_restore.php', ['id' => $program->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('program_restore', 'tool_muprog'), 'i/settings');
            } else {
                $url = new moodle_url('/admin/tool/muprog/management/program_archive.php', ['id' => $program->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('program_archive', 'tool_muprog'), 'i/settings');
            }
            $action->set_form_size('sm');
            $archived .= $this->output->render($action);
        }
        $details->add(get_string('archived', 'tool_muprog'), $archived);

        $handler = \tool_muprog\customfield\program_handler::create();
        foreach ($handler->get_instance_data($program->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }

        $result = $this->output->render($details);

        if (!$programimage) {
            return $result;
        }

        return "<div class='d-flex'><div class='w-100'>$result</div><div class='flex-shrink-1'>$programimage</div></div>";
    }

    /**
     * Render program allocation.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program_allocation(stdClass $program): string {
        $details = new \tool_mulib\output\entity_details();

        $details->add(
            get_string('allocationstart', 'tool_muprog'),
            $program->timeallocationstart ? userdate($program->timeallocationstart) : get_string('notset', 'tool_muprog')
        );
        $details->add(
            get_string('allocationend', 'tool_muprog'),
            $program->timeallocationend ? userdate($program->timeallocationend) : get_string('notset', 'tool_muprog')
        );

        return $this->output->render($details);
    }

    /**
     * Rdener program scheduling.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program_scheduling(stdClass $program): string {
        $details = new \tool_mulib\output\entity_details();

        $start = (object)json_decode($program->startdatejson);
        $types = program::get_program_startdate_types();
        if ($start->type === 'date') {
            $startstr = userdate($start->date);
        } else if ($start->type === 'delay') {
            $startstr = $types[$start->type] . ' - ' . util::format_delay($start->delay);
        } else {
            $startstr = $types[$start->type];
        }
        $details->add(get_string('programstart', 'tool_muprog'), $startstr);

        $due = (object)json_decode($program->duedatejson);
        $types = program::get_program_duedate_types();
        if ($due->type === 'date') {
            $duestr = userdate($due->date);
        } else if ($due->type === 'delay') {
            $duestr = $types[$due->type] . ' - ' . util::format_delay($due->delay);
        } else {
            $duestr = $types[$due->type];
        }
        $details->add(get_string('programdue', 'tool_muprog'), $duestr);

        $end = (object)json_decode($program->enddatejson);
        $types = program::get_program_enddate_types();
        if ($end->type === 'date') {
            $endstr = userdate($end->date);
        } else if ($end->type === 'delay') {
            $endstr = $types[$end->type] . ' - ' . util::format_delay($end->delay);
        } else {
            $endstr = $types[$end->type];
        }
        $details->add(get_string('programend', 'tool_muprog'), $endstr);

        return $this->output->render($details);
    }

    /**
     * Render program visibility.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program_visibility(stdClass $program): string {
        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('publicaccess', 'tool_muprog'), ($program->publicaccess ? get_string('yes') : get_string('no')));
        $cohorts = management::fetch_current_cohorts_menu($program->id);
        if ($cohorts) {
            $cohrotsstr = implode(', ', array_map('format_string', $cohorts));
        } else {
            $cohrotsstr = '-';
        }
        $details->add(get_string('cohorts', 'tool_muprog'), $cohrotsstr);

        return $this->output->render($details);
    }

    /**
     * Render program content.
     *
     * @param int $programid
     * @param int|null $movetargetsfor
     * @return string
     */
    public function render_content(int $programid, ?int $movetargetsfor): string {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);
        if ($program->archived) {
            $canedit = false;
        } else {
            $canedit = has_capability('tool/muprog:edit', $context);
        }

        $result = '';

        // Load the content tree with full problem detections.
        $top = program::load_content($program->id);

        if ($top->is_problem_detected()) {
            $result .= $this->output->notification(get_string('errorcontentproblem', 'tool_muprog'), \core\output\notification::NOTIFY_ERROR);
            if (has_capability('tool/muprog:admin', $context)) {
                $fixurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id, 'autofix' => 1, 'sesskey' => sesskey()]);
                $result .= '<div class="buttons mb-3">';
                $result .= $this->output->single_button($fixurl, get_string('programautofix', 'tool_muprog'));
                $result .= '</div>';
            }
        }

        if (!$canedit || !$movetargetsfor) {
            $movetargetsfor = null;
        }
        $movetargetsforname = null;
        if ($movetargetsfor) {
            $movetargetsforname = $DB->get_field('tool_muprog_item', 'fullname', ['id' => $movetargetsfor]);
            $movetargetsforname = format_string($movetargetsforname);
            $cancelmove = get_string('moveitemcancel', 'tool_muprog');
            $result .= $this->output->single_button($this->page->url, $cancelmove, 'get');
        }

        $rows = [];
        $hasactions = false;
        $output = $this->output;
        $renderercolumns = function (
            item $item,
            int $itemdepth,
            int $position,
            ?set $parent,
            bool $showtargets
        ) use (
            &$renderercolumns,
            &$rows,
            $canedit,
            &$hasactions,
            &$output,
            &$movetargetsfor,
            $movetargetsforname
): void {
            $fullname = $item->get_fullname();
            $id = $item->get_id();
            $padding = str_repeat('&nbsp;', $itemdepth * 6);
            $childpadding = str_repeat('&nbsp;', ($itemdepth + 1) * 6);

            if ($item instanceof set) {
                $completion = $item->get_sequencetype_info();
            } else if ($item instanceof training) {
                $completion = get_string('trainingcompletion', 'tool_muprog', $item->get_required_training());
            } else {
                $completion = '';
            }

            if ($completiondelay = $item->get_completiondelay()) {
                if ($completion !== '') {
                    $completion .= '<br />';
                }
                $completion .= '<small>' . get_string('completiondelay', 'tool_muprog') . ': ' . util::format_duration($completiondelay) . '</small>';
            }

            if ($movetargetsfor == $item->get_id()) {
                $showtargets = false;
            }
            if ($movetargetsfor && !$showtargets) {
                $fullname = '<span class="dimmed_text">' . $fullname . '</span>';
            }

            $actions = [];
            if ($canedit) {
                $importurl = null;
                if ($item instanceof set) {
                    $appendurl = new moodle_url('/admin/tool/muprog/management/item_append.php', ['parentitemid' => $id]);
                    $appendaction = new \tool_mulib\output\ajax_form\icon($appendurl, get_string('appenditem', 'tool_muprog'), 'appenditem', 'tool_muprog');
                    $actions[] = $output->render($appendaction);
                    if ($item instanceof top) {
                        $importurl = new moodle_url('/admin/tool/muprog/management/program_content_import.php', ['id' => $item->get_programid()]);
                        $importaction = new \tool_mulib\output\ajax_form\icon(
                            $importurl,
                            get_string('importprogramcontent', 'tool_muprog'),
                            'import',
                            'tool_muprog'
                        );
                        $actions[] = $output->render($importaction);
                    }
                } else {
                    $actions[] = $output->pix_icon('i/navigationitem', '') . ' ';
                }
                if ($item->is_deletable()) {
                    if ($item instanceof course) {
                        $deletestr = get_string('deletecourse', 'tool_muprog');
                    } else if ($item instanceof training) {
                        $deletestr = get_string('deletetraining', 'tool_muprog');
                    } else {
                        $deletestr = get_string('deleteset', 'tool_muprog');
                    }
                    $deleteurl = new moodle_url('/admin/tool/muprog/management/item_delete.php', ['id' => $id]);
                    $deleteaction = new \tool_mulib\output\ajax_form\icon($deleteurl, $deletestr, 'deleteitem', 'tool_muprog');
                    $actions[] = $output->render($deleteaction);
                } else {
                    if (!$importurl) {
                        $actions[] = $output->pix_icon('i/navigationitem', '') . ' ';
                    }
                }

                $targetpre = false;
                $targetpost = false;
                if ($item instanceof top) {
                    $actions[] = $output->pix_icon('i/navigationitem', '') . ' ';
                } else if ($movetargetsfor) {
                    $actions[] = $output->pix_icon('i/navigationitem', '') . ' ';
                    if ($showtargets) {
                        if ($position == 0 || $parent->get_children()[$position - 1]->get_id() != $movetargetsfor) {
                            $targetpre = true;
                        }
                        if ($position == count($parent->get_children()) - 1) {
                            $targetpost = true;
                        }
                    }
                } else {
                    $moveurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $item->get_programid(), 'movetargetsfor' => $item->get_id()]);
                    $moveicon = $output->pix_icon('move', get_string('moveitem', 'tool_muprog'), 'tool_muprog');
                    $actions[] = \html_writer::link($moveurl, $moveicon, ['title' => get_string('moveitem', 'tool_muprog')]);
                }

                if ($item instanceof set) {
                    $editurl = new moodle_url('/admin/tool/muprog/management/item_set_edit.php', ['id' => $id]);
                    $editaction = new \tool_mulib\output\ajax_form\icon($editurl, get_string('updateset', 'tool_muprog'), 'i/settings');
                    $actions[] = $output->render($editaction);
                } else if ($item instanceof course) {
                    $editurl = new moodle_url('/admin/tool/muprog/management/item_course_edit.php', ['id' => $id]);
                    $editaction = new \tool_mulib\output\ajax_form\icon($editurl, get_string('updatecourse', 'tool_muprog'), 'i/settings');
                    $actions[] = $output->render($editaction);
                    $actions[] = $output->pix_icon('i/navigationitem', '') . ' ';
                } else if ($item instanceof training) {
                    $editurl = new moodle_url('/admin/tool/muprog/management/item_training_edit.php', ['id' => $id]);
                    $editaction = new \tool_mulib\output\ajax_form\icon($editurl, get_string('updatetraining', 'tool_muprog'), 'i/settings');
                    $actions[] = $output->render($editaction);
                    $actions[] = $output->pix_icon('i/navigationitem', '') . ' ';
                } else {
                    $actions[] = $output->pix_icon('i/navigationitem', '') . ' ';
                }
            }

            if ($item instanceof course) {
                $courseid = $item->get_courseid();
                $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
                if ($coursecontext) {
                    if (has_capability('moodle/course:view', $coursecontext) && !$movetargetsfor) {
                        $detailurl = new moodle_url('/course/view.php', ['id' => $courseid]);
                        $fullname = \html_writer::link($detailurl, $fullname);
                    }
                } else {
                    $fullname .= ' <span class="badge bg-danger">' . get_string('errorcoursemissing', 'tool_muprog') . '</span>';
                }
            }

            if ($item instanceof top) {
                $itemname = $output->pix_icon('itemtop', get_string('program', 'tool_muprog'), 'tool_muprog') . '&nbsp;' . $fullname;
            } else if ($item instanceof course) {
                $itemname = $padding . $output->pix_icon('itemcourse', get_string('course'), 'tool_muprog') . $fullname;
            } else if ($item instanceof training) {
                $itemname = $padding . $output->pix_icon('itemtraining', get_string('training', 'tool_muprog'), 'tool_muprog') . $fullname;
            } else {
                $itemname = $padding . $output->pix_icon('itemset', get_string('set', 'tool_muprog'), 'tool_muprog') . $fullname;
            }
            if ($actions) {
                $hasactions = true;
            }

            if ($canedit && $targetpre) {
                $turl = new moodle_url(
                    '/admin/tool/muprog/management/program_content.php',
                    ['id' => $item->get_programid(), 'moveitem' => $movetargetsfor, 'movetoparent' => $parent->get_id(), 'moveposition' => $position, 'sesskey' => sesskey()]
                );
                $a = (object)['item' => $movetargetsforname, 'target' => $item->get_fullname()];
                $movehere = get_string('movebefore', 'tool_muprog', $a);
                $target = $padding . \html_writer::link($turl, $movehere, ['class' => 'movehere']);
                $rows[]  = [$target, '', ''];
            }

            if ($item instanceof top) {
                $points = '';
            } else {
                $points = $item->get_points();
            }

            $rows[] = [$itemname, $points, $completion, implode('', $actions)];

            $children = $item->get_children();
            if ($children) {
                $i = 0;
                foreach ($children as $child) {
                    $renderercolumns($child, $itemdepth + 1, $i, $item, $showtargets);
                    $i++;
                }
            } else if ($showtargets && ($item instanceof set)) {
                $turl = new moodle_url(
                    '/admin/tool/muprog/management/program_content.php',
                    ['id' => $item->get_programid(), 'moveitem' => $movetargetsfor, 'movetoparent' => $item->get_id(), 'moveposition' => 0, 'sesskey' => sesskey()]
                );
                $a = (object)['item' => $movetargetsforname, 'target' => $item->get_fullname()];
                $movehere = get_string('moveinto', 'tool_muprog', $a);
                $target = $childpadding . \html_writer::link($turl, $movehere, ['class' => 'movehere']);
                $rows[]  = [$target, '', ''];
            }

            if ($canedit && $targetpost) {
                $turl = new moodle_url(
                    '/admin/tool/muprog/management/program_content.php',
                    ['id' => $item->get_programid(), 'moveitem' => $movetargetsfor, 'movetoparent' => $parent->get_id(), 'moveposition' => $position + 1, 'sesskey' => sesskey()]
                );
                $a = (object)['item' => $movetargetsforname, 'target' => $item->get_fullname()];
                $movehere = get_string('moveafter', 'tool_muprog', $a);
                $target = $padding . \html_writer::link($turl, $movehere, ['class' => 'movehere']);
                $rows[]  = [$target, '', ''];
            }
        };
        $renderercolumns($top, 0, 0, null, isset($movetargetsfor));

        $table = new \html_table();
        $table->head = [
            get_string('item', 'tool_muprog'),
            get_string('itempoints', 'tool_muprog'),
            get_string('sequencetype', 'tool_muprog'),
            get_string('actions'),
        ];
        $table->id = 'program_content';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $rows;

        if (isset($movetargetsfor)) {
            $hasactions = false;
        }

        if (!$hasactions) {
            array_pop($table->head);
            foreach ($table->data as $k => $v) {
                array_pop($table->data[$k]);
            }
        }

        $result .= \html_writer::table($table);

        return $result;
    }

    /**
     * Render orphaned items.
     *
     * @param int $programid
     * @return string
     */
    public function render_content_orphans(int $programid): string {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);

        if ($program->archived) {
            return '';
        }
        if (!has_capability('tool/muprog:edit', $context)) {
            return '';
        }

        $top = program::load_content($program->id);

        $orphanedsets = $top->get_orphaned_sets();
        $orphanedcourses = $top->get_orphaned_courses();

        if (!$orphanedsets && !$orphanedcourses) {
            return '';
        }

        $rows = [];

        $iconcourse = $this->output->pix_icon('itemcourse', get_string('course'), 'tool_muprog');
        $iconset = $this->output->pix_icon('itemset', get_string('set', 'tool_muprog'), 'tool_muprog');

        foreach ($orphanedsets as $set) {
            $fullname = $iconset . $set->get_fullname();

            $actions = [];
            $deletestr = get_string('deleteset', 'tool_muprog');
            $deleteurl = new moodle_url('/admin/tool/muprog/management/item_delete.php', ['id' => $set->get_id()]);
            $deleteimg = $this->output->pix_icon('deleteitem', $deletestr, 'tool_muprog');
            $actions[] = \html_writer::link($deleteurl, $deleteimg, ['title' => $deletestr]);

            $rows[] = [$fullname, implode('', $actions)];
        }

        foreach ($orphanedcourses as $course) {
            $fullname = $iconcourse . $course->get_fullname();

            $actions = [];
            $deletestr = get_string('deletecourse', 'tool_muprog');
            $deleteurl = new moodle_url('/admin/tool/muprog/management/item_delete.php', ['id' => $course->get_id()]);
            $deleteimg = $this->output->pix_icon('deleteitem', $deletestr, 'tool_muprog');
            $actions[] = \html_writer::link($deleteurl, $deleteimg, ['title' => $deletestr]);

            $rows[] = [$fullname, implode('', $actions)];
        }

        $table = new \html_table();
        $table->head = [get_string('item', 'tool_muprog'), get_string('actions')];
        $table->id = 'program_content_orphaned_sets';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $rows;

        $result = '';
        $result .= $this->output->heading(get_string('unlinkeditems', 'tool_muprog'));
        $result .= \html_writer::table($table);

        return $result;
    }

    /**
     * Render allocation.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return string
     */
    public function render_user_allocation(stdClass $program, stdClass $source, stdClass $allocation): string {
        global $DB;

        $strnotset = get_string('notset', 'tool_muprog');
        $sourceclasses = allocation::get_source_classes();
        $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);
        /** @var \tool_muprog\local\source\base $sourceclass */
        $sourceclass = $sourceclasses[$source->type];

        $details = new \tool_mulib\output\entity_details();

        $details->add(
            get_string('programstatus', 'tool_muprog'),
            allocation::get_completion_status_html($program, $allocation)
        );
        $details->add(
            get_string('source', 'tool_muprog'),
            $sourceclass::render_allocation_source($program, $source, $allocation)
        );
        $details->add(
            get_string('allocationdate', 'tool_muprog'),
            userdate($allocation->timeallocated)
        );
        $details->add(
            get_string('programstart', 'tool_muprog'),
            userdate($allocation->timestart)
        );
        $details->add(
            get_string('programdue', 'tool_muprog'),
            (isset($allocation->timedue) ? userdate($allocation->timedue) : $strnotset)
        );
        $details->add(
            get_string('programend', 'tool_muprog'),
            (isset($allocation->timeend) ? userdate($allocation->timeend) : $strnotset)
        );
        $details->add(
            get_string('programcompletion', 'tool_muprog'),
            (isset($allocation->timecompleted) ? userdate($allocation->timecompleted) : $strnotset)
        );

        $handler = \tool_muprog\customfield\allocation_handler::create();
        foreach ($handler->get_instance_data($allocation->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }

        return $this->output->render($details);
    }

    /**
     * Render notification.
     *
     * @param stdClass $program
     * @param stdClass $allocation
     * @return string
     */
    public function render_user_notifications(stdClass $program, stdClass $allocation): string {
        $strnotset = get_string('notset', 'tool_muprog');

        $result = $this->output->heading(get_string('notificationdates', 'tool_muprog'), 3, ['h4']);

        $details = new \tool_mulib\output\entity_details();

        $types = notification_manager::get_all_types();
        // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingForeach
        /** @var class-string<\tool_muprog\local\notification\base> $classname */
        foreach ($types as $notificationtype => $classname) {
            if ($notificationtype === 'deallocation') {
                continue;
            }
            $timenotified = notification_manager::get_timenotified($allocation->userid, $program->id, $notificationtype);
            $details->add($classname::get_name(), $timenotified ? userdate($timenotified) : $strnotset);
        }

        $result .= $this->output->render($details);

        return $result;
    }

    /**
     * Render progress.
     *
     * @param stdClass $program
     * @param stdClass $allocation
     * @return string
     */
    public function render_user_progress(stdClass $program, stdClass $allocation): string {
        global $DB;

        $context = \context::instance_by_id($program->contextid);
        $canevidence = has_capability('tool/muprog:manageevidence', $context);
        $canadmin = has_capability('tool/muprog:admin', $context);
        $dateformat = get_string('strftimedatetimeshort');

        $top = program::load_content($program->id);

        $output = $this->output;
        $rows = [];
        $renderercolumns = function (
            item $item,
            $itemdepth
        ) use (
            &$renderercolumns,
            &$rows,
            $program,
            $allocation,
            &$output,
            &$DB,
            &$context,
            $dateformat,
            $canevidence,
            $canadmin
): void {

            $fullname = $item->get_fullname();
            $id = $item->get_id();
            $padding = str_repeat('&nbsp;', $itemdepth * 6);

            if ($item instanceof set) {
                $completiontype = $item->get_sequencetype_info();
            } else if ($item instanceof training) {
                $completiontype = $item->get_training_progress($allocation);
            } else {
                $completiontype = '';
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
                $itemname = $output->pix_icon('itemtop', get_string('program', 'tool_muprog'), 'tool_muprog') . '&nbsp;' . $fullname;
            } else if ($item instanceof course) {
                $itemname = $padding . $output->pix_icon('itemcourse', get_string('course'), 'tool_muprog') . $fullname;
            } else if ($item instanceof training) {
                $itemname = $padding . $output->pix_icon('itemtraining', get_string('training', 'tool_muprog'), 'tool_muprog') . $fullname;
            } else {
                $itemname = $padding . $output->pix_icon('itemset', get_string('set', 'tool_muprog'), 'tool_muprog') . $fullname;
            }

            if ($item instanceof top) {
                $points = '';
            } else {
                $points = $item->get_points();
            }

            // Completion stuff.
            $completioninfo = '';
            $completion = $DB->get_record('tool_muprog_completion', ['itemid' => $item->get_id(), 'allocationid' => $allocation->id]);
            if ($completion) {
                $completioninfo = userdate($completion->timecompleted, $dateformat);
            }
            if ($canadmin) {
                $editurl = new moodle_url('/admin/tool/muprog/management/item_completion_override.php', ['allocationid' => $allocation->id, 'itemid' => $item->get_id()]);
                $editaction = new \tool_mulib\output\ajax_form\icon($editurl, get_string('completionoverride', 'tool_muprog'), 'i/settings');
                $completioninfo .= ' ' . $output->render($editaction);
            }

            $evidenceinfo = '';
            $evidence = $DB->get_record('tool_muprog_evidence', ['itemid' => $item->get_id(), 'userid' => $allocation->userid]);
            if ($evidence) {
                $jsondata = (object)json_decode($evidence->evidencejson);
                $evidenceinfo .= format_text($jsondata->details, FORMAT_PLAIN, ['para' => false]);
            }
            if ($canevidence && !$program->archived && !$allocation->archived) {
                $editurl = new moodle_url('/admin/tool/muprog/management/item_evidence_edit.php', ['allocationid' => $allocation->id, 'itemid' => $item->get_id()]);
                if ($evidence) {
                    $editaction = new \tool_mulib\output\ajax_form\icon($editurl, get_string('evidenceupdate', 'tool_muprog'), 'i/edit');
                } else {
                    $editaction = new \tool_mulib\output\ajax_form\icon($editurl, get_string('evidenceupdate', 'tool_muprog'), 't/add');
                }
                $evidenceinfo .= ' ' . $output->render($editaction);
            }

            $rows[] = [$itemname, $points, $completiontype, $completioninfo, $evidenceinfo];

            foreach ($item->get_children() as $child) {
                $renderercolumns($child, $itemdepth + 1);
            }
        };
        $renderercolumns($top, 0);

        $table = new \html_table();
        $table->head = [
            get_string('item', 'tool_muprog'),
            get_string('itempoints', 'tool_muprog'),
            get_string('sequencetype', 'tool_muprog'),
            get_string('completiondate', 'tool_muprog'),
            get_string('evidence', 'tool_muprog'),
        ];
        $table->id = 'program_content';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $rows;

        $result = $this->output->heading(get_string('completion', 'completion'), 3, ['h4']);
        $result .= \html_writer::table($table);

        return $result;
    }

    /**
     * Render sources.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program_sources(\stdClass $program): string {
        global $DB;

        $sources = [];
        /** @var \tool_muprog\local\source\base[] $sourceclasses */
        $sourceclasses = \tool_muprog\local\allocation::get_source_classes();
        foreach ($sourceclasses as $sourcetype => $sourceclass) {
            $sourcerecord = $DB->get_record('tool_muprog_source', ['type' => $sourcetype, 'programid' => $program->id]);
            if (!$sourcerecord && !$sourceclass::is_new_allowed($program)) {
                continue;
            }
            if (!$sourcerecord) {
                $sourcerecord = null;
            }
            $sources[$sourcetype] = $sourceclass::render_status($program, $sourcerecord);
        }

        if (!$sources) {
            return get_string('notavailable');
        }

        $details = new \tool_mulib\output\entity_details();
        foreach ($sources as $sourcetype => $status) {
            $name = $sourceclasses[$sourcetype]::get_name();
            $details->add($name, $status);
        }

        return $this->output->render($details);
    }
}
