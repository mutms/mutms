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
 * Settings for the muprogmyoverview block
 *
 * @package    block_muprogmyoverview
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2025 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/** @var stdClass $CFG */
/** @var admin_settingpage $settings */
/** @var admin_root $ADMIN */

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/blocks/muprogmyoverview/lib.php');

    // Presentation options heading.
    $settings->add(new admin_setting_heading(
        'block_muprogmyoverview/appearance',
        get_string('appearance', 'admin'),
        ''
    ));

    // Display Program Categories on Dashboard program items (cards, lists, description items).
    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaycategories',
        get_string('displaycategories', 'block_muprogmyoverview'),
        get_string('displaycategories_help', 'block_muprogmyoverview'),
        0
    )); // Program categories are less useful than course categories.

    // Enable / Disable available layouts.
    $choices = [BLOCK_MUPROGMYOVERVIEW_VIEW_CARD => get_string('card', 'block_muprogmyoverview'),
            BLOCK_MUPROGMYOVERVIEW_VIEW_LIST => get_string('list', 'block_muprogmyoverview'),
            BLOCK_MUPROGMYOVERVIEW_VIEW_DESCRIPTION => get_string('description', 'block_muprogmyoverview')];
    $settings->add(new admin_setting_configmulticheckbox(
        'block_muprogmyoverview/layouts',
        get_string('layouts', 'block_muprogmyoverview'),
        get_string('layouts_help', 'block_muprogmyoverview'),
        $choices,
        $choices
    ));
    unset($choices);

    // Enable / Disable program filter items.
    $settings->add(new admin_setting_heading(
        'block_muprogmyoverview/availablegroupings',
        get_string('availablegroupings', 'block_muprogmyoverview'),
        get_string('availablegroupings_desc', 'block_muprogmyoverview')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaygroupingallincludinghidden',
        get_string('allincludinghidden', 'block_muprogmyoverview'),
        '',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaygroupingall',
        get_string('all', 'block_muprogmyoverview'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaygroupinginprogress',
        get_string('inprogress', 'block_muprogmyoverview'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaygroupingpast',
        get_string('past', 'block_muprogmyoverview'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaygroupingfuture',
        get_string('future', 'block_muprogmyoverview'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaygroupingfavourites',
        get_string('favourites', 'block_muprogmyoverview'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_muprogmyoverview/displaygroupinghidden',
        get_string('hiddenprograms', 'block_muprogmyoverview'),
        '',
        1
    ));
}
