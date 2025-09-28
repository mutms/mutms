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

namespace tool_mutenancy\output\tenant_auth;

use tool_mutenancy\local\config;

/**
 * Tenant auth settings renderer.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class renderer extends \tool_mutenancy\output\tenant_renderer_base {
    #[\Override]
    public function render_section(\stdClass $tenant): string {
        $tenantcontext = \context_tenant::instance($tenant->id);
        $canconfig = has_capability('tool/mutenancy:configauth', $tenantcontext);

        $result = '';

        $details = new \tool_mulib\output\entity_details();

        if (config::is_overridden($tenant->id, 'core', 'registerauth')) {
            $auth = config::get($tenant->id, 'core', 'registerauth');
            $isdefault = false;
        } else {
            $auth = get_config('core', 'registerauth');
            $isdefault = true;
        }
        if ($auth) {
            $auth = get_string('pluginname', 'auth_' . $auth);
        } else {
            $auth = get_string('disabled', 'core_admin');
        }
        if ($isdefault) {
            $auth = get_string('config_default_value', 'tool_mutenancy', $auth);
        }
        if (config::is_value_forced('core', 'registerauth')) {
            $auth .= ' ' . \html_writer::span(get_string('configoverride', 'admin'), 'alert-info');
        }
        $details->add(get_string('selfregistration', 'core_auth'), $auth);

        if (config::is_overridden($tenant->id, 'core', 'showloginform')) {
            $showloginform = config::get($tenant->id, 'core', 'showloginform');
            $isdefault = false;
        } else {
            $showloginform = get_config('core', 'showloginform');
            $isdefault = true;
        }
        $showloginform = $showloginform ? get_string('yes') : get_string('no');
        if ($isdefault) {
            $showloginform = get_string('config_default_value', 'tool_mutenancy', $showloginform);
        }
        if (config::is_value_forced('core', 'showloginform')) {
            $showloginform .= ' ' . \html_writer::span(get_string('configoverride', 'admin'), 'alert-info');
        }
        $details->add(get_string('showloginform', 'core_auth'), $showloginform);

        if (config::is_overridden($tenant->id, 'core', 'allowemailaddresses')) {
            $allowemailaddresses = config::get($tenant->id, 'core', 'allowemailaddresses');
            $isdefault = false;
        } else {
            $allowemailaddresses = get_config('core', 'allowemailaddresses');
            $isdefault = true;
        }
        $allowemailaddresses = $allowemailaddresses === '' ? get_string('emptysettingvalue', 'core_admin') : s($allowemailaddresses);
        if ($isdefault) {
            $allowemailaddresses = get_string('config_default_value', 'tool_mutenancy', $allowemailaddresses);
        }
        if (config::is_value_forced('core', 'allowemailaddresses')) {
            $allowemailaddresses .= ' ' . \html_writer::span(get_string('configoverride', 'admin'), 'alert-info');
        }
        $details->add(get_string('allowemailaddresses', 'core_admin'), $allowemailaddresses);

        if (config::is_overridden($tenant->id, 'core', 'denyemailaddresses')) {
            $denyemailaddresses = config::get($tenant->id, 'core', 'denyemailaddresses');
            $isdefault = false;
        } else {
            $denyemailaddresses = get_config('core', 'denyemailaddresses');
            $isdefault = true;
        }
        $denyemailaddresses = $denyemailaddresses === '' ? get_string('emptysettingvalue', 'core_admin') : s($denyemailaddresses);
        if ($isdefault) {
            $denyemailaddresses = get_string('config_default_value', 'tool_mutenancy', $denyemailaddresses);
        }
        if (config::is_value_forced('core', 'denyemailaddresses')) {
            $denyemailaddresses .= ' ' . \html_writer::span(get_string('configoverride', 'admin'), 'alert-info');
        }
        $details->add(get_string('denyemailaddresses', 'core_admin'), $denyemailaddresses);

        if (config::is_overridden($tenant->id, 'core', 'auth_instructions')) {
            $instructions = config::get($tenant->id, 'core', 'auth_instructions');
            $isdefault = false;
        } else {
            $instructions = get_config('core', 'auth_instructions');
            $isdefault = true;
        }
        $instructions = (trim($instructions) === '') ? get_string('emptysettingvalue', 'core_admin') : clean_text($instructions);
        if ($isdefault) {
            $instructions = get_string('config_default_value', 'tool_mutenancy', $instructions);
        }
        if (config::is_value_forced('core', 'auth_instructions')) {
            $instructions .= ' ' . \html_writer::span(get_string('configoverride', 'admin'), 'alert-info');
        }
        $details->add(get_string('instructions', 'core_auth'), $instructions);

        $result .= $this->output->render($details);

        $buttons = [];
        if ($canconfig) {
            $url = new \moodle_url('/admin/tool/mutenancy/management/auth_edit.php', ['id' => $tenant->id]);
            $button = new \tool_mulib\output\ajax_form\button($url, get_string('auth_edit', 'tool_mutenancy'));
            $button->set_form_size('xl');
            $buttons[] = $this->render($button);
        }
        $result .= '<div class="buttons">' . implode('', $buttons) . '</div>';

        return $result;
    }
}
