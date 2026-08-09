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
 * Multi-tenancy upgrade environment tests.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Check that correct version of multi-tenancy patch was applied.
 *
 * @param environment_results $result $result
 * @return environment_results updated results object
 */
function tool_mutenancy_environment_corepatch(environment_results $result): environment_results {
    $release = 'mutenancy-5.1.6-01';

    $result->setInfo("Core Multi-tenancy patch ($release is required)");

    $patchfile = __DIR__ . '/../../../../patch/mutenancy.php';
    if (!file_exists($patchfile)) {
        $result->setStatus(false);
        return $result;
    }

    $patchinfo = require($patchfile);

    if ($patchinfo['release'] !== $release) {
        $result->setStatus(false);
        return $result;
    }

    $result->setStatus(true);
    return $result;
}
