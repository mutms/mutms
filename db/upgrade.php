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
 * Certification upgrade.
 *
 * @package tool_mucertify
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2023, Andrew Hancox
 */

/**
 * Upgrade certifications.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_tool_mucertify_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025042300) {
        $table = new xmldb_table('tool_mucertify_crt_snapshot');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('tool_mucertify_usr_snapshot');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2025042300, 'tool', 'mucertify');
    }

    if ($oldversion < 2025052300) {
        // Fix certification fields area.
        $DB->set_field('customfield_category', 'area', 'certification', ['component' => 'tool_mucertify', 'area' => 'fields']);

        upgrade_plugin_savepoint(true, 2025052300, 'tool', 'mucertify');
    }

    if ($oldversion < 2025080950.01) {
        // Rename field public on table tool_mucertify_certification to publicaccess.
        $table = new xmldb_table('tool_mucertify_certification');
        $field = new xmldb_field('public', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'presentationjson');

        // Launch rename field public.
        $dbman->rename_field($table, $field, 'publicaccess');

        // Mucertify savepoint reached.
        upgrade_plugin_savepoint(true, 2025080950.01, 'tool', 'mucertify');
    }

    if ($oldversion < 2025083145.01) {
        $table = new xmldb_table('tool_mucertify_period');
        $index = new xmldb_index('allocationid', XMLDB_INDEX_UNIQUE, ['allocationid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $table = new xmldb_table('tool_mucertify_period');
        $index = new xmldb_index('allocationid', XMLDB_INDEX_NOTUNIQUE, ['allocationid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025083145.01, 'tool', 'mucertify');
    }

    if ($oldversion < 2025092450.01) {
        // Use certification table name for tag itemtype.
        $DB->set_field(
            'tag_instance',
            'itemtype',
            'tool_mucertify_certification',
            ['itemtype' => 'certification', 'component' => 'tool_mucertify']
        );

        upgrade_plugin_savepoint(true, 2025092450.01, 'tool', 'mucertify');
    }

    return true;
}
