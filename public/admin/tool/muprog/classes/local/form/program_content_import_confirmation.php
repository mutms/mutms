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

namespace tool_muprog\local\form;

/**
 * Add program content items confirmation.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_content_import_confirmation extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        global $DB, $PAGE;
        $mform = $this->_form;

        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        $targetprogram = $DB->get_record('tool_muprog_program', ['id' => $this->_customdata['id']], '*', MUST_EXIST);
        $fromprogram = $DB->get_record('tool_muprog_program', ['id' => $this->_customdata['fromprogram']], '*', MUST_EXIST);

        $fromcontext = \context::instance_by_id($fromprogram->contextid);

        $a = new \stdClass();
        $a->fullname = format_string($fromprogram->fullname);
        $a->idnumber = s($fromprogram->idnumber);
        $a->category = $fromcontext->get_context_name(false);
        $message = get_string('importprogramcontentconfirmation', 'tool_muprog', $a);
        $message = markdown_to_html($message);
        $message = $renderer->notification($message, \core\notification::INFO);
        $mform->addElement('html', $message);

        /** @var \tool_muprog\output\catalogue\renderer $catalogueoutput */
        $catalogueoutput = $PAGE->get_renderer('tool_muprog', 'catalogue', 'general');

        $content = $catalogueoutput->render_program_content($fromprogram);
        $mform->addElement('html', $content);

        $mform->addElement('hidden', 'fromprogram');
        $mform->setType('fromprogram', PARAM_INT);
        $mform->setDefault('fromprogram', $fromprogram->id);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $targetprogram->id);

        $this->add_action_buttons(true, get_string('importprogramcontent', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Check if the user has capability to copy the selected program.
        $programid = $data['fromprogram'];
        $programcontextid = $DB->get_field('tool_muprog_program', 'contextid', ['id' => $programid]);
        $context = \context::instance_by_id($programcontextid);
        if (!has_capability('tool/muprog:clone', $context)) {
            $errors['fromprogram'] = get_string('error');
        }
        return $errors;
    }
}
