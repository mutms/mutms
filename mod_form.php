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

use mod_mubook\local\markdown_formatter;

defined('MOODLE_INTERNAL') || die;

/** @var stdClass $CFG */
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Interactive book plugin activity form.
 *
 * @package    mod_mubook
 * @copyright  2004 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mubook_mod_form extends moodleform_mod {
    #[\Override]
    public function definition() {
        $mform = $this->_form;

        $config = get_config('mubook');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements(get_string('moduleintro'));

        $options = \mod_mubook\local\toc::get_numbering_menu();
        $mform->addElement('select', 'numbering', get_string('numbering', 'mod_mubook'), $options);
        $mform->setDefault('numbering', $config->numberingdefault ?? 1);

        $cman = \core\di::get(\mod_mubook\local\content_manager::class);
        $options = $cman->get_types_menu(true);
        $mform->addElement('select', 'contentdefault', get_string('contentdefault', 'mod_mubook'), $options);
        $contentdefault = get_config('mubook', 'contentdefault');
        if (isset($options[$contentdefault])) {
            $mform->setDefault('contentdefault', $contentdefault);
        } else {
            $mform->setDefault('contentdefault', 'html');
        }

        $menu = markdown_formatter::get_html_options();
        $mform->addElement('select', 'markdownhtml', get_string('markdown_html', 'mod_mubook'), $menu);
        $mform->setDefault('markdownhtml', $config->markdownhtml ?? markdown_formatter::HTML_ALLOW);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
