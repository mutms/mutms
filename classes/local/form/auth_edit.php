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

namespace tool_mutenancy\local\form;

use tool_mutenancy\local\config;

/**
 * Tenant auth edit form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class auth_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $tenant = $this->_customdata['tenant'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $tenant->id);

        $syscontext = \context_system::instance();

        if (has_capability('moodle/site:config', $syscontext)) {
            // Adding random new accounts cannot be allowed by tenant managers!
            $default = get_config('core', 'registerauth');
            if ($default) {
                $defaultstr = get_string('pluginname', 'auth_' . $default);
            } else {
                $defaultstr = get_string('disabled', 'core_admin');
            }
            $options = [];
            $options[''] = get_string('disabled', 'core_admin');
            if (is_enabled_auth('email')) {
                $options['email'] = get_string('pluginname', 'auth_email');
            }
            $group = [];
            $group[] = $mform->createElement('advcheckbox', 'registerauth_override', get_string('config_override_value', 'tool_mutenancy', $defaultstr));
            $group[] = $mform->createElement('select', 'registerauth', get_string('selfregistration', 'core_auth'), $options);
            $mform->addGroup(
                $group,
                'registerauth_group',
                '<div>' . get_string('selfregistration', 'core_auth') . '<div class="small text-muted">registerauth</div></div>',
                '<div style="width: 100%"/>',
                false
            );
            if (config::is_overridden($tenant->id, 'core', 'registerauth')) {
                $mform->setDefault('registerauth_override', '1');
                $mform->setDefault('registerauth', config::get($tenant->id, 'core', 'registerauth'));
            } else {
                $mform->setDefault('registerauth_override', '0');
                if (isset($options[$default])) {
                    $mform->setDefault('registerauth', $default);
                }
            }
            $mform->hideIf('registerauth', 'registerauth_override', 'eq', '0');
            $mform->addElement('static', 'registerauth_desc', '', markdown_to_html(get_string('selfregistration_help', 'auth')));
        } else {
            if (config::is_overridden($tenant->id, 'core', 'registerauth')) {
                $auth = config::get($tenant->id, 'core', 'registerauth');
            } else {
                $default = get_config('core', 'registerauth');
                if ($default) {
                    $defaultstr = get_string('pluginname', 'auth_' . $default);
                } else {
                    $defaultstr = get_string('disabled', 'core_admin');
                }
                $auth = get_string('config_default_value', 'tool_mutenancy', $defaultstr);
            }
            $mform->addElement('static', 'registerauth_static', get_string('selfregistration', 'core_auth'), $auth);
        }

        $default = get_config('core', 'showloginform');
        if ($default) {
            $defaultstr = get_string('yes');
        } else {
            $defaultstr = get_string('no');
        }
        $options = [];
        $options['1'] = get_string('yes');
        $options['0'] = get_string('no');
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'showloginform_override', get_string('config_override_value', 'tool_mutenancy', $defaultstr));
        $group[] = $mform->createElement('select', 'showloginform', get_string('showloginform', 'core_auth'), $options);
        $mform->addGroup(
            $group,
            'showloginform_group',
            '<div>' . get_string('showloginform', 'core_auth') . '<div class="small text-muted">showloginform</div></div>',
            '<div style="width: 100%"/>',
            false
        );
        if (config::is_overridden($tenant->id, 'core', 'showloginform')) {
            $mform->setDefault('showloginform_override', '1');
            $mform->setDefault('showloginform', config::get($tenant->id, 'core', 'showloginform'));
        } else {
            $mform->setDefault('showloginform', $default);
            $mform->setDefault('showloginform_override', '0');
        }
        $mform->hideIf('showloginform', 'showloginform_override', 'eq', '0');
        $mform->addElement('static', 'showloginform_desc', '', markdown_to_html(get_string('showloginform_desc', 'auth')));

        $default = get_config('core', 'allowemailaddresses');
        if ($default === '') {
            $defaultstr = get_string('emptysettingvalue', 'core_admin');
        } else {
            $defaultstr = s($default);
        }
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'allowemailaddresses_override', get_string('config_override_value', 'tool_mutenancy', $defaultstr));
        $group[] = $mform->createElement('text', 'allowemailaddresses', get_string('allowemailaddresses', 'core_admin'), ['size' => 60], PARAM_NOTAGS);
        $mform->addGroup(
            $group,
            'allowemailaddresses_group',
            '<div>' . get_string('allowemailaddresses', 'core_admin') . '<div class="small text-muted">allowemailaddresses</div></div>',
            '<div style="width: 100%"/>',
            false
        );
        $mform->setType('allowemailaddresses', PARAM_NOTAGS);
        if (config::is_overridden($tenant->id, 'core', 'allowemailaddresses')) {
            $mform->setDefault('allowemailaddresses_override', '1');
            $mform->setDefault('allowemailaddresses', config::get($tenant->id, 'core', 'allowemailaddresses'));
        } else {
            $mform->setDefault('allowemailaddresses', $default);
            $mform->setDefault('allowemailaddresses_override', '0');
        }
        $mform->hideIf('allowemailaddresses', 'allowemailaddresses_override', 'eq', '0');
        $mform->addElement('static', 'allowemailaddresses_desc', '', markdown_to_html(get_string('configallowemailaddresses', 'core_admin')));

        $default = get_config('core', 'denyemailaddresses');
        if ($default === '') {
            $defaultstr = get_string('emptysettingvalue', 'core_admin');
        } else {
            $defaultstr = s($default);
        }
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'denyemailaddresses_override', get_string('config_override_value', 'tool_mutenancy', $defaultstr));
        $group[] = $mform->createElement('text', 'denyemailaddresses', get_string('denyemailaddresses', 'core_admin'), ['size' => 60]);
        $mform->addGroup(
            $group,
            'denyemailaddresses_group',
            '<div>' . get_string('denyemailaddresses', 'core_admin') . '<div class="small text-muted">denyemailaddresses</div></div>',
            '<div style="width: 100%"/>',
            false
        );
        $mform->setType('denyemailaddresses', PARAM_NOTAGS);
        if (config::is_overridden($tenant->id, 'core', 'denyemailaddresses')) {
            $mform->setDefault('denyemailaddresses_override', '1');
            $mform->setDefault('denyemailaddresses', config::get($tenant->id, 'core', 'denyemailaddresses'));
        } else {
            $mform->setDefault('denyemailaddresses', $default);
            $mform->setDefault('denyemailaddresses_override', '0');
        }
        $mform->hideIf('denyemailaddresses', 'denyemailaddresses_override', 'eq', '0');
        $mform->addElement('static', 'denyemailaddresses_desc', '', markdown_to_html(get_string('configdenyemailaddresses', 'core_admin')));

        $default = get_config('core', 'denyemailaddresses');
        if ($default === '') {
            $defaultstr = get_string('emptysettingvalue', 'core_admin');
        } else {
            $defaultstr = s(shorten_text($default, 20));
        }
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'auth_instructions_override', get_string('config_override_value', 'tool_mutenancy', $defaultstr));
        $group[] = $mform->createElement('editor', 'auth_instructions', get_string('instructions', 'core_auth'), ['rows' => 6, 'autosave' => false]);
        $mform->addGroup(
            $group,
            'auth_instructions_group',
            '<div>' . get_string('instructions', 'core_auth') . '<div class="small text-muted">auth_instructions</div></div>',
            '<div style="width: 100%"/>',
            false
        );
        if (config::is_overridden($tenant->id, 'core', 'auth_instructions')) {
            $mform->setDefault('auth_instructions_override', '1');
            $mform->setDefault('auth_instructions', ['text' => config::get($tenant->id, 'core', 'auth_instructions'), 'format' => FORMAT_HTML]);
        } else {
            $mform->setDefault('auth_instructions', ['text' => get_config('core', 'auth_instructions'), 'format' => FORMAT_HTML]);
            $mform->setDefault('auth_instructions_override', '0');
        }
        $mform->hideIf('auth_instructions', 'auth_instructions_override', 'eq', '0');
        $mform->addElement('static', 'auth_instructions_desc', '', markdown_to_html(get_string('authinstructions', 'core_auth')));

        $this->add_action_buttons(true, get_string('update'));
    }
}
