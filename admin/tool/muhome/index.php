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
// phpcs:disable moodle.Files.RequireLogin.Missing

/**
 * Custom home page.
 *
 * @package    tool_muhome
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use tool_muhome\local\page;
use tool_mulib\output\header_actions;

/** @var moodle_page $CFG */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */

require('../../../config.php');

$pageid = optional_param('pageid', 0, PARAM_INT);

$syscontext = context_system::instance();

// If the site is currently under maintenance, then print a message.
if (!empty($CFG->maintenance_enabled)) {
    if (!has_capability('moodle/site:maintenanceaccess', $syscontext)) {
        print_maintenance_message();
    }
}

// Make sure site is upgraded when accessing homepage as admin.
if (has_capability('moodle/site:config', $syscontext) && moodle_needs_upgrading()) {
    redirect(new url('/admin/index.php'));
}

if (!empty($CFG->forcelogin)) {
    require_login();
}

// Always update mypages cache.
$mypages = page::get_my_pages(false);

if ($pageid) {
    $page = $DB->get_record('tool_muhome_page', ['id' => $pageid], '*', MUST_EXIST);
} else {
    if (!$mypages) {
        redirect(new moodle_url('/', ['redirect' => 0]));
    }
    $pageid = array_key_first($mypages);
    $page = $DB->get_record('tool_muhome_page', ['id' => $pageid], '*', MUST_EXIST);
}

$context = context::instance_by_id($page->contextid);
if (!isset($mypages[$page->id])) {
    require_login();
    require_capability('tool/muhome:view', $context);
    $PAGE->set_cacheable(false);
}

if (get_config('tool_muhome', 'replacehome') && array_key_first($mypages) == $pageid) {
    $currenturl = page::get_url(null);
} else {
    $currenturl = page::get_url($pageid);
}

$site = get_site();

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = format_string($page->title ?? $site->fullname);
$PAGE->set_title($title);
$PAGE->set_secondary_navigation(false);
$PAGE->set_pagetype(page::PAGE_TYPE);
$PAGE->set_subpage($page->id);
$PAGE->set_blocks_editing_capability('tool/muhome:manage');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_pagelayout('mydashboard');
$PAGE->blocks->add_region('content');
$PAGE->set_heading($title);

$actions = new header_actions(get_string('page_actions', 'tool_muhome'));

if (has_capability('tool/muhome:manage', $context)) {
    $link = new \tool_mulib\output\ajax_form\link(
        new url('/admin/tool/muhome/management/page_update.php', ['id' => $page->id]),
        get_string('page_update', 'tool_muhome'),
        'i/settings'
    );
    $actions->get_dropdown()->add_ajax_form($link);
}
if (has_capability('tool/muhome:view', $context)) {
    $url = new url('/admin/tool/muhome/management/index.php', ['contextid' => $context->id]);
    $actions->get_dropdown()->add_item(get_string('management', 'tool_muhome'), $url, new \core\output\pix_icon('i/menubars', ''));
} else if (has_capability('tool/muhome:view', $syscontext)) {
    $url = new url('/admin/tool/muhome/management/index.php');
    $actions->get_dropdown()->add_item(get_string('management', 'tool_muhome'), $url, new \core\output\pix_icon('i/menubars', ''));
}

if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

$hint = \tool_muhome\local\management::get_page_hint($page, $context);
if ($hint !== null) {
    echo $OUTPUT->notification($hint, \core\output\notification::NOTIFY_INFO, false);
}

echo $OUTPUT->addblockbutton('content');
echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();
