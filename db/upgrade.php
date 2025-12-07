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
 * Plugin tool_mulib upgrades.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade mulib.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_tool_mulib_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025111845) {
        // Define table tool_mulib_extdb_server to be created.
        $table = new xmldb_table('tool_mulib_extdb_server');

        // Adding fields to table tool_mulib_extdb_server.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dsn', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dbuser', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('dbpass', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('dboptions', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('note', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table tool_mulib_extdb_server.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for tool_mulib_extdb_server.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_mulib_extdb_query to be created.
        $table = new xmldb_table('tool_mulib_extdb_query');

        // Adding fields to table tool_mulib_extdb_query.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('serverid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('component', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sqlquery', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('note', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table tool_mulib_extdb_query.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, ['contextid'], 'context', ['id']);
        $table->add_key('serverid', XMLDB_KEY_FOREIGN, ['serverid'], 'tool_mulib_extdb_server', ['id']);

        // Conditionally launch create table for tool_mulib_extdb_query.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mulib savepoint reached.
        upgrade_plugin_savepoint(true, 2025111845, 'tool', 'mulib');
    }

    if ($oldversion < 2025112345) {
        $table = new xmldb_table('tool_mulib_notification');

        $field = new xmldb_field('supervisorframeworkid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'instanceid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('supervisorframeworkid', XMLDB_INDEX_NOTUNIQUE, ['supervisorframeworkid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025112345, 'tool', 'mulib');
    }

    if ($oldversion < 2025112545) {
        $table = new xmldb_table('tool_mulib_context_parent');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('parentcontextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('contextid', XMLDB_KEY_UNIQUE, ['contextid'], 'context', ['id']);
        $table->add_key('parentcontextid', XMLDB_KEY_FOREIGN, ['parentcontextid'], 'context', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('tool_mulib_context_map');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('relatedcontextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('distance', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, ['contextid'], 'context', ['id']);
        $table->add_key('relatedcontextid', XMLDB_KEY_FOREIGN, ['relatedcontextid'], 'context', ['id']);
        $table->add_index('contextid-distance', XMLDB_INDEX_UNIQUE, ['contextid', 'distance']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025112545, 'tool', 'mulib');
    }

    if ($oldversion < 2025112745) {
        \tool_mulib\local\context_map_builder::build();
        upgrade_plugin_savepoint(true, 2025112745, 'tool', 'mulib');
    }

    return true;
}
