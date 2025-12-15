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
 * Training credits upgrade.
 *
 * @package    customfield_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade training credit custom fields.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_customfield_mutrain_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025120945) {
        $fieldids = $DB->get_fieldset('customfield_field', 'id', ['type' => 'mutrain']);
        if ($fieldids) {
            $fieldids = implode(',', $fieldids);
            $sql = "UPDATE {customfield_data}
                       SET decvalue = intvalue
                     WHERE fieldid IN ($fieldids)
                           AND intvalue IS NOT NULL AND decvalue IS NULL";
            $DB->execute($sql);
            $sql = "UPDATE {customfield_data}
                       SET intvalue = NULL
                     WHERE fieldid IN ($fieldids)";
            $DB->execute($sql);
        }

        upgrade_plugin_savepoint(true, 2025120945, 'customfield', 'mutrain');
    }

    return true;
}
