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

namespace mod_mubook\local\content\form;

use stdClass;
use mod_mubook\local\toc;
use mod_mubook\local\chapter;
use mod_mubook\local\content;

/**
 * Update disclosure button.
 *
 * @package    mod_mubook
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class disclosure_update extends \mod_mubook\local\form\content_update_base {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var content $content */
        $content = $this->_customdata['content'];
        /** @var chapter $chapter */
        $chapter = $this->_customdata['chapter'];
        /** @var toc $toc */
        $toc = $this->_customdata['toc'];
        $mubook = $toc->get_mubook();
        $context = $toc->get_context();

        $nextinfo = null;
        foreach ($chapter->get_contents() as $c) {
            if ($c->sortorder == $content->sortorder + 1) {
                $nextinfo = $c->get_identification();
                break;
            }
        }
        if (!$nextinfo) {
            $nextinfo = get_string('content_type_disclosure_target_none', 'mod_mubook');
        }
        $mform->addElement(
            'static',
            'target',
            get_string('content_type_disclosure_target', 'mod_mubook'),
            $nextinfo
        );

        $options = (object)json_decode($content->data1 ?? '[]');

        $mform->addElement('text', 'labelshow', get_string('content_type_disclosure_show_custom', 'mod_mubook'), ['size' => 40]);
        $mform->setType('labelshow', PARAM_TEXT);
        $mform->setDefault('labelshow', $options->labelshow ?? '');

        $mform->addElement('text', 'labelhide', get_string('content_type_disclosure_hide_custom', 'mod_mubook'), ['size' => 40]);
        $mform->setType('labelhide', PARAM_TEXT);
        $mform->setDefault('labelhide', $options->labelhide ?? '');

        $mform->addElement('text', 'labelprinted', get_string('content_type_disclosure_printed_custom', 'mod_mubook'), ['size' => 40]);
        $mform->setType('labelprinted', PARAM_TEXT);
        $mform->setDefault('labelprinted', $options->labelprinted ?? '');

        $this->add_shared_content_elements();

        $this->add_action_buttons(true, get_string('content_update', 'mod_mubook'));
    }

    #[\Override]
    public static function before_db_update(stdClass $record, stdClass $data, chapter $chapter, stdClass $mubook, \context_module $context): void {
        $options = (array)json_decode($record->data1 ?? '[]');

        if (property_exists($data, 'labelshow')) {
            $options['labelshow'] = $data->labelshow;
        }

        if (property_exists($data, 'labelhide')) {
            $options['labelhide'] = $data->labelhide;
        }

        if (property_exists($data, 'labelprinted')) {
            $options['labelprinted'] = $data->labelprinted;
        }

        $record->data1 = json_encode($options, JSON_UNESCAPED_UNICODE);
    }
}
