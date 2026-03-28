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

/**
 * Certification settings.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var core_renderer $OUTPUT */
/** @var admin_root $ADMIN */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('root', new admin_category('tool_mucertify', new lang_string('certifications', 'tool_mucertify')), 'competencies');

$settings = new admin_settingpage(
    'tool_mucertify_settings',
    new lang_string('settings', 'tool_mucertify'),
    'moodle/site:config'
);
$ADMIN->add('tool_mucertify', $settings);
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'tool_mucertify/source_approval_allownew',
        new lang_string('source_approval_allownew', 'tool_mucertify'),
        new lang_string('source_approval_allownew_desc', 'tool_mucertify'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'tool_mucertify/source_cohort_allownew',
        new lang_string('source_cohort_allownew', 'tool_mucertify'),
        new lang_string('source_cohort_allownew_desc', 'tool_mucertify'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'tool_mucertify/source_selfassignment_allownew',
        new lang_string('source_selfassignment_allownew', 'tool_mucertify'),
        new lang_string('source_selfassignment_allownew_desc', 'tool_mucertify'),
        1
    ));
}

$ADMIN->add('tool_mucertify', new admin_externalpage(
    'tool_mucertify_customfield_certification',
    new lang_string('customfields', 'tool_mucertify'),
    new \core\url("/admin/tool/mucertify/management/customfield_certification.php"),
    'tool/mucertify:configurecustomfields'
));

$ADMIN->add('tool_mucertify', new admin_externalpage(
    'tool_mucertify_customfield_assignment',
    new lang_string('customfields_assignment', 'tool_mucertify'),
    new \core\url("/admin/tool/mucertify/management/customfield_assignment.php"),
    'tool/mucertify:configurecustomfields'
));

$ADMIN->add('tool_mucertify', new admin_externalpage(
    'tool_mucertify_management',
    new lang_string('management', 'tool_mucertify'),
    new \core\url("/admin/tool/mucertify/management/index.php"),
    'tool/mucertify:view'
));

$settings = null;
