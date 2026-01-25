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

/**
 * Custom home pages settings.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */
$ADMIN->add('appearance', new admin_category('tool_muhome', new lang_string('pluginname', 'tool_muhome')));

$settings = new admin_settingpage(
    'tool_muhome_settings',
    new lang_string('settings', 'tool_muhome'),
    'moodle/site:config'
);
$ADMIN->add('tool_muhome', $settings);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'tool_muhome/replacehome',
        new lang_string('setting_replacehome', 'tool_muhome'),
        new lang_string('setting_replacehome_desc', 'tool_muhome'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'tool_muhome/addmenu',
        new lang_string('setting_addmenu', 'tool_muhome'),
        new lang_string('setting_addmenu_desc', 'tool_muhome'),
        '',
        PARAM_TEXT,
        50
    ));
}

$ADMIN->add('tool_muhome', new admin_externalpage(
    'tool_muhome_management',
    new lang_string('management', 'tool_muhome'),
    new url('/admin/tool/muhome/management/index.php'),
    'tool/muhome:view'
));
