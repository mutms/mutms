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
 * Behat task execution helper.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is a fake CLI script, it is a really ugly hack which emulates CLI via web interface.
define('CLI_SCRIPT', true);
define('WEB_CRON_EMULATED_CLI', 'defined'); // Ugly ugly hack.
define('NO_OUTPUT_BUFFERING', true);

require('../../../../../config.php');

if (!defined('BEHAT_SITE_RUNNING')) {
    die;
}

error_reporting(E_ALL | 2048);
ini_set('display_errors', '1');
$CFG->debug = (E_ALL | 2048);
$CFG->debugdisplay = 1;

require_once($CFG->libdir . '/clilib.php');

\core\session\manager::write_close();

$taskname = required_param('behat_task', PARAM_RAW);
$taskname = ltrim($taskname, '\\');
$record = $DB->get_record('task_scheduled', ['classname' => '\\' . $taskname], '*', MUST_EXIST);

$task = \core\task\manager::get_scheduled_task($taskname);

// Do setup for cron task.
raise_memory_limit(MEMORY_EXTRA);
\core\cron::setup_user();

// Get lock.
$cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
    throw new Exception('Unable to obtain core_cron lock for scheduled task');
}
if (!$lock = $cronlockfactory->get_lock('\\' . get_class($task), 10)) {
    $cronlock->release();
    throw new Exception('Unable to obtain task lock for scheduled task');
}
$task->set_lock($lock);
$cronlock->release();

@header('Content-Type: text/plain; charset=utf-8');
@ini_set('html_errors', 'off');

try {
    // Prepare the renderer.
    \core\cron::prepare_core_renderer();

    $task->execute();

    // Restore the previous renderer.
    \core\cron::prepare_core_renderer();

    // Mark task complete.
    \core\task\manager::scheduled_task_complete($task);

    echo "\nScheduled task '$taskname' completed";
} catch (Throwable $e) {
    // Restore the previous renderer.
    \core\cron::prepare_core_renderer();

    // Mark task failed and throw exception.
    \core\task\manager::scheduled_task_failed($task);

    throw new Exception('Scheduled task failed', 0, $e);
}
