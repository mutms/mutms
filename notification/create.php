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
 * Add a new notification.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\local\notification\util;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require('../../../../config.php');

$component = required_param('component', PARAM_COMPONENT);
$instanceid = required_param('instanceid', PARAM_INT);

require_login();

/** @var class-string<\tool_mulib\local\notification\manager> $manager */
$manager = \tool_mulib\local\notification\util::get_manager_classname($component);
if (!$manager) {
    throw new invalid_parameter_exception('Invalid notification component');
}

$returnurl = $manager::get_instance_management_url($instanceid);
if (!$manager::can_manage($instanceid)) {
    redirect($returnurl);
}
$context = $manager::get_instance_context($instanceid);

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/mulib/notification/add.php', ['component' => 'component', 'instanceid' => $instanceid]);

$form = new \tool_mulib\local\form\notification_create(
    null,
    ['instanceid' => $instanceid, 'component' => $component, 'manager' => $manager]
);
if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    if (!empty($data->types)) {
        foreach ($data->types as $type => $enabled) {
            if (!$enabled) {
                continue;
            }
            $d = [
                'component' => $data->component,
                'instanceid' => $data->instanceid,
                'enabled' => $data->enabled,
                'notificationtype' => $type,
            ];
            util::notification_create((array)$d);
        }
    }
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
