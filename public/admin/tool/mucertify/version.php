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
 * Certifications plugin version.
 *
 * @package     tool_mucertify
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var stdClass $plugin */
$plugin->component = 'tool_mucertify';
$plugin->version = 2026032650;
$plugin->requires = 2025041400;
$plugin->supported = [500, 501];

$plugin->release = 'v5.0.6.03';

$plugin->dependencies = [
    'tool_mulib' => 2026032650,
    'enrol_muprog' => 2026032650,
    'block_muprog_my' => 2026032650,
    'block_muprogmyoverview' => 2026032650,
    'tool_muprog' => 2026032650,
    'block_mucertify_my' => 2026032650,
];
