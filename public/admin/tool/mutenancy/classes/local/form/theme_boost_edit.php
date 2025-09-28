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
use tool_mutenancy\local\appearance;

/**
 * Tenant boost theme edit form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class theme_boost_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $currentdata = $this->_customdata['currentdata'];
        $tenant = $this->_customdata['tenant'];
        $syscontext = \context_system::instance();

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $tenant->id);

        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'theme_boost', 'preset', 0, 'itemid, filepath, filename', false);
        $choices = [];
        foreach ($files as $file) {
            $choices[$file->get_filename()] = $file->get_filename();
        }
        // These are the built-in presets.
        $choices['default.scss'] = 'default.scss';
        $choices['plain.scss'] = 'plain.scss';

        $default = get_config('theme_boost', 'preset');
        if ($default === '') {
            $defaultstr = get_string('emptysettingvalue', 'core_admin');
        } else {
            $defaultstr = s($default);
        }
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'preset_override', get_string('config_default_value', 'tool_mutenancy', $defaultstr));
        $group[] = $mform->createElement('select', 'preset', get_string('preset', 'theme_boost'), $choices);
        $mform->addGroup(
            $group,
            'preset_group',
            '<div>' . get_string('preset', 'theme_boost') . '<div class="small text-muted">theme_boost | preset</div></div>',
            '<div style="width: 100%"/>',
            false
        );
        if (config::is_overridden($tenant->id, 'theme_boost', 'preset')) {
            $mform->setDefault('preset_override', '1');
            $mform->setDefault('preset', config::get($tenant->id, 'theme_boost', 'preset'));
        } else {
            $mform->setDefault('preset', $default);
            $mform->setDefault('preset_override', '0');
        }
        $mform->hideIf('preset', 'preset_override', 'eq', '0');
        $mform->addElement('static', 'preset_desc', '', markdown_to_html(get_string('preset_desc', 'theme_boost')));

        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'backgroundimage_override', get_string('config_override', 'tool_mutenancy'));
        $mform->addGroup(
            $group,
            'backgroundimage_group',
            '<div>' . get_string('backgroundimage', 'theme_boost') . '<div class="small text-muted">theme_boost | backgroundimage</div></div>',
            ' ',
            false
        );
        $mform->addElement(
            'filemanager',
            'backgroundimage',
            '<span class="accesshide">' . get_string('backgroundimage', 'theme_boost') . '</span>',
            null,
            self::get_logo_options()
        );
        if (config::is_overridden($tenant->id, 'theme_boost', 'backgroundimage')) {
            $mform->setDefault('backgroundimage_override', '1');
        } else {
            $mform->setDefault('backgroundimage_override', '0');
        }
        $mform->hideIf('backgroundimage', 'backgroundimage_override', 'eq', '0');
        $mform->setDefault('backgroundimage', $currentdata->backgroundimage);
        $mform->addElement('static', 'backgroundimage_desc', '', markdown_to_html(get_string('backgroundimage_desc', 'theme_boost')));

        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'loginbackgroundimage_override', get_string('config_override', 'tool_mutenancy'));
        $mform->addGroup(
            $group,
            'loginbackgroundimage_group',
            '<div>' . get_string('loginbackgroundimage', 'theme_boost') . '<div class="small text-muted">theme_boost | loginbackgroundimage</div></div>',
            ' ',
            false
        );
        $mform->addElement(
            'filemanager',
            'loginbackgroundimage',
            '<span class="accesshide">' . get_string('loginbackgroundimage', 'theme_boost') . '</span>',
            null,
            self::get_logo_options()
        );
        if (config::is_overridden($tenant->id, 'theme_boost', 'loginbackgroundimage')) {
            $mform->setDefault('loginbackgroundimage_override', '1');
        } else {
            $mform->setDefault('loginbackgroundimage_override', '0');
        }
        $mform->hideIf('loginbackgroundimage', 'loginbackgroundimage_override', 'eq', '0');
        $mform->setDefault('loginbackgroundimage', $currentdata->loginbackgroundimage);
        $mform->addElement('static', 'loginbackgroundimage_desc', '', markdown_to_html(get_string('loginbackgroundimage_desc', 'theme_boost')));

        $default = get_config('theme_boost', 'brandcolor');
        if ($default === '') {
            $defaultstr = get_string('emptysettingvalue', 'core_admin');
        } else {
            $defaultstr = s($default);
        }
        $group = [];
        $group[] = $mform->createElement('advcheckbox', 'brandcolor_override', get_string('config_default_value', 'tool_mutenancy', $defaultstr));
        $group[] = $mform->createElement('text', 'brandcolor', get_string('brandcolor', 'theme_boost'), ['size' => 10]);
        $mform->addGroup(
            $group,
            'brandcolor_group',
            '<div>' . get_string('brandcolor', 'theme_boost') . '<div class="small text-muted">theme_boost | brandcolor</div></div>',
            '<div style="width: 100%"/>',
            false
        );
        if (config::is_overridden($tenant->id, 'theme_boost', 'brandcolor')) {
            $mform->setDefault('brandcolor_override', '1');
            $mform->setDefault('brandcolor', config::get($tenant->id, 'theme_boost', 'brandcolor'));
        } else {
            $mform->setDefault('brandcolor', $default);
            $mform->setDefault('brandcolor_override', '0');
        }
        $mform->hideIf('brandcolor', 'brandcolor_override', 'eq', '0');
        $mform->setType('brandcolor', PARAM_RAW);
        $mform->addElement('static', 'brandcolor_desc', '', markdown_to_html(get_string('brandcolor_desc', 'theme_boost')));

        if (has_capability('moodle/site:config', $syscontext)) {
            $group = [];
            $group[] = $mform->createElement('advcheckbox', 'scsspre_override', get_string('config_override', 'tool_mutenancy'));
            $mform->addGroup(
                $group,
                'scsspre_group',
                '<div>' . get_string('rawscsspre', 'theme_boost') . '<div class="small text-muted">theme_boost | scsspre</div></div>',
                '<div style="width: 100%"/>',
                false
            );
            $mform->addElement('textarea', 'scsspre', '<span class="accesshide">' . get_string('rawscsspre', 'theme_boost') . '</span>', ['rows' => 6]);
            if (config::is_overridden($tenant->id, 'theme_boost', 'scsspre')) {
                $mform->setDefault('scsspre_override', '1');
                $mform->setDefault('scsspre', config::get($tenant->id, 'theme_boost', 'scsspre'));
            } else {
                $mform->setDefault('scsspre', get_config('theme_boost', 'scsspre'));
                $mform->setDefault('scsspre_override', '0');
            }
            $mform->hideIf('scsspre', 'scsspre_override', 'eq', '0');
            $mform->setType('scsspre', PARAM_RAW);
            $mform->addElement('static', 'scsspre_desc', '', markdown_to_html(get_string('rawscsspre_desc', 'theme_boost')));

            $group = [];
            $group[] = $mform->createElement('advcheckbox', 'scss_override', get_string('config_default', 'tool_mutenancy'));
            $mform->addGroup(
                $group,
                'scss_group',
                '<div>' . get_string('rawscss', 'theme_boost') . '<div class="small text-muted">theme_boost | scss</div></div>',
                '<div style="width: 100%"/>',
                false
            );
            $mform->addElement('textarea', 'scss', '<span class="accesshide">' . get_string('rawscss', 'theme_boost') . '</span>', ['rows' => 6]);
            if (config::is_overridden($tenant->id, 'theme_boost', 'scss')) {
                $mform->setDefault('scss_override', '1');
                $mform->setDefault('scss', config::get($tenant->id, 'theme_boost', 'scss'));
            } else {
                $mform->setDefault('scss', get_config('theme_boost', 'scss'));
                $mform->setDefault('scss_override', '0');
            }
            $mform->hideIf('scss', 'scss_override', 'eq', '0');
            $mform->setType('scss', PARAM_RAW);
            $mform->addElement('static', 'scss_desc', '', markdown_to_html(get_string('rawscss_desc', 'theme_boost')));
        }

        $this->add_action_buttons(true, get_string('update'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['brandcolor_override']) {
            if ($data['brandcolor'] !== '') {
                if (!appearance::is_valid_color($data['brandcolor'])) {
                    $errors['brandcolor_group'] = get_string('error');
                }
            }
        }

        return $errors;
    }

    /**
     * File manager options for boost.
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
}
