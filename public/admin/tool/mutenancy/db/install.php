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
 * Multi-tenancy installation script.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Make core changes necessary for multi-tenancy plugin.
 *
 * @return bool always true
 */
function xmldb_tool_mutenancy_install(): bool {
    global $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('context');
    $field = new xmldb_field('tenantid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'locked');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $table = new xmldb_table('context');
    $index = new xmldb_index('tenantid', XMLDB_INDEX_NOTUNIQUE, ['tenantid']);
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    $table = new xmldb_table('user');
    $field = new xmldb_field('tenantid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'moodlenetprofile');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $table = new xmldb_table('user');
    $index = new xmldb_index('tenantid', XMLDB_INDEX_NOTUNIQUE, ['tenantid']);
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    return true;
}
