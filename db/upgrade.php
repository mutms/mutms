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
 * Programs plugin upgrade.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade programs.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_tool_muprog_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025042200) {
        $table = new xmldb_table('tool_muprog_prg_snapshot');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('tool_muprog_usr_snapshot');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('tool_muprog_allocation');
        $field = new xmldb_field('calendarupdated', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timecompleted');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025042200, 'tool', 'muprog');
    }

    if ($oldversion < 2025052300) {
        // Fix program fields area.
        $DB->set_field('customfield_category', 'area', 'program', ['component' => 'tool_muprog', 'area' => 'fields']);

        upgrade_plugin_savepoint(true, 2025052300, 'tool', 'muprog');
    }

    if ($oldversion < 2025080950.01) {
        // Rename field public on table tool_muprog_program to publicaccess.
        $table = new xmldb_table('tool_muprog_program');
        $field = new xmldb_field('public', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'presentationjson');

        // Launch rename field public.
        $dbman->rename_field($table, $field, 'publicaccess');

        // Muprog savepoint reached.
        upgrade_plugin_savepoint(true, 2025080950.01, 'tool', 'muprog');
    }

    if ($oldversion < 2025092450.01) {
        // Use program table name for tag itemtype.
        $DB->set_field(
            'tag_instance',
            'itemtype',
            'tool_muprog_program',
            ['itemtype' => 'program', 'component' => 'tool_muprog']
        );

        upgrade_plugin_savepoint(true, 2025092450.01, 'tool', 'muprog');
    }

    if ($oldversion < 2025111845) {
        $table = new xmldb_table('tool_muprog_source');

        $field = new xmldb_field('auxint4', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'auxint3');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('auxint5', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'auxint4');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025111845, 'tool', 'muprog');
    }

    if ($oldversion < 2025120945) {
        $table = new xmldb_table('tool_muprog_item');

        $index = new xmldb_index('trainingid', XMLDB_INDEX_NOTUNIQUE, ['trainingid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field('trainingid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'creditframeworkid');
        }

        $index = new xmldb_index('creditframeworkid', XMLDB_INDEX_NOTUNIQUE, ['creditframeworkid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025120945, 'tool', 'muprog');
    }

    if ($oldversion < 2025121145) {
        $table = new xmldb_table('tool_muprog_program');
        $field = new xmldb_field('itemscount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'enddatejson');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $sql = "UPDATE {tool_muprog_program}
                   SET itemscount = (SELECT COUNT('x')
                                       FROM {tool_muprog_item} i
                                      WHERE i.programid = {tool_muprog_program}.id
                                            AND (i.courseid IS NOT NULL OR i.creditframeworkid IS NOT NULL)
                                    )";
        $DB->execute($sql);

        $table = new xmldb_table('tool_muprog_allocation');
        $field = new xmldb_field('itemscompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'calendarupdated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $sql = "UPDATE {tool_muprog_allocation}
                   SET itemscompleted = (SELECT COUNT('x')
                                          FROM {tool_muprog_completion} c
                                          JOIN {tool_muprog_item} i ON i.id = c.itemid AND i.programid = {tool_muprog_allocation}.programid
                                         WHERE c.allocationid = {tool_muprog_allocation}.id
                                               AND (i.courseid IS NOT NULL OR i.creditframeworkid IS NOT NULL)
                                               AND c.timecompleted IS NOT NULL
                                       )";
        $DB->execute($sql);

        upgrade_plugin_savepoint(true, 2025121145, 'tool', 'muprog');
    }

    return true;
}
