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

namespace tool_mutrain\output\management;

use stdClass, moodle_url;

/**
 * Frameworks management renderer.
 *
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render framework.
     *
     * @param stdClass $framework
     * @return string
     */
    public function render_framework(stdClass $framework): string {
        $context = \context::instance_by_id($framework->contextid);

        $description = '';
        if ($framework->description) {
            $description = format_text($framework->description, $framework->descriptionformat, ['context' => $context]);
            $description = $this->output->box($description);
        }

        $details = new \tool_mulib\output\entity_details();

        $details->add(get_string('framework_name', 'tool_mutrain'), format_string($framework->name));
        if ($framework->idnumber === null) {
            $idnumber = get_string('notset', 'tool_mulib');
        } else {
            $idnumber = s($framework->idnumber);
        }
        $details->add(get_string('framework_idnumber', 'tool_mutrain'), $idnumber);
        $details->add(get_string('category'), $context->get_context_name(false));
        $details->add(get_string('publicaccess', 'tool_mutrain'), ($framework->publicaccess ? get_string('yes') : get_string('no')));
        $details->add(get_string('requiredcredits', 'tool_mutrain'), format_float($framework->requiredcredits, 2, true, true));
        if ($framework->restrictcontext) {
            $restrictcontext = $context->get_context_name(false);
        } else {
            $restrictcontext = get_string('no');
        }
        $details->add(get_string('restrictcontext', 'tool_mutrain'), $restrictcontext);
        if ($framework->restrictafter) {
            $restrictafter = userdate($framework->restrictafter, get_string('strftimedatetimeshort'));
        } else {
            $restrictafter = get_string('notset', 'tool_mulib');
        }
        $details->add(get_string('restrictafter', 'tool_mutrain'), $restrictafter);
        $archived = $framework->archived ? get_string('yes') : get_string('no');
        if (has_capability('tool/mutrain:manageframeworks', $context)) {
            if ($framework->archived) {
                $url = new moodle_url('/admin/tool/mutrain/management/framework_restore.php', ['id' => $framework->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('framework_restore', 'tool_mutrain'), 'i/settings');
            } else {
                $url = new moodle_url('/admin/tool/mutrain/management/framework_archive.php', ['id' => $framework->id]);
                $action = new \tool_mulib\output\ajax_form\icon($url, get_string('framework_archive', 'tool_mutrain'), 'i/settings');
            }
            $action->set_form_size('sm');
            $archived .= $this->output->render($action);
        }
        $details->add(get_string('archived', 'tool_mutrain'), $archived);

        return $description . $this->output->render($details);
    }
}
