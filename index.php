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
 * My programs overview page.
 *
 * @package     block_muprogmyoverview
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $USER */

require_once(__DIR__ . '/../../config.php');

redirect_if_major_upgrade_required();

require_login();

if (!\tool_mulib\local\mulib::is_muprog_active()) {
    redirect(new moodle_url('/'));
}

$context = context_system::instance();

$title = get_string('myprograms', 'block_muprogmyoverview');

// Start setting up the page.
$PAGE->set_context($context);
$PAGE->set_url('/blocks/muprogmyoverview/');
$PAGE->add_body_classes(['limitedwidth']);
$PAGE->set_pagelayout('mycourses');
$PAGE->set_pagetype('block-muprogmyoverview-index');
$PAGE->blocks->add_region('content');
$PAGE->set_title($title);
$PAGE->set_heading($title);

\block_muprogmyoverview\local\util::ensure_block_added();

// No blocks can be edited on this page (including by managers/admins)!
$PAGE->force_lock_all_blocks();

// Make sure the enrolments are 100% up-to-date for the current user,
// this is where are they going to look first in case of any problems.
\tool_muprog\local\allocation::fix_user_enrolments(null, $USER->id);
\tool_muprog\local\notification_manager::trigger_notifications(null, $USER->id);

\block_muprogmyoverview\event\my_programs_viewed::create(['context' => $context])->trigger();

echo $OUTPUT->header();

echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();
