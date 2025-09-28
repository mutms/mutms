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
 * Training fields upgrade.
 *
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade certifications.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_tool_mutrain_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025080950.01) {
        // Rename field public on table tool_mutrain_framework to publicaccess.
        $table = new xmldb_table('tool_mutrain_framework');
        $field = new xmldb_field('public', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'descriptionformat');

        // Launch rename field public.
        $dbman->rename_field($table, $field, 'publicaccess');

        // Mutrain savepoint reached.
        upgrade_plugin_savepoint(true, 2025080950.01, 'tool', 'mutrain');
    }

    return true;
}
