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

namespace tool_mucertify\output\management;

use tool_mucertify\local\notification_manager;
use tool_mucertify\local\assignment;
use tool_mucertify\local\management;
use tool_mucertify\local\util;
use tool_mucertify\local\certification;
use tool_mucertify\local\period;
use stdClass, \core\url, html_writer;

/**
 * Certification management renderer.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render general certification info.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_general(stdClass $certification): string {
        global $CFG;

        $syscontext = \context_system::instance();
        $context = \context::instance_by_id($certification->contextid);

        $certificationimage = '';

        $imageurl = certification::get_image_url($certification, false);
        if ($imageurl) {
            $certificationimage = '<div class="certificationimage">' . html_writer::img($imageurl, '') . '</div>';
        }

        $details = new \tool_mulib\output\entity_details();
        $details->add(get_string('certificationname', 'tool_mucertify'), format_string($certification->fullname));
        $details->add(get_string('certificationidnumber', 'tool_mucertify'), s($certification->idnumber));

        $category = $context->get_context_name(false);
        if (has_capability('tool/mucertify:edit', $context)) {
            $url = new url('/admin/tool/mucertify/management/certification_move.php', ['id' => $certification->id]);
            $action = new \tool_mulib\output\ajax_form\icon($url, get_string('certification_move', 'tool_mucertify'), 'i/edit');
            $category .= $this->output->render($action);
        }
        $details->add(get_string('category'), $category);

        if ($CFG->usetags) {
            $tags = \core_tag_tag::get_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification->id);
            if ($tags) {
                $details->add(get_string('tags'), $this->output->tag_list($tags, '', 'certification-tags'));
            }
        }
        $description = file_rewrite_pluginfile_urls($certification->description, 'pluginfile.php', $syscontext->id, 'tool_mucertify', 'description', $certification->id);
        $description = format_text($description, $certification->descriptionformat, ['context' => $syscontext]);
        if (trim($description) === '') {
            $description = '&nbsp;';
        }
        $details->add(get_string('description'), $description);

        $archived = $certification->archived ? get_string('yes') : get_string('no');
        if (has_capability('tool/mucertify:edit', $context)) {
            if ($certification->archived) {
                $url = new \core\url('/admin/tool/mucertify/management/certification_restore.php', ['id' => $certification->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('certification_restore', 'tool_mucertify'), 'i/settings');
            } else {
                $url = new \core\url('/admin/tool/mucertify/management/certification_archive.php', ['id' => $certification->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('certification_archive', 'tool_mucertify'), 'i/settings');
            }
            $action->set_form_size('sm');
            $archived .= $this->output->render($action);
        }
        $details->add(get_string('archived', 'tool_mucertify'), $archived);

        $handler = \tool_mucertify\customfield\certification_handler::create();
        foreach ($handler->get_instance_data($certification->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }

        $result = $this->output->render($details);

        if (!$certificationimage) {
            return $result;
        }

        return "<div class='d-flex'><div class='w-100'>$result</div><div class='flex-shrink-1'>$certificationimage</div></div>";
    }

    /**
     * Render certification visibility.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_visibility(stdClass $certification): string {
        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('publicaccess', 'tool_mucertify'), ($certification->publicaccess ? get_string('yes') : get_string('no')));
        $cohorts = management::fetch_current_cohorts_menu($certification->id);
        if ($cohorts) {
            $cohortsstr = implode(', ', array_map('format_string', $cohorts));
        } else {
            $cohortsstr = '-';
        }
        $details->add(get_string('cohorts', 'tool_mucertify'), $cohortsstr);

        return $this->output->render($details);
    }

    /**
     * Render first period settings.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_settings1(stdClass $certification): string {
        global $DB;

        $settings = certification::get_periods_settings($certification);

        $details = new \tool_mulib\output\entity_details();

        if ($settings->programid1) {
            $program1 = $DB->get_record('tool_muprog_program', ['id' => $settings->programid1]);
            if ($program1) {
                $context = \context::instance_by_id($program1->contextid, IGNORE_MISSING);
                $program1str = format_string($program1->fullname);
                if ($context && has_capability('tool/muprog:view', $context)) {
                    $url = new \core\url('/admin/tool/muprog/management/program.php', ['id' => $program1->id]);
                    $program1str = html_writer::link($url, $program1str);
                }
                if (!$DB->record_exists('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify'])) {
                    $program1str = '<span class="badge bg-danger">' . $program1str . '</span>';
                }
            } else {
                $program1str = '<span class="badge bg-danger">' . get_string('error') . '</span>';
            }
        } else {
            $program1str = '<span class="badge bg-danger">' . get_string('notset', 'tool_mucertify') . '</span>';
        }
        $details->add(get_string('program', 'tool_muprog'), $program1str);

        $resettypes = certification::get_resettype_options();
        $details->add(get_string('resettype1', 'tool_mucertify'), $resettypes[$settings->resettype1]);

        if ($settings->due1) {
            $due = util::format_duration($settings->due1);
        } else {
            $due = get_string('notset', 'tool_mucertify');
        }
        $details->add(get_string('windowduedate', 'tool_mucertify'), $due);

        $since = certification::get_valid_options();
        $details->add(get_string('fromdate', 'tool_mucertify'), $since[$settings->valid1]);

        $since = certification::get_windowend_options();
        if ($settings->windowend1['since'] === certification::SINCE_NEVER) {
            $windowend = $since[$settings->windowend1['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->windowend1['delay']);
            $a->after = $since[$settings->windowend1['since']];
            $windowend = get_string('delayafter', 'tool_mucertify', $a);
        }
        $details->add(get_string('windowenddate', 'tool_mucertify'), $windowend);

        $since = certification::get_expiration_options();
        if ($settings->expiration1['since'] === certification::SINCE_NEVER) {
            $expiration = $since[$settings->expiration1['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->expiration1['delay']);
            $a->after = $since[$settings->expiration1['since']];
            $expiration = get_string('delayafter', 'tool_mucertify', $a);
        }
        $details->add(get_string('untildate', 'tool_mucertify'), $expiration);

        if ($settings->recertify === null) {
            $recertifystr = get_string('no');
        } else {
            $a = new stdClass();
            $a->delay = util::format_duration($settings->recertify);
            $a->before = get_string('untildate', 'tool_mucertify');
            $recertifystr = get_string('delaybefore', 'tool_mucertify', $a);
        }
        $details->add(get_string('recertify', 'tool_mucertify'), $recertifystr);

        return $this->output->render($details);
    }

    /**
     * Render recertification period settings.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_settings2(stdClass $certification): string {
        global $DB;

        $settings = certification::get_periods_settings($certification);

        $details = new \tool_mulib\output\entity_details();

        if ($settings->programid2) {
            $program2 = $DB->get_record('tool_muprog_program', ['id' => $settings->programid2]);
            if ($program2) {
                $context = \context::instance_by_id($program2->contextid, IGNORE_MISSING);
                $program2str = format_string($program2->fullname);
                if ($context && has_capability('tool/muprog:view', $context)) {
                    $url = new \core\url('/admin/tool/muprog/management/program.php', ['id' => $program2->id]);
                    $program2str = html_writer::link($url, $program2str);
                }
                if (!$DB->record_exists('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify'])) {
                    $program2str = '<span class="badge bg-danger">' . $program2str . '</span>';
                }
            } else {
                $program2str = '<span class="badge bg-danger">' . get_string('error') . '</span>';
            }
        } else {
            $program2str = '<span class="badge bg-danger">' . get_string('notset', 'tool_mucertify') . '</span>';
        }
        $details->add(get_string('program', 'tool_muprog'), $program2str);

        $resettypes = certification::get_resettype_options();
        $details->add(get_string('resettype2', 'tool_mucertify'), $resettypes[$settings->resettype2]);

        if ($settings->grace2) {
            $grace = util::format_duration($settings->grace2);
        } else {
            $grace = get_string('notset', 'tool_mucertify');
        }
        $details->add(get_string('graceperiod', 'tool_mucertify'), $grace);

        $since = certification::get_valid_options();
        $details->add(get_string('fromdate', 'tool_mucertify'), $since[$settings->valid2]);

        $since = certification::get_windowend_options();
        if ($settings->windowend2['since'] === certification::SINCE_NEVER) {
            $windowend = $since[$settings->windowend2['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->windowend2['delay']);
            $a->after = $since[$settings->windowend2['since']];
            $windowend = get_string('delayafter', 'tool_mucertify', $a);
        }
        $details->add(get_string('windowenddate', 'tool_mucertify'), $windowend);

        $since = certification::get_expiration_options();
        if ($settings->expiration2['since'] === certification::SINCE_NEVER) {
            $expiration = $since[$settings->expiration2['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->expiration2['delay']);
            $a->after = $since[$settings->expiration2['since']];
            $expiration = get_string('delayafter', 'tool_mucertify', $a);
        }
        $details->add(get_string('untildate', 'tool_mucertify'), $expiration);

        return $this->output->render($details);
    }

    /**
     * Render certificate settings.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_certificate(stdClass $certification): string {
        global $DB;

        $details = new \tool_mulib\output\entity_details();

        if ($certification->templateid) {
            $template = $DB->get_record('tool_certificate_templates', ['id' => $certification->templateid]);
            if (!$template) {
                $details->add(get_string('certificatetemplate', 'tool_certificate'), get_string('error'));
            } else {
                $name = format_string($template->name);
                $template = $DB->get_record('tool_certificate_templates', ['id' => $certification->templateid]);
                if ($template) {
                    $templatecontext = \context::instance_by_id($template->contextid);
                    if (has_capability('tool/certificate:viewallcertificates', $templatecontext)) {
                        $url = new \core\url('/admin/tool/certificate/certificates.php', ['templateid' => $template->id]);
                        $name = html_writer::link($url, $name);
                    }
                }
                $details->add(get_string('certificatetemplate', 'tool_certificate'), $name);
            }
        } else {
            $details->add(
                get_string('certificatetemplate', 'tool_certificate'),
                get_string('notset', 'tool_muprog')
            );
        }

        return $this->output->render($details);
    }

    /**
     * Render assignment.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @param bool $subpage
     * @return string
     */
    public function render_user_assignment(stdClass $certification, stdClass $assignment, bool $subpage = false): string {
        global $DB;

        $sourceclasses = assignment::get_source_classes();
        $sourcenames = assignment::get_source_names();
        $context = \context::instance_by_id($certification->contextid);
        $source = $DB->get_record('tool_mucertify_source', ['id' => $assignment->sourceid], '*', MUST_EXIST);
        /** @var \tool_mucertify\local\source\base $sourceclass */
        $sourceclass = $sourceclasses[$source->type];

        $buttons = [];
        $backheading = '';
        if ($subpage) {
            $backurl = new \core\url('/admin/tool/mucertify/management/assignment.php', ['id' => $assignment->id]);
            $backheading = $this->output->heading(get_string('periods', 'tool_mucertify'), 3, ['h4']);
            $backheading = \html_writer::link($backurl, $backheading);
        } else {
            if (has_capability('tool/mucertify:admin', $context)) {
                if ($sourceclass::is_assignment_update_possible($certification, $source, $assignment)) {
                    $url = new \core\url('/admin/tool/mucertify/management/assignment_update.php', ['id' => $assignment->id]);
                    $button = new \tool_mulib\output\ajax_form\button($url, get_string('assignment_update', 'tool_mucertify'));
                    $buttons[] = $this->output->render($button);
                }
            }
            if (has_capability('tool/mucertify:unassign', $context)) {
                if ($sourceclass::is_assignment_delete_possible($certification, $source, $assignment)) {
                    $url = new \core\url('/admin/tool/mucertify/management/assignment_delete.php', ['id' => $assignment->id]);
                    $button = new \tool_mulib\output\ajax_form\button($url, get_string('assignment_delete', 'tool_mucertify'));
                    $button->set_submitted_action($button::SUBMITTED_ACTION_REDIRECT);
                    $buttons[] = $this->output->render($button);
                }
            }
            if (!$certification->archived && !$assignment->archived && has_capability('tool/mucertify:admin', $context)) {
                $url = new \core\url('/admin/tool/mucertify/management/period_create.php', ['assignmentid' => $assignment->id]);
                $button = new \tool_mulib\output\ajax_form\button($url, get_string('period_create', 'tool_mucertify'));
                $buttons[] = $this->output->render($button);
            }
        }

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('certificationstatus', 'tool_mucertify'), assignment::get_status_html($certification, $assignment));

        if ($certification->recertify && !$certification->archived && !$assignment->archived) {
            $stoprecertify = !$DB->record_exists('tool_mucertify_period', [
                'certificationid' => $assignment->certificationid,
                'userid' => $assignment->userid,
                'recertifiable' => 1,
            ]);
            $details->add(get_string('stoprecertify', 'tool_mucertify'), ($stoprecertify ? get_string('yes') : get_string('no')));
        }

        if ($assignment->timecertifiedtemp) {
            $details->add(get_string('certifieduntiltemporary', 'tool_mucertify'), userdate($assignment->timecertifiedtemp));
        }
        $details->add(get_string('source', 'tool_mucertify'), $sourcenames[$source->type]);

        if ($certification->archived) {
            $archived = get_string('yes');
        } else {
            $archived = $assignment->archived ? get_string('yes') : get_string('no');
            if ($assignment->archived && has_capability('tool/mucertify:assign', $context)) {
                if ($sourceclass::is_assignment_restore_possible($certification, $source, $assignment)) {
                    $url = new \core\url('/admin/tool/mucertify/management/assignment_restore.php', ['id' => $assignment->id]);
                    $action = new \tool_mulib\output\ajax_form\icon($url, get_string('assignment_restore', 'tool_mucertify'), 'i/settings');
                    $action->set_form_size('sm');
                    $archived .= $this->output->render($action);
                }
            }
            if (!$assignment->archived && has_capability('tool/mucertify:unassign', $context)) {
                if ($sourceclass::is_assignment_archive_possible($certification, $source, $assignment)) {
                    $url = new \core\url('/admin/tool/mucertify/management/assignment_archive.php', ['id' => $assignment->id]);
                    $action = new \tool_mulib\output\ajax_form\icon($url, get_string('assignment_archive', 'tool_mucertify'), 'i/settings');
                    $action->set_form_size('sm');
                    $archived .= $this->output->render($action);
                }
            }
        }

        $details->add(get_string('archived', 'tool_mucertify'), $archived);

        $handler = \tool_mucertify\customfield\assignment_handler::create();
        foreach ($handler->get_instance_data($assignment->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }

        $result = $this->output->render($details);

        if ($buttons) {
            $result .= '<div class="buttons mb-5">';
            $result .= implode(' ', $buttons);
            $result .= '</div>';
        }

        $result .= $backheading;

        return $result;
    }

    /**
     * Render periods.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public function render_user_periods(stdClass $certification, stdClass $assignment): string {
        $result = $this->output->heading(get_string('periods', 'tool_mucertify'), 3, ['h4']);

        $context = \context::instance_by_id($certification->contextid);
        $report = \core_reportbuilder\system_report_factory::create(
            \tool_mucertify\reportbuilder\local\systemreports\assignment_periods::class,
            $context,
            parameters:['assignmentid' => $assignment->id]
        );
        $result .= $report->output();

        return $result;
    }

    /**
     * Render period.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @return string
     */
    public function render_user_period(stdClass $certification, ?stdClass $assignment, stdClass $period): string {
        global $DB;

        $context = \context::instance_by_id($certification->contextid);
        $strnotset = get_string('notset', 'tool_mucertify');

        $program = false;
        $programcontext = false;
        $allocation = false;
        if ($period->programid) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $period->programid]);
            $programcontext = \context::instance_by_id($program->contextid, IGNORE_MISSING);
        }
        if ($program) {
            $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $period->programid, 'userid' => $period->userid]);
        }

        $buttons = [];

        if (!$certification->archived && (!$assignment || !$assignment->archived) && has_capability('tool/mucertify:admin', $context)) {
            $updateurl = new \core\url('/admin/tool/mucertify/management/period_update.php', ['id' => $period->id]);
            $updatebutton = new \tool_mulib\output\ajax_form\button($updateurl, get_string('period_update', 'tool_mucertify'));
            $buttons[] = $this->output->render($updatebutton);

            if ($period->timerevoked) {
                $deleteurl = new \core\url('/admin/tool/mucertify/management/period_delete.php', ['id' => $period->id]);
                $deletebutton = new \tool_mulib\output\ajax_form\button($deleteurl, get_string('period_delete', 'tool_mucertify'));
                $deletebutton->set_submitted_action($deletebutton::SUBMITTED_ACTION_REDIRECT);
                $buttons[] = $this->output->render($deletebutton);
            }
        }

        $details = new \tool_mulib\output\entity_details();

        $programname = $strnotset;
        if ($program) {
            $programname = format_string($program->fullname);
            if ($programcontext && has_capability('tool/muprog:view', $programcontext)) {
                if ($allocation) {
                    $url = new \core\url('/admin/tool/muprog/management/allocation.php', ['id' => $allocation->id]);
                } else {
                    $url = new \core\url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
                }
                $programname = html_writer::link($url, $programname);
            }
        }
        $details->add(get_string('program', 'tool_muprog'), $programname);

        if ($allocation) {
            $programstatus = \tool_muprog\local\allocation::get_completion_status_html($program, $allocation);
        } else {
            $programstatus = get_string('notallocated', 'tool_mucertify');
        }
        $details->add(get_string('programstatus', 'tool_muprog'), $programstatus);

        if ($allocation) {
            $details->add(
                get_string('programprogress', 'tool_muprog'),
                \tool_muprog\local\allocation::get_progress_percentage($program, $allocation)
            );
        }

        $details->add(
            get_string('windowstartdate', 'tool_mucertify'),
            period::get_windowstart_html($certification, $assignment, $period)
        );

        $details->add(
            get_string('windowduedate', 'tool_mucertify'),
            period::get_windowdue_html($certification, $assignment, $period)
        );

        $details->add(
            get_string('windowenddate', 'tool_mucertify'),
            period::get_windowend_html($certification, $assignment, $period)
        );

        $details->add(
            get_string('fromdate', 'tool_mucertify'),
            period::get_from_html($certification, $assignment, $period)
        );

        $details->add(
            get_string('untildate', 'tool_mucertify'),
            period::get_until_html($certification, $assignment, $period)
        );

        $details->add(
            get_string('recertify', 'tool_mucertify'),
            period::get_recertify_html($certification, $assignment, $period)
        );

        $details->add(
            get_string('certifieddate', 'tool_mucertify'),
            ($period->timecertified ? userdate($period->timecertified) : $strnotset)
        );

        $details->add(
            get_string('revokeddate', 'tool_mucertify'),
            ($period->timerevoked ? userdate($period->timerevoked) : $strnotset)
        );

        if (!empty($certification->templateid) && \tool_mucertify\local\certificate::is_available()) {
            $template = $DB->get_record('tool_certificate_templates', ['id' => $certification->templateid]);
            if ($template) {
                $issuecode = '&nbsp;';
                if ($period->certificateissueid) {
                    $templatecontext = \context::instance_by_id($template->contextid);
                    $issue = $DB->get_record('tool_certificate_issues', ['id' => $period->certificateissueid]);
                    if ($issue) {
                        $issuecode = $issue->code;
                        if (has_capability('tool/certificate:viewallcertificates', $templatecontext)) {
                            $url = \tool_certificate\template::view_url($issue->code);
                            $issuecode = \html_writer::link($url, $issuecode, ['target' => '_blank']);
                        }
                    } else {
                        $issuecode = get_string('error');
                    }
                }
                $details->add(get_string('certificate', 'tool_certificate'), $issuecode);
            }
        }

        if ($period->evidencejson) {
            $jsondata = (object)json_decode($period->evidencejson);
            if (isset($jsondata->details)) {
                $details->add(
                    get_string('evidence_details', 'tool_mucertify'),
                    format_text($jsondata->details, FORMAT_PLAIN, ['para' => false])
                );
            }
        }

        $details->add(
            get_string('periodstatus', 'tool_mucertify'),
            period::get_status_html($certification, $assignment, $period)
        );

        $result = $this->output->render($details);

        if ($buttons) {
            $result .= '<div class="buttons mb-5">';
            $result .= implode(' ', $buttons);
            $result .= '</div>';
        }

        return $result;
    }

    /**
     * Render notifications.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public function render_user_notifications(stdClass $certification, stdClass $assignment): string {
        $strnotset = get_string('notset', 'tool_mucertify');

        $heading = $this->output->heading(get_string('notificationdates', 'tool_mucertify'), 3, ['h4']);

        $details = new \tool_mulib\output\entity_details();

        $types = notification_manager::get_all_types();
        // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingForeach
        /** @var class-string<\tool_mucertify\local\notification\base> $classname */
        foreach ($types as $notificationtype => $classname) {
            if ($notificationtype === 'unassignment') {
                continue;
            }
            $timenotified = notification_manager::get_timenotified($assignment->userid, $certification->id, $notificationtype);
            $details->add($classname::get_name(), $timenotified ? userdate($timenotified) : $strnotset);
        }

        return $heading . $this->output->render($details);
    }

    /**
     * Render sources.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_sources(stdClass $certification): string {
        global $DB;

        $sources = [];
        /** @var \tool_mucertify\local\source\base[] $sourceclasses */
        $sourceclasses = assignment::get_source_classes();
        foreach ($sourceclasses as $sourcetype => $sourceclass) {
            $sourcerecord = $DB->get_record('tool_mucertify_source', ['type' => $sourcetype, 'certificationid' => $certification->id]);
            if (!$sourcerecord && !$sourceclass::is_new_allowed($certification)) {
                continue;
            }
            if (!$sourcerecord) {
                $sourcerecord = null;
            }
            $sources[$sourcetype] = $sourceclass::render_status($certification, $sourcerecord);
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
