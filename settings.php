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
 * User relations and teams settings.
 *
 * @package    tool_murelation
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var core_renderer $OUTPUT */
/** @var admin_root $ADMIN */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('users', new admin_category('tool_murelation', new lang_string('pluginname', 'tool_murelation')));

$settings = new admin_settingpage(
    'tool_murelation_settings',
    new lang_string('settings', 'tool_murelation'),
    'moodle/site:config'
);
$ADMIN->add('tool_murelation', $settings);

if ($ADMIN->fulltree) {
    $choices = \tool_mulib\local\role_util::get_contextlevel_roles_menu(CONTEXT_USER);
    if (!$choices) {
        $notify = get_string('error_nouserroles', 'tool_murelation');
        $notify = new \core\output\notification($notify, \core\output\notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('tool_murelation_settings_roles', '', $OUTPUT->render($notify)));
        $choices = ['' => ''];
    }
    $settings->add(new admin_setting_configmultiselect(
        'tool_murelation/roles',
        new lang_string('settings_roles', 'tool_murelation'),
        new lang_string('settings_roles_desc', 'tool_murelation'),
        [],
        $choices
    ));
}

$ADMIN->add('tool_murelation', new admin_externalpage(
    'tool_murelation_frameworks',
    get_string('management_frameworks', 'tool_murelation'),
    new moodle_url('/admin/tool/murelation/management/index.php'),
    'tool/murelation:viewframeworks'
));
