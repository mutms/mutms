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
 * Edit initial certification settings.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class certification_settings_edit1 extends \tool_mulib\local\ajax_form {
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
            'programid1',
            get_string('program', 'tool_muprog'),
            $context
        );
        $mform->setDefault('programid1', $certification->programid1);
        $mform->addRule('programid1', get_string('required'), 'required', null, 'client');

        $resettypes = certification::get_resettype_options();
        $mform->addElement('select', 'resettype1', get_string('resettype1', 'tool_mucertify'), $resettypes);
        $mform->setDefault('resettype1', $settings->resettype1);

        $mform->addElement(
            'duration',
            'due1',
            get_string('windowdueafter', 'tool_mucertify'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );
        $mform->setDefault('due1', $settings->due1);

        $since = certification::get_valid_options();
        $mform->addElement('select', 'valid1', get_string('validfrom', 'tool_mucertify'), $since);
        $mform->setDefault('valid1', $settings->valid1);

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
        $mform->addGroup([$dvalue, $dunit, $dsince], 'windowend1', get_string('windowendafter', 'tool_mucertify'));
        $mform->setType('windowend1[number]', PARAM_INT);
        $mform->setDefault('windowend1', util::get_delay_form_value($settings->windowend1, 'days'));

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
        $mform->addGroup([$dvalue, $dunit, $dsince], 'expiration1', get_string('expirationafter', 'tool_mucertify'));
        $mform->setType('expiration1[number]', PARAM_INT);
        $mform->setDefault('expiration1', util::get_delay_form_value($settings->expiration1, 'months'));

        $mform->addElement(
            'duration',
            'recertify',
            get_string('recertifybefore', 'tool_mucertify'),
            ['optional' => true, 'defaultunit' => DAYSECS]
        );
        $mform->setDefault('recertify', $settings->recertify);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $certification->id);

        $this->add_action_buttons(true, get_string('certification_update', 'tool_mucertify'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $context = $this->_customdata['context'];

        if ($data['windowend1']['since'] !== certification::SINCE_NEVER && $data['windowend1']['number'] <= 0) {
            $errors['windowend1'] = get_string('required');
        }

        if ($data['expiration1']['since'] !== certification::SINCE_NEVER && $data['expiration1']['number'] <= 0) {
            $errors['expiration1'] = get_string('required');
        }

        $error = certification_periods_programid::validate_value($data['programid1'], $this->arguments, $context);
        if ($error !== null) {
            $errors['programid1'] = $error;
        }

        return $errors;
    }
}
