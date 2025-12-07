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
 * Create a new external database query.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\local\extdb\query;

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');

$component = optional_param('component', '', PARAM_ALPHANUMEXT);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/admin/tool/mulib/extdb/query_create.php', ['component' => $component, 'type' => $type]);
$PAGE->set_context($context);

$returnurl = new moodle_url('/admin/tool/mulib/extdb/queries.php');

['component' => $component, 'type' => $type]
    = \tool_mulib\local\extdb\form\query_create_select::decode_type_optmenu($component, $type);

if (!$type) {
    $form = new \tool_mulib\local\extdb\form\query_create_select();
} else {
    $currentdata = [
        'component' => $component,
        'type' => $type,
    ];
    $form = new \tool_mulib\local\extdb\form\query_create(null, ['currentdata' => $currentdata]);

    if ($form->is_cancelled()) {
        $form->ajax_form_cancelled($returnurl);
    }
    $data = $form->get_data();
    if ($data && empty($data->check)) {
        $query = query::create($data);
        $form->ajax_form_submitted($returnurl);
    }
}

$form->ajax_form_render();
