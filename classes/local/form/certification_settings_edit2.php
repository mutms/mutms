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

namespace tool_mucertify\local\form;

use tool_mucertify\local\util;
use tool_mucertify\local\certification;
use tool_mucertify\external\form_autocomplete\certification_periods_programid;

/**
 * Edit re-certification settings.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification_settings_edit2 extends \tool_mulib\local\ajax_form {
    /** @var array $arguments for WS call to get candidate programs */
    protected $arguments;

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $certification = $this->_customdata['certification'];
        $context = $this->_customdata['context'];
        $this->arguments = ['certificationid' => $certification->id];
        $settings = certification::get_periods_settings($certification);

        certification_periods_programid::add_element(
            $mform,
            $this->arguments,
            'programid2',
            get_string('program', 'tool_muprog'),
            $context
        );
        $mform->setDefault('programid2', $settings->programid2);
        $mform->addRule('programid2', get_string('required'), 'required', null, 'client');

        $resettypes = certification::get_resettype_options();
        $mform->addElement('select', 'resettype2', get_string('resettype2', 'tool_mucertify'), $resettypes);
        $mform->setDefault('resettype2', $settings->resettype2);

        $mform->addElement(
            'duration',
            'grace2',
            get_string('graceperiod', 'tool_mucertify'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );
        $mform->setDefault('grace2', $settings->grace2);

        $since = certification::get_valid_options();
        $mform->addElement('select', 'valid2', get_string('validfrom', 'tool_mucertify'), $since);
        $mform->setDefault('valid2', $settings->valid2);

        $since = certification::get_windowend_options();
        $timeunits = [
            'years' => get_string('years'),
            'months' => get_string('months'),
            'days' => get_string('days'),
            'hours' => get_string('hours'),
        ];
        $dvalue = $mform->createElement('text', 'number', '', ['size' => 3]);
        $dunit = $mform->createElement('select', 'timeunit', '', $timeunits);
        $dsince = $mform->createElement('select', 'since', '', $since);
        $mform->addGroup([$dvalue, $dunit, $dsince], 'windowend2', get_string('windowendafter', 'tool_mucertify'));
        $mform->setType('windowend2[number]', PARAM_INT);
        $mform->setDefault('windowend2', util::get_delay_form_value($settings->windowend2, 'days'));

        $since = certification::get_expiration_options();
        $timeunits = [
            'years' => get_string('years'),
            'months' => get_string('months'),
            'days' => get_string('days'),
            'hours' => get_string('hours'),
        ];
        $dvalue = $mform->createElement('text', 'number', '', ['size' => 3]);
        $dunit = $mform->createElement('select', 'timeunit', '', $timeunits);
        $dsince = $mform->createElement('select', 'since', '', $since);
        $mform->addGroup([$dvalue, $dunit, $dsince], 'expiration2', get_string('expirationafter', 'tool_mucertify'));
        $mform->setType('expiration2[number]', PARAM_INT);
        $mform->setDefault('expiration2', util::get_delay_form_value($settings->expiration2, 'months'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $certification->id);

        $this->add_action_buttons(true, get_string('updaterecertification', 'tool_mucertify'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        if ($data['windowend2']['since'] !== certification::SINCE_NEVER && $data['windowend2']['number'] <= 0) {
            $errors['windowend2'] = get_string('required');
        }

        if ($data['expiration2']['since'] !== certification::SINCE_NEVER && $data['expiration2']['number'] <= 0) {
            $errors['expiration2'] = get_string('required');
        }

        $error = certification_periods_programid::validate_value($data['programid2'], $this->arguments, $context);
        if ($error !== null) {
            $errors['programid2'] = $error;
        }

        return $errors;
    }
}
