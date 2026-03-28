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
 * Upgrade training credits plugin.
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

    if ($oldversion < 2025120945) {
        $table = new xmldb_table('tool_mutrain_framework');
        $field = new xmldb_field('requiredcredits', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'publicaccess');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $sql = "UPDATE {tool_mutrain_framework}
                       SET requiredcredits = requiredtraining";
            $DB->execute($sql);
        }

        $table = new xmldb_table('tool_mutrain_framework');
        $field = new xmldb_field('requiredcredits', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, null, 'publicaccess');
        $dbman->change_field_notnull($table, $field);

        $table = new xmldb_table('tool_mutrain_framework');
        $field = new xmldb_field('requiredtraining');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025120945, 'tool', 'mutrain');
    }

    if ($oldversion < 2025121045) {
        $table = new xmldb_table('tool_mutrain_framework');
        $field = new xmldb_field('restrictafter', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'requiredcredits');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('tool_mutrain_framework');
        $field = new xmldb_field('restrictcontext', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'restrictafter');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('tool_mutrain_framework');
        $field = new xmldb_field('restrictedcompletion');
        if ($dbman->field_exists($table, $field)) {
            // The old dynamic aggregation based on program start date was removed,
            // switch to time-based aggregation to indicate they need to update it somehow manually.
            $sql = "UPDATE {tool_mutrain_framework}
                       SET restrictafter = :now
                     WHERE restrictedcompletion = 1";
            $DB->execute($sql, ['now' => time()]);

            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('tool_mutrain_credit');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('frameworkid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('credits', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('timereached', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('frameworkid', XMLDB_KEY_FOREIGN, ['frameworkid'], 'tool_mutrain_framework', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('frameworkid-userid', XMLDB_INDEX_UNIQUE, ['frameworkid', 'userid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025121045, 'tool', 'mutrain');
    }

    return true;
}
