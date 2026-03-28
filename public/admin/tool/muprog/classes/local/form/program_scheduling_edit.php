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

use tool_muprog\local\program;

/**
 * Edit program scheduling settings.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_scheduling_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $this->parse_program_allocation_date($data, 'start');
        $this->add_program_date('start');

        $this->parse_program_allocation_date($data, 'due');
        $this->add_program_date('due');

        $this->parse_program_allocation_date($data, 'end');
        $this->add_program_date('end');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $data->id);

        $this->add_action_buttons(true, get_string('updatescheduling', 'tool_muprog'));

        $this->set_data($data);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $this->validate_program_date('start', $data, $errors);
        $this->validate_program_date('due', $data, $errors);
        $this->validate_program_date('end', $data, $errors);

        return $errors;
    }

    /**
     * Add program date.
     *
     * @param string $name
     * @return void
     */
    protected function add_program_date(string $name): void {
        $mform = $this->_form;

        $delaytypes = [
            'months' => get_string('months'),
            'days' => get_string('days'),
            'hours' => get_string('hours'),
        ];

        $datetypes = program::{'get_program_' . $name . 'date_types'}();

        $mform->addElement('select', 'program' . $name . '_type', get_string('program' . $name, 'tool_muprog'), $datetypes);
        $mform->addHelpButton('program' . $name . '_type', 'program' . $name, 'tool_muprog');
        $mform->addElement('date_time_selector', 'program' . $name . '_date', get_string('program' . $name . '_date', 'tool_muprog'), ['optional' => false]);
        $mform->hideIf('program' . $name . '_date', 'program' . $name . '_type', 'notequal', 'date');
        $dvalue = $mform->createElement('text', 'value', '');
        $dtype = $mform->createElement('select', 'type', '', $delaytypes);
        $mform->addGroup([$dvalue, $dtype], 'program' . $name . '_delay', get_string('program' . $name . '_delay', 'tool_muprog'));
        $mform->setType('program' . $name . '_delay[value]', PARAM_INT);
        $mform->hideIf('program' . $name . '_delay', 'program' . $name . '_type', 'notequal', 'delay');
    }

    /**
     * Validate date.
     *
     * @param string $name
     * @param array $data
     * @param array $errors
     * @return void
     */
    protected function validate_program_date(string $name, array $data, array &$errors): void {
        if ($data['program' . $name . '_type'] === 'delay') {
            if ($data['program' . $name . '_delay']['value'] <= 0) {
                $errors['program' . $name . '_delay'] = get_string('required');
            }
        }
        if ($name !== 'start') {
            if ($data['program' . $name . '_type'] === 'date') {
                if ($data['programstart_type'] === 'date') {
                    if ($data['programstart_date'] >= $data['program' . $name . '_date']) {
                        $errors['program' . $name . '_date'] = get_string('error');
                    }
                }
            }
            if ($name === 'end') {
                if ($data['programdue_type'] === 'date' && $data['programend_type'] === 'date') {
                    if ($data['programdue_date'] > $data['programend_date']) {
                        $errors['programend_date'] = get_string('error');
                    }
                }
            }
        }
    }

    /**
     * Parse date.
     *
     * @param \stdClass $program
     * @param string $name
     * @return void
     */
    protected function parse_program_allocation_date(\stdClass $program, string $name): void {
        if (!$program->{$name . 'datejson'}) {
            return;
        }

        $start = (array)json_decode($program->{$name . 'datejson'});
        foreach ($start as $k => $v) {
            $program->{'program' . $name . '_' . $k} = $v;
        }

        if (isset($program->{'program' . $name . '_delay'})) {
            $di = new \DateInterval($program->{'program' . $name . '_delay'});
            $program->{'program' . $name . '_delay'} = [];
            if ($di->m) {
                $program->{'program' . $name . '_delay'}['type'] = 'months';
                $program->{'program' . $name . '_delay'}['value'] = $di->m;
            } else if ($di->d) {
                $program->{'program' . $name . '_delay'}['type'] = 'days';
                $program->{'program' . $name . '_delay'}['value'] = $di->d;
            } else if ($di->h) {
                $program->{'program' . $name . '_delay'}['type'] = 'hours';
                $program->{'program' . $name . '_delay'}['value'] = $di->h;
            }
        }
    }
}
