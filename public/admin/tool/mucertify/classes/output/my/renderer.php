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

namespace tool_mucertify\output\my;

use tool_mucertify\local\assignment;
use stdClass;

/**
 * My certification renderer.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render certification.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification(\stdClass $certification): string {
        global $CFG;

        $context = \context::instance_by_id($certification->contextid);
        $fullname = format_string($certification->fullname);

        $description = file_rewrite_pluginfile_urls($certification->description, 'pluginfile.php', $context->id, 'tool_mucertify', 'description', $certification->id);
        $description = format_text($description, $certification->descriptionformat, ['context' => $context]);

        $tagsdiv = '';
        if ($CFG->usetags) {
            $tags = \core_tag_tag::get_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification->id);
            if ($tags) {
                $tagsdiv = $this->output->tag_list($tags, '', 'certification-tags');
            }
        }

        $certificationimage = '';
        $presentation = (array)json_decode($certification->presentationjson);
        if (!empty($presentation['image'])) {
            $imageurl = \core\url::make_file_url(
                "$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/tool_mucertify/image/' . $certification->id . '/' . $presentation['image'],
                false
            );
            $certificationimage = '<div class="certificationimage">' . \html_writer::img($imageurl, '') . '</div>';
        }

        $result = $this->output->heading($fullname);
        $result .= $tagsdiv;
        $result .= "<div class='d-flex'><div class='w-100'>$description</div><div class='flex-shrink-1'>$certificationimage</div></div>";

        return $result;
    }

    /**
     * Render assignment.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public function render_user_assignment(stdClass $certification, stdClass $assignment): string {
        global $DB;

        $details = new \tool_mulib\output\entity_details();

        $handler = \tool_mucertify\customfield\certification_handler::create();
        foreach ($handler->get_instance_data($certification->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }

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

        $handler = \tool_mucertify\customfield\assignment_handler::create();
        foreach ($handler->get_instance_data($assignment->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }

        return $this->output->render($details);
    }

    /**
     * Render periods.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public function render_user_periods(stdClass $certification, stdClass $assignment): string {
        $result = $this->output->heading(get_string('periods', 'tool_mucertify'), 3);

        $context = \context_user::instance($assignment->userid);
        $report = \core_reportbuilder\system_report_factory::create(
            \tool_mucertify\reportbuilder\local\systemreports\assignment_periods_user::class,
            $context,
            parameters:['assignmentid' => $assignment->id]
        );
        $result .= $report->output();

        return $result;
    }

    /**
     * Returns body of My certifications block.
     *
     * @return string
     */
    public function render_block_content(): string {
        global $DB, $USER;

        $sql = "SELECT ca.*
                  FROM {tool_mucertify_certification} c
                  JOIN {tool_mucertify_assignment} ca ON ca.certificationid = c.id
                 WHERE c.archived = 0 AND ca.archived = 0
                       AND ca.userid = :userid
              ORDER BY c.fullname ASC";
        $params = ['userid' => $USER->id];
        $assignments = $DB->get_records_sql($sql, $params);

        if (!$assignments) {
            return '<em>' . get_string('errornomycertifications', 'tool_mucertify') . '</em>';
        }

        $certificationicon = $this->output->pix_icon('certification', '', 'tool_mucertify');

        foreach ($assignments as $assignment) {
            $row = [];

            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid]);
            $fullname = $certificationicon . format_string($certification->fullname);
            $detailurl = new \core\url('/admin/tool/mucertify/my/certification.php', ['id' => $certification->id]);
            $fullname = \html_writer::link($detailurl, $fullname);
            $row[] = $fullname;

            $row[] = assignment::get_status_html($certification, $assignment);

            $row[] = assignment::get_until_html($certification, $assignment);

            $data[] = $row;
        }

        $table = new \html_table();
        $table->head = [
            get_string('certificationname', 'tool_mucertify'),
            get_string('certificationstatus', 'tool_mucertify'),
            get_string('untildate', 'tool_mucertify'),
        ];
        $table->attributes['class'] = 'table table-striped table-hover';
        $table->data = $data;
        return \html_writer::table($table);
    }

    /**
     * Returns footer of My certifications block.
     *
     * @return string
     */
    public function render_block_footer(): string {
        $url = \tool_mucertify\local\catalogue::get_catalogue_url();
        if ($url) {
            return '<div class="float-end">' . \html_writer::link($url, get_string('catalogue', 'tool_mucertify')) . '</div>';
        }
        return '';
    }
}
