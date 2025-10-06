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

namespace tool_mucertify\output\catalogue;

use stdClass;

/**
 * Certification catalogue renderer.
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
        global $CFG, $DB;

        $context = \context::instance_by_id($certification->contextid);
        $fullname = format_string($certification->fullname);

        $description = file_rewrite_pluginfile_urls($certification->description, 'pluginfile.php', $context->id, 'tool_mucertify', 'description', $certification->id);
        $description = format_text($description, $certification->descriptionformat, ['context' => $context]);

        $tagsdiv = '';
        if ($CFG->usetags) {
            $tags = \core_tag_tag::get_item_tags('tool_mucertify', 'tool_mucertify_certification', $certification->id);
            if ($tags) {
                $tagsdiv = $this->output->tag_list($tags, '', 'certification-tags mb-3');
            }
        }

        $certificationimage = '';
        $presentation = (array)json_decode($certification->presentationjson);
        if (!empty($presentation['image'])) {
            $imageurl = \moodle_url::make_file_url(
                "$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/tool_mucertify/image/' . $certification->id . '/' . $presentation['image'],
                false
            );
            $certificationimage = '<div class="certificationimage">' . \html_writer::img($imageurl, '') . '</div>';
        }

        $result = $this->output->heading($fullname);
        $result .= $tagsdiv;
        $result .= "<div class='d-flex'><div class='w-100'>$description</div><div class='flex-shrink-1'>$certificationimage</div></div>";

        $details = new \tool_mulib\output\entity_details();
        $details->add(get_string('certificationstatus', 'tool_mucertify'), get_string('errornoassignment', 'tool_mucertify'));
        $handler = \tool_mucertify\customfield\certification_handler::create();
        foreach ($handler->get_instance_data($certification->id) as $data) {
            $value = $data->export_value();
            if ($value === null || $value === '') {
                continue;
            }
            $details->add($data->get_field()->get('name'), $value);
        }
        $result .= $this->output->render($details);

        $actions = [];
        /** @var \tool_mucertify\local\source\base[] $sourceclasses */ // Type hack.
        $sourceclasses = \tool_mucertify\local\assignment::get_source_classes();
        foreach ($sourceclasses as $type => $classname) {
            $source = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification->id, 'type' => $type]);
            if (!$source) {
                continue;
            }
            $actions = array_merge($actions, $classname::get_catalogue_actions($certification, $source));
        }

        if ($actions) {
            $result .= '<div class="buttons mb-5">';
            $result .= implode(' ', $actions);
            $result .= '</div>';
        }

        return $result;
    }
}
