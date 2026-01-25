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
 * Tenant settings.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;

defined('MOODLE_INTERNAL') || die();

// phpcs:ignore moodle.Commenting.InlineComment.TypeHintingMatch
/** @var admin_root $ADMIN */

$ADMIN->add('root', new admin_category('tool_mutenancy', new lang_string('pluginname', 'tool_mutenancy')), 'payment');

if (tenancy::is_active()) {
    $ADMIN->add(
        'tool_mutenancy',
        new admin_externalpage(
            'tool_mutenancy_tenants',
            new lang_string('tenants', 'tool_mutenancy'),
            new \core\url('/admin/tool/mutenancy/index.php'),
            'tool/mutenancy:view'
        )
    );
} else {
    $ADMIN->add(
        'tool_mutenancy',
        new admin_externalpage(
            'tool_mutenancy_tenants',
            new lang_string('tenants', 'tool_mutenancy'),
            new \core\url('/admin/tool/mutenancy/index.php'),
            'moodle/site:config'
        )
    );
}

$settings = new admin_settingpage(
    'tool_mutenancy_settings',
    new lang_string('settings', 'tool_mutenancy'),
    'moodle/site:config'
);

$settings->add(new admin_setting_configtext(
    'tool_mutenancy/tenantlimit',
    new lang_string('setting_tenantlimit', 'tool_mutenancy'),
    new lang_string('setting_tenantlimit_desc', 'tool_mutenancy'),
    50,
    PARAM_INT,
    4
));

$settings->add(new admin_setting_configcheckbox(
    'tool_mutenancy/tenantprimarynav',
    new lang_string('setting_tenantprimarynav', 'tool_mutenancy'),
    new lang_string('setting_tenantprimarynav_desc', 'tool_mutenancy'),
    1
));

$settings->add(new admin_setting_configtext(
    'tool_mutenancy/tenantentity',
    new lang_string('setting_tenantentity', 'tool_mutenancy'),
    new lang_string('setting_tenantentity_desc', 'tool_mutenancy'),
    '',
    PARAM_TEXT,
    15
));

$settings->add(new admin_setting_configtext(
    'tool_mutenancy/tenantentities',
    new lang_string('setting_tenantentities', 'tool_mutenancy'),
    new lang_string('setting_tenantentities_desc', 'tool_mutenancy'),
    '',
    PARAM_TEXT,
    15
));

$setting = new admin_setting_configcheckbox(
    'tool_mutenancy/allowguests',
    new lang_string('setting_allowguests', 'tool_mutenancy'),
    new lang_string('setting_allowguests_desc', 'tool_mutenancy'),
    0
);
$setting->set_updatedcallback(function () {
    cache_helper::purge_by_event('changesincourse');
    cache_helper::purge_by_event('changesincoursecat');
});
$settings->add($setting);

$ADMIN->add('tool_mutenancy', $settings, 'tool_mutenancy_tenants');
