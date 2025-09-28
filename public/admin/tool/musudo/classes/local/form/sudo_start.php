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

namespace tool_musudo\local\form;

use tool_musudo\local\mfa;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/formslib.php");

/**
 * Start sudo session.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudo_start extends \moodleform {
    /** @var bool is mfa required */
    protected $mfrequired = false;
    /** @var bool mfa is required but user cannot use it */
    protected $nomfa = false;
    /** @var \tool_mfa\local\factor\object_factor_base[] */
    protected $factors = null;
    /** @var \tool_mfa\local\factor\object_factor_base */
    protected $factor;

    #[\Override]
    protected function definition() {
        global $SITE, $USER;
        $mform = $this->_form;
        $sudoer = $this->_customdata['sudoer'];

        $mform->disable_form_change_checker();
        $this->set_display_vertical();

        $mform->addElement('static', 'site', get_string('site'), format_string($SITE->fullname));
        $mform->addElement('static', 'user', get_string('user'), fullname($USER));

        $privileges = \tool_musudo\local\sudoer::get_privileges_description($sudoer);
        $mform->addElement('static', 'privilegesdesc', get_string('privileges', 'tool_musudo'), $privileges);

        if (mfa::is_mfa_enabled() && $sudoer->mfarequired) {
            $this->factors = mfa::get_user_factors();
            if ($this->factors) {
                $this->mfrequired = true;
                if (isset($this->factors[$this->_customdata['factor']])) {
                    $this->factor = $this->factors[$this->_customdata['factor']];
                } else {
                    $this->factor = reset($this->factors);
                }
                mfa::form_definition($this->_form, $this->factor, $this->factors);
            } else {
                $this->nomfa = true;
                $mform->addElement('static', 'mfarequired', get_string('mfarequired', 'tool_musudo'), get_string('yes'));
            }

            $mform->addElement('hidden', 'factor');
            $mform->setType('factor', PARAM_ALPHANUM);
            if ($this->mfrequired) {
                $mform->setDefault('factor', $this->factor->name);
            }
        }

        $this->add_action_buttons(true, get_string('continue'));

        if ($this->mfrequired) {
            mfa::form_definition_additional($this->_form, $this->factor, $this->factors);
        }
    }

    #[\Override]
    public function definition_after_data() {
        if ($this->mfrequired) {
            mfa::form_definition_after_data($this->_form, $this->factor);
        }
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($this->nomfa) {
            $errors['mfarequired'] = get_string('required');
        } else if ($this->mfrequired) {
            $errors = array_merge($errors, mfa::form_validation($this->_form, $this->factor, $data, $files));
        }
        return $errors;
    }
}
