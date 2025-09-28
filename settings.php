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
 * Program settings.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var core_renderer $OUTPUT */
/** @var admin_root $ADMIN */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('root', new admin_category('tool_muprog', new lang_string('programs', 'tool_muprog')), 'security');

$settings = new admin_settingpage(
    'tool_muprog_settings',
    new lang_string('settings', 'tool_muprog'),
    'moodle/site:config'
);
$ADMIN->add('tool_muprog', $settings);
if ($ADMIN->fulltree) {
    if (!enrol_is_enabled('muprog')) {
        $url = new moodle_url('/admin/enrol.php', ['sesskey' => sesskey(), 'action' => 'enable', 'enrol' => 'muprog']);
        $a = new stdClass();
        $a->url = $url->out(false);
        $notify = get_string('plugindisabled', 'tool_muprog', $a);
        $notify = markdown_to_html($notify);
        $notify = new \core\output\notification($notify, \core\output\notification::NOTIFY_WARNING);
        $settings->add(new admin_setting_heading('tool_muprog_enable_plugin', '', $OUTPUT->render($notify)));
    }
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect(
            'tool_muprog/roleid',
            new lang_string('enrolrole', 'tool_muprog'),
            new lang_string('enrolrole_desc', 'tool_muprog'),
            $student->id ?? null,
            $options
        ));

        unset($options);
        unset($student);
    }

    $settings->add(new admin_setting_configcheckbox(
        'tool_muprog/source_approval_allownew',
        new lang_string('source_approval_allownew', 'tool_muprog'),
        new lang_string('source_approval_allownew_desc', 'tool_muprog'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'tool_muprog/source_cohort_allownew',
        new lang_string('source_cohort_allownew', 'tool_muprog'),
        new lang_string('source_cohort_allownew_desc', 'tool_muprog'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'tool_muprog/source_selfallocation_allownew',
        new lang_string('source_selfallocation_allownew', 'tool_muprog'),
        new lang_string('source_selfallocation_allownew_desc', 'tool_muprog'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'tool_muprog/source_mucertify_allownew',
        new lang_string('source_mucertify_allownew', 'tool_muprog'),
        new lang_string('source_mucertify_allownew_desc', 'tool_muprog'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'tool_muprog/source_program_allownew',
        new lang_string('source_program_allownew', 'tool_muprog'),
        new lang_string('source_program_allownew_desc', 'tool_muprog'),
        1
    ));
}

$ADMIN->add('tool_muprog', new admin_externalpage(
    'tool_muprog_customfield_program',
    new lang_string('customfields', 'tool_muprog'),
    new moodle_url("/admin/tool/muprog/management/customfield_program.php"),
    'tool/muprog:configurecustomfields'
));

$ADMIN->add('tool_muprog', new admin_externalpage(
    'tool_muprog_customfield_allocation',
    new lang_string('customfields_allocation', 'tool_muprog'),
    new moodle_url("/admin/tool/muprog/management/customfield_allocation.php"),
    'tool/muprog:configurecustomfields'
));

$ADMIN->add('tool_muprog', new admin_externalpage(
    'tool_muprog_management',
    new lang_string('management', 'tool_muprog'),
    new moodle_url("/admin/tool/muprog/management/index.php"),
    'tool/muprog:view'
));


$settings = null;
