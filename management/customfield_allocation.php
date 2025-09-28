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
 * Configuration for program allocation custom fields.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var stdClass $CFG */
/** @var moodle_page $PAGE */

require('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_muprog_customfield_allocation');

/** @var \core_customfield\output\renderer $output */
$output = $PAGE->get_renderer('core_customfield');

$handler = \tool_muprog\customfield\allocation_handler::create();
$outputpage = new \core_customfield\output\management($handler);

echo $output->header(),
$output->heading(new lang_string('customfields', 'tool_muprog')),
$output->render($outputpage),
$output->footer();
