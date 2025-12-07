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
 * Update external database query.
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

$id = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/admin/tool/mulib/extdb/query_update.php', ['id' => $id]);
$PAGE->set_context($context);

$returnurl = new moodle_url('/admin/tool/mulib/extdb/queries.php');

$query = $DB->get_record('tool_mulib_extdb_query', ['id' => $id], '*', MUST_EXIST);

$form = new \tool_mulib\local\extdb\form\query_update(null, ['query' => $query]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
}
$data = $form->get_data();
if ($data && empty($data->check)) {
    $query = query::update($data);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
