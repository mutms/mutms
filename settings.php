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
// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch

/**
 * Interactive book settings.
 *
 * @package    mod_mubook
 * @copyright  2004 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mubook\local\toc;

defined('MOODLE_INTERNAL') || die;

/** @var admin_root $ADMIN */

if ($ADMIN->fulltree) {
    /** @var \admin_settingpage $settings */

    // General settings.

    $settings->add(new admin_setting_configcheckbox(
        'mubook/restoreothertrustunsafe',
        get_string('restoreothertrustunsafe', 'mod_mubook'),
        get_string('restoreothertrustunsafe_desc', 'mod_mubook'),
        0
    ));

    // New module editing form defaults.

    $settings->add(new admin_setting_heading(
        'bookmodeditdefaults',
        get_string('modeditdefaults', 'admin'),
        get_string('condifmodeditdefaults', 'admin')
    ));

    $settings->add(new admin_setting_configselect(
        'mubook/numberingdefault',
        get_string('numberingdefault', 'mod_mubook'),
        '',
        1,
        function (): array {
            return toc::get_numbering_menu();
        }
    ));

    $settings->add(new admin_setting_configselect(
        'mubook/contentdefault',
        get_string('contentdefault', 'mod_mubook'),
        get_string('contentdefault_desc', 'mod_mubook'),
        'html',
        function (): array {
            $cman = \core\di::get(\mod_mubook\local\content_manager::class);
            return $cman->get_types_menu(true);
        }
    ));

    $settings->add(new admin_setting_configselect(
        'mubook/markdownhtml',
        get_string('markdown_html_setting', 'mod_mubook'),
        get_string('markdown_html_setting_desc', 'mod_mubook'),
        \mod_mubook\local\markdown_formatter::HTML_ALLOW,
        function (): array {
            return \mod_mubook\local\markdown_formatter::get_html_options();
        }
    ));
}
