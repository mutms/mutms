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

use tool_muprog\local\source\cohort;
use tool_muprog\external\form_autocomplete\source_cohort_edit_cohortids;

/**
 * Edit cohort allocation settings.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_cohort_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $source = $this->_customdata['source'];
        $program = $this->_customdata['program'];

        $mform->addElement('select', 'enable', get_string('active'), ['1' => get_string('yes'), '0' => get_string('no')]);
        $mform->setDefault('enable', $source->enable);
        if ($source->hasallocations) {
            $mform->hardFreeze('enable');
        }

        source_cohort_edit_cohortids::add_element(
            $mform,
            ['programid' => $program->id],
            'cohortids',
            get_string('source_cohort_cohortstoallocate', 'tool_muprog'),
            $context
        );
        if (!empty($source->id)) {
            $cohorts = cohort::fetch_allocation_cohorts_menu($source->id);
            $mform->setDefault('cohortids', array_keys($cohorts));
        }
        $mform->hideIf('cohortids', 'enable', 'eq', 0);

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);
        $mform->setDefault('programid', $program->id);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUMEXT);
        $mform->setDefault('type', $source->type);

        $this->add_action_buttons(true, get_string('update'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $program = $this->_customdata['program'];
        $context = $this->_customdata['context'];

        $args = ['programid' => $program->id];
        if ($data['enable']) {
            foreach ($data['cohortids'] as $cohortid) {
                $error = source_cohort_edit_cohortids::validate_value($cohortid, $args, $context);
                if ($error !== null) {
                    $errors['cohortids'] = $error;
                    break;
                }
            }
        }

        return $errors;
    }
}
