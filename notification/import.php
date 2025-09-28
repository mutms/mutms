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
 * Import notification.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

use tool_mulib\local\notification\util;

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$component = required_param('component', PARAM_COMPONENT);
$instanceid = required_param('instanceid', PARAM_INT);
$frominstance = optional_param('frominstance', 0, PARAM_INT);

require_login();

/** @var class-string<\tool_mulib\local\notification\manager> $manager */
$manager = \tool_mulib\local\notification\util::get_manager_classname($component);
if (!$manager) {
    throw new invalid_parameter_exception('Invalid notification component');
}

$returnurl = $manager::get_instance_management_url($instanceid);
if (!$manager::can_manage($instanceid) || !$manager::is_import_supported()) {
    redirect($returnurl);
}
$context = $manager::get_instance_context($instanceid);

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/mulib/notification/import.php', ['component' => 'component', 'instanceid' => $instanceid]);

$form = null;
if (!$manager::validate_import_frominstance($instanceid, $frominstance)) {
    $form = new \tool_mulib\local\form\notification_import(null, [
        'instanceid' => $instanceid,
        'component' => $component,
        'manager' => $manager,
    ]);
    if ($form->is_cancelled()) {
        $form->ajax_form_cancelled($returnurl);
    } else if ($data = $form->get_data()) {
        $frominstance = $data->frominstance;
        unset($data);
        $form = null;
    }
}

if (!$form) {
    $form = new \tool_mulib\local\form\notification_import_confirmation(null, [
        'instanceid' => $instanceid,
        'component' => $component,
        'manager' => $manager,
        'frominstance' => $frominstance,
    ]);

    if ($form->is_cancelled()) {
        $form->ajax_form_cancelled($returnurl);
    }

    if ($data = $form->get_data()) {
        $notificationids = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'notificationid_') && $value == 1) {
                $notificationids[] = explode('_', $key, 2)[1];
            }
        }
        util::notification_import($data, $notificationids);

        $form->ajax_form_submitted($returnurl);
    }
}

$form->ajax_form_render();
