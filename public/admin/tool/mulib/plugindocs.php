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
 * Plugin documentation rendering.
 *
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\local\plugindocs;

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require('../../../config.php');
require_once($CFG->libdir . '/filelib.php');

$relativepath = get_file_argument();

require_login();
if (!$relativepath) {
    send_file_not_found();
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/admin/tool/mulib/plugindocs.php/' . $relativepath);
$CFG->docroot = null; // Do not show any docs links in this page!

$relativepath = ltrim($relativepath, '/');
$parts = explode('/', $relativepath);
if (count($parts) < 2) {
    send_file_not_found();
}
$component = array_shift($parts);
$component = clean_param($component, PARAM_COMPONENT);
$file = implode('/', $parts);
$file = clean_param($file, PARAM_PATH);

if (!preg_match(plugindocs::FILE_REGEX, $file)) {
    send_file_not_found();
}

$plugindir = core_component::get_component_directory($component);
if (!$plugindir) {
    send_file_not_found();
}

$basedir = $plugindir . '/docs/en';
if (!file_exists("$basedir/index.md")) {
    send_file_not_found();
}

$filepath = "$basedir/$file";

if (!file_exists($filepath)) {
    send_file_not_found();
}

if (str_starts_with($file, 'img/') && str_ends_with($file, '.png')) {
    send_file($filepath, basename($file), 60);
    die;
}

if (str_ends_with($file, '.md')) {
    $PAGE->set_pagelayout('popup'); // Page with the least distractions.
    $PAGE->set_secondary_navigation(false);
    $PAGE->requires->css('/admin/tool/mulib/plugindocs.css');
    $PAGE->set_title(get_string('plugindocs', 'tool_mulib', get_string('pluginname', $component)));

    $content = file_get_contents($filepath);
    $content = plugindocs::render_github_markdown($content);

    echo $OUTPUT->header();
    echo '<div id="tool_mulib_plugindocs">';
    echo $content;
    echo '</div>';
    echo $OUTPUT->footer();
    die;
}

send_file_not_found();
