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
 * Log-in-as external functions.
 *
 * @package    tool_muloginas
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_muloginas_token_create' => [
        'classname' => tool_muloginas\external\token_create::class,
        'description' => 'Create log-in-as token from popup.js, it cannot be used as normal web services.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_muloginas_token_check' => [
        'classname' => tool_muloginas\external\token_check::class,
        'description' => 'Check log-in-as token status from popup.js, it cannot be used as normal web services.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
