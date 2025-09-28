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

namespace tool_muprog\local\form;

use tool_muprog\local\content\set;
use tool_muprog\external\form_autocomplete\item_append_trainingid;
use tool_muprog\local\util;

/**
 * Add program content item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_append extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        /** @var set $parentset */
        $parentset = $this->_customdata['parentset'];
        $context = $this->_customdata['context'];

        $select = 'programid = :programid AND courseid IS NOT NULL';
        $params = ['programid' => $parentset->get_programid()];
        $exclude = $DB->get_fieldset_select('tool_muprog_item', 'courseid', $select, $params);

        $mform->addElement(
            'course',
            'courses',
            get_string('courses'),
            ['multiple' => true, 'exclude' => $exclude, 'requiredcapabilities' => ['tool/muprog:addcourse']]
        );

        if (util::is_mutrain_available() && $DB->record_exists('tool_mutrain_framework', ['archived' => 0])) {
            $args = ['programid' => $parentset->get_programid()];
            item_append_trainingid::add_element(
                $mform,
                $args,
                'trainingid',
                get_string('training', 'tool_muprog'),
                $context
            );
        }

        $mform->addElement('select', 'addset', get_string('addset', 'tool_muprog'), ['0' => get_string('no'), '1' => get_string('yes')]);

        $mform->addElement('text', 'fullname', get_string('fullname'), 'maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->hideIf('fullname', 'addset', 'eq', 0);

        $mform->addElement('text', 'points', get_string('itempoints', 'tool_muprog'));
        $mform->setType('points', PARAM_INT);
        $mform->setDefault('points', '1');

        $stypes = set::get_sequencetype_types();
        $mform->addElement('select', 'sequencetype', get_string('sequencetype', 'tool_muprog'), $stypes);
        $mform->hideIf('sequencetype', 'addset', 'eq', 0);

        $mform->addElement('text', 'minprerequisites', $stypes[set::SEQUENCE_TYPE_ATLEAST]);
        $mform->setType('minprerequisites', PARAM_INT);
        $mform->setDefault('minprerequisites', 1);
        $mform->hideIf('minprerequisites', 'addset', 'eq', 0);
        $mform->hideIf('minprerequisites', 'sequencetype', 'noteq', set::SEQUENCE_TYPE_ATLEAST);

        $mform->addElement('text', 'minpoints', $stypes[set::SEQUENCE_TYPE_MINPOINTS]);
        $mform->setType('minpoints', PARAM_INT);
        $mform->hideIf('minpoints', 'sequencetype', 'noteq', set::SEQUENCE_TYPE_MINPOINTS);
        $mform->setDefault('minpoints', 1);

        $mform->addElement(
            'duration',
            'completiondelay',
            get_string('completiondelay', 'tool_muprog'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );

        $mform->addElement('hidden', 'parentitemid');
        $mform->setType('parentitemid', PARAM_INT);
        $mform->setDefault('parentitemid', $parentset->get_id());

        $this->add_action_buttons(true, get_string('appenditem', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $context = $this->_customdata['context'];
        /** @var set $parentset */
        $parentset = $this->_customdata['parentset'];

        if ($data['points'] < 0) {
            $errors['points'] = get_string('error');
        }

        if ($data['addset']) {
            if (trim($data['fullname']) === '') {
                $errors['fullname'] = get_string('required');
            }
            if ($data['sequencetype'] === set::SEQUENCE_TYPE_ATLEAST) {
                if ($data['minprerequisites'] <= 0) {
                    $errors['minprerequisites'] = get_string('required');
                }
            } else if ($data['sequencetype'] === set::SEQUENCE_TYPE_MINPOINTS) {
                if ($data['minpoints'] <= 0) {
                    $errors['minpoints'] = get_string('required');
                }
            }
        } else {
            if (!$data['courses'] && empty($data['trainingid'])) {
                $errors['courses'] = get_string('required');
            } else {
                if (\tool_muprog\local\util::is_mutenancy_active()) {
                    if ($context->tenantid) {
                        foreach ($data['courses'] as $courseid) {
                            // The caps are removed in other tenants, but we need to make sure
                            // admins do not add other tenant courses accidentally.
                            $coursecontext = \context_course::instance($courseid);
                            if ($coursecontext->tenantid && $coursecontext->tenantid != $context->tenantid) {
                                $errors['courses'] = get_string('errordifferenttenant', 'tool_muprog');
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($data['trainingid'])) {
            $args = ['programid' => $parentset->get_programid()];
            $error = item_append_trainingid::validate_value($data['trainingid'], $args, $context);
            if ($error !== null) {
                $errors['trainingid'] = $error;
            }
        }

        return $errors;
    }
}
