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
 * Tenant appearance edit form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class logos_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $currentdata = $this->_customdata['currentdata'];
        $tenant = $this->_customdata['tenant'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $tenant->id);

        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'logo_override', get_string('config_override', 'tool_mutenancy'));
        $mform->addGroup(
            $group,
            'logo_group',
            '<div>' . get_string('logo', 'core_admin') . '<div class="small text-muted">core_admin | logo</div></div>',
            ' ',
            false
        );
        $mform->addElement('filemanager', 'logo', '<span class="accesshide">' . get_string('logo', 'core_admin') . '</span>', null, self::get_logo_options());
        if (config::is_overridden($tenant->id, 'core_admin', 'logo')) {
            $mform->setDefault('logo_override', '1');
        } else {
            $mform->setDefault('logo_override', '0');
        }
        $mform->hideIf('logo', 'logo_override', 'eq', '0');
        $mform->setDefault('logo', $currentdata->logo);
        $mform->addElement('static', 'logo_desc', '', markdown_to_html(get_string('logo_desc', 'core_admin')));

        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'logocompact_override', get_string('config_override', 'tool_mutenancy'));
        $mform->addGroup(
            $group,
            'logocompact_group',
            '<div>' . get_string('logocompact', 'core_admin') . '<div class="small text-muted">core_admin | logocompact</div></div>',
            ' ',
            false
        );
        $mform->addElement('filemanager', 'logocompact', '<span class="accesshide">' . get_string('logocompact', 'core_admin') . '</span>', null, self::get_logo_options());
        if (config::is_overridden($tenant->id, 'core_admin', 'logocompact')) {
            $mform->setDefault('logocompact_override', '1');
        } else {
            $mform->setDefault('logocompact_override', '0');
        }
        $mform->hideIf('logocompact', 'logocompact_override', 'eq', '0');
        $mform->setDefault('logocompact', $currentdata->logocompact);
        $mform->addElement('static', 'logocompact_desc', '', markdown_to_html(get_string('logocompact_desc', 'core_admin')));

        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'favicon_override', get_string('config_override', 'tool_mutenancy'));
        $mform->addGroup(
            $group,
            'favicon_group',
            '<div>' . get_string('favicon', 'core_admin') . '<div class="small text-muted">core_admin | favicon</div></div>',
            ' ',
            false
        );
        $mform->addElement('filemanager', 'favicon', '<span class="accesshide">' . get_string('favicon', 'core_admin') . '</span>', null, self::get_favicon_options());
        if (config::is_overridden($tenant->id, 'core_admin', 'favicon')) {
            $mform->setDefault('favicon_override', '1');
        } else {
            $mform->setDefault('favicon_override', '0');
        }
        $mform->hideIf('favicon', 'favicon_override', 'eq', '0');
        $mform->setDefault('favicon', $currentdata->favicon);
        $mform->addElement('static', 'favicon_desc', '', markdown_to_html(get_string('favicon_desc', 'core_admin')));

        $this->add_action_buttons(true, get_string('update'));
    }

    /**
     * File manager options for logos.
     *
     * @return array
     */
    public static function get_logo_options(): array {
        return [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.jpg', '.png', '.gif'], // No SVG for security reasons!
        ];
    }

    /**
     * File manager options for favicons.
     *
     * @return array
     */
    public static function get_favicon_options(): array {
        return [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.jpg', '.png', '.ico', '.gif'], // No SVG for security reasons!
        ];
    }
}
